<?php

namespace App\Services;

use App\Models\FileNumberReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service for managing file number reservations
 * 
 * Handles reservation, release, and cleanup of file numbers to prevent
 * race conditions and duplicate assignments in draft applications.
 */
class FileNumberReservationService
{
    /**
     * Reserve the next available file number for a draft
     *
     * @param string $landUse - COMMERCIAL, RESIDENTIAL, INDUSTRIAL, MIXED
     * @param int $year - Year for the file number
     * @param string|null $draftId - UUID of the associated draft
     * @return array - ['success' => bool, 'file_number' => string, 'serial' => int, 'reservation_id' => int]
     */
    public function reserveFileNumber(string $landUse, int $year, ?string $draftId = null): array
    {
        DB::connection('sqlsrv')->beginTransaction();

        try {
            // Normalize land use
            $normalizedLandUse = $this->normalizeLandUse($landUse);
            
            // Get next available serial (considering existing reservations and used numbers)
            $serialInfo = $this->getNextAvailableSerial($normalizedLandUse, $year);
            $nextSerial = $serialInfo['serial'];
            $isGapFilled = $serialInfo['is_gap_filled'];
            $gapReason = $serialInfo['gap_reason'];
            $nextNewSerial = $serialInfo['next_new_serial'];
            
            // Generate file number
            $fileNumber = $this->generateFileNumber($normalizedLandUse, $nextSerial, $year);
            
            // Check if this file number is already reserved or used
            $existingReservation = FileNumberReservation::where('file_number', $fileNumber)
                ->whereIn('status', [FileNumberReservation::STATUS_RESERVED, FileNumberReservation::STATUS_USED])
                ->first();
            
            if ($existingReservation) {
                // If it's an expired reservation, mark it and try next serial
                if ($existingReservation->isExpired()) {
                    $existingReservation->markAsExpired();
                    DB::connection('sqlsrv')->commit();
                    
                    // Recursively try next number
                    return $this->reserveFileNumber($landUse, $year, $draftId);
                }
                
                DB::connection('sqlsrv')->rollBack();
                
                Log::warning('File number already reserved', [
                    'file_number' => $fileNumber,
                    'existing_status' => $existingReservation->status,
                    'draft_id' => $draftId
                ]);
                
                return [
                    'success' => false,
                    'message' => 'File number already reserved',
                    'file_number' => null,
                    'serial' => null,
                ];
            }
            
            // Create reservation
            $reservation = FileNumberReservation::create([
                'file_number' => $fileNumber,
                'land_use_type' => $normalizedLandUse,
                'serial_number' => $nextSerial,
                'year' => $year,
                'status' => FileNumberReservation::STATUS_RESERVED,
                'draft_id' => $draftId,
                'reserved_at' => now(),
                'expires_at' => now()->addDays(FileNumberReservation::EXPIRY_DAYS),
            ]);
            
            DB::connection('sqlsrv')->commit();
            
            Log::info('File number reserved successfully', [
                'file_number' => $fileNumber,
                'serial' => $nextSerial,
                'draft_id' => $draftId,
                'reservation_id' => $reservation->id,
                'expires_at' => $reservation->expires_at->toDateTimeString(),
                'is_gap_filled' => $isGapFilled ?? false
            ]);
            
            return [
                'success' => true,
                'file_number' => $fileNumber,
                'serial' => $nextSerial,
                'reservation_id' => $reservation->id,
                'expires_at' => $reservation->expires_at,
                'is_gap_filled' => $isGapFilled ?? false,
                'gap_reason' => $gapReason ?? null,
                'next_new_serial' => $nextNewSerial ?? null,
            ];
            
        } catch (Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            
            Log::error('Failed to reserve file number', [
                'error' => $e->getMessage(),
                'land_use' => $landUse,
                'year' => $year,
                'draft_id' => $draftId,
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to reserve file number: ' . $e->getMessage(),
                'file_number' => null,
                'serial' => null,
            ];
        }
    }

    /**
     * Mark a reserved file number as used when application is submitted
     *
     * @param string $fileNumber - The file number to mark as used
     * @param int $applicationId - The final application ID
     * @return bool
     */
    public function markAsUsed(string $fileNumber, int $applicationId): bool
    {
        try {
            $reservation = FileNumberReservation::where('file_number', $fileNumber)
                ->where('status', FileNumberReservation::STATUS_RESERVED)
                ->first();
            
            if (!$reservation) {
                Log::warning('No reservation found to mark as used', [
                    'file_number' => $fileNumber,
                    'application_id' => $applicationId
                ]);
                return false;
            }
            
            $result = $reservation->markAsUsed($applicationId);
            
            Log::info('File number reservation marked as used', [
                'file_number' => $fileNumber,
                'application_id' => $applicationId,
                'reservation_id' => $reservation->id
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Failed to mark reservation as used', [
                'error' => $e->getMessage(),
                'file_number' => $fileNumber,
                'application_id' => $applicationId
            ]);
            return false;
        }
    }

    /**
     * Release a file number reservation (e.g., when draft is deleted)
     *
     * @param string $fileNumber - The file number to release
     * @return bool
     */
    public function releaseReservation(string $fileNumber): bool
    {
        try {
            $reservation = FileNumberReservation::where('file_number', $fileNumber)
                ->where('status', FileNumberReservation::STATUS_RESERVED)
                ->first();
            
            if (!$reservation) {
                Log::info('No active reservation found to release', [
                    'file_number' => $fileNumber
                ]);
                return false;
            }
            
            $result = $reservation->markAsReleased();
            
            Log::info('File number reservation released', [
                'file_number' => $fileNumber,
                'reservation_id' => $reservation->id
            ]);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('Failed to release reservation', [
                'error' => $e->getMessage(),
                'file_number' => $fileNumber
            ]);
            return false;
        }
    }

    /**
     * Clean up expired reservations
     * Marks reservations as expired if they've passed expiry date and haven't been used
     *
     * @return int - Number of reservations cleaned up
     */
    public function cleanupExpiredReservations(): int
    {
        try {
            $expiredCount = 0;
            
            // Find all expired reservations
            $expiredReservations = FileNumberReservation::expired()->get();
            
            foreach ($expiredReservations as $reservation) {
                $reservation->markAsExpired();
                $expiredCount++;
                
                Log::info('Expired reservation marked', [
                    'file_number' => $reservation->file_number,
                    'reservation_id' => $reservation->id,
                    'reserved_at' => $reservation->reserved_at->toDateTimeString(),
                    'expired_at' => $reservation->expires_at->toDateTimeString()
                ]);
            }
            
            if ($expiredCount > 0) {
                Log::info('Cleaned up expired reservations', [
                    'count' => $expiredCount
                ]);
            }
            
            return $expiredCount;
            
        } catch (Exception $e) {
            Log::error('Failed to cleanup expired reservations', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get next available serial number considering reservations and used numbers
     * Uses GAP-FILLING strategy: reuses released/expired serial numbers before creating new ones
     * Returns array with serial info and gap-filling metadata
     *
     * @param string $landUse
     * @param int $year
     * @return array ['serial' => int, 'is_gap_filled' => bool, 'gap_reason' => string|null, 'next_new_serial' => int|null]
     */
    private function getNextAvailableSerial(string $landUse, int $year): array
    {
        // STRATEGY 1: GAP-FILLING
        // First, check for any released or expired reservations that can be reused
        // This ensures no gaps in the final sequence (ST-RES-2025-1, 2, 3... with no missing numbers)
        $availableGap = FileNumberReservation::forLandUseYear($landUse, $year)
            ->whereIn('status', [
                FileNumberReservation::STATUS_RELEASED,
                FileNumberReservation::STATUS_EXPIRED
            ])
            ->orderBy('serial_number', 'asc')  // Fill smallest gap first
            ->lockForUpdate()  // Prevent concurrent access to same gap
            ->first();
        
        if ($availableGap) {
            // Calculate what the next NEW serial would be (for user info)
            $nextNewSerial = $this->calculateNextNewSerial($landUse, $year);
            
            $gapReason = $availableGap->status === FileNumberReservation::STATUS_EXPIRED
                ? 'This file number was previously reserved but expired after 3 days of inactivity.'
                : 'This file number was previously reserved but the draft was deleted.';
            
            Log::info('Reusing released/expired serial number (gap-filling)', [
                'land_use' => $landUse,
                'year' => $year,
                'serial_number' => $availableGap->serial_number,
                'previous_status' => $availableGap->status,
                'next_new_serial' => $nextNewSerial
            ]);
            
            // Delete the old reservation record (it will be replaced with new one)
            $availableGap->delete();
            
            return [
                'serial' => $availableGap->serial_number,
                'is_gap_filled' => true,
                'gap_reason' => $gapReason,
                'next_new_serial' => $nextNewSerial,
            ];
        }
        
        // STRATEGY 2: SEQUENTIAL INCREMENT
        // No gaps available, get next sequential number
        
        // Get the current highest serial from land_use_serials table
        $currentSerial = DB::connection('sqlsrv')
            ->table('land_use_serials')
            ->where('land_use_type', $landUse)
            ->where('year', $year)
            ->lockForUpdate()
            ->value('current_serial');
        
        // Get highest serial from active reservations and used reservations
        $highestReservedSerial = FileNumberReservation::forLandUseYear($landUse, $year)
            ->whereIn('status', [FileNumberReservation::STATUS_RESERVED, FileNumberReservation::STATUS_USED])
            ->max('serial_number');
        
        // Get highest serial from actual applications (fallback check)
        $highestApplicationSerial = DB::connection('sqlsrv')
            ->table('mother_applications')
            ->where('land_use', $landUse)
            ->whereYear('created_at', $year)
            ->whereNotNull('np_fileno')
            ->selectRaw("MAX(CAST(SUBSTRING(np_fileno, LEN(np_fileno) - CHARINDEX('-', REVERSE(np_fileno)) + 2, LEN(np_fileno)) AS INT)) as max_serial")
            ->value('max_serial');
        
        // Take the maximum of all sources + 1
        $nextSerial = max(
            $currentSerial ?? 0,
            $highestReservedSerial ?? 0,
            $highestApplicationSerial ?? 0
        ) + 1;
        
        // Update the land_use_serials table to reflect this reservation
        $serialRecord = DB::connection('sqlsrv')
            ->table('land_use_serials')
            ->where('land_use_type', $landUse)
            ->where('year', $year)
            ->first();
        
        if ($serialRecord) {
            // Only update if our calculated serial is higher
            if ($nextSerial > $serialRecord->current_serial) {
                DB::connection('sqlsrv')
                    ->table('land_use_serials')
                    ->where('land_use_type', $landUse)
                    ->where('year', $year)
                    ->update([
                        'current_serial' => $nextSerial,
                        'updated_at' => now()
                    ]);
            }
        } else {
            // Create new record
            $prefix = $this->getLandUsePrefix($landUse);
            
            DB::connection('sqlsrv')
                ->table('land_use_serials')
                ->insert([
                    'land_use_type' => $landUse,
                    'prefix' => $prefix,
                    'year' => $year,
                    'current_serial' => $nextSerial,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
        }
        
        return [
            'serial' => $nextSerial,
            'is_gap_filled' => false,
            'gap_reason' => null,
            'next_new_serial' => null,
        ];
    }

    /**
     * Calculate what the next NEW serial would be (ignoring gaps)
     * Used to show users what number they would get if not filling a gap
     *
     * @param string $landUse
     * @param int $year
     * @return int
     */
    private function calculateNextNewSerial(string $landUse, int $year): int
    {
        // Get highest serial from active reservations and used reservations
        $highestSerial = FileNumberReservation::forLandUseYear($landUse, $year)
            ->whereIn('status', [FileNumberReservation::STATUS_RESERVED, FileNumberReservation::STATUS_USED])
            ->max('serial_number');
        
        // Get highest from land_use_serials
        $currentSerial = DB::connection('sqlsrv')
            ->table('land_use_serials')
            ->where('land_use_type', $landUse)
            ->where('year', $year)
            ->value('current_serial');
        
        // Get highest from actual applications
        $highestApplicationSerial = DB::connection('sqlsrv')
            ->table('mother_applications')
            ->where('land_use', $landUse)
            ->whereYear('created_at', $year)
            ->whereNotNull('np_fileno')
            ->selectRaw("MAX(CAST(SUBSTRING(np_fileno, LEN(np_fileno) - CHARINDEX('-', REVERSE(np_fileno)) + 2, LEN(np_fileno)) AS INT)) as max_serial")
            ->value('max_serial');
        
        return max(
            $highestSerial ?? 0,
            $currentSerial ?? 0,
            $highestApplicationSerial ?? 0
        ) + 1;
    }

    /**
     * Normalize land use type to standard format
     *
     * @param string $landUse
     * @return string
     */
    private function normalizeLandUse(string $landUse): string
    {
        return match(strtoupper(trim($landUse))) {
            'COMMERCIAL', 'COMMERCIAL USE' => 'COMMERCIAL',
            'INDUSTRIAL', 'INDUSTRIAL USE' => 'INDUSTRIAL',
            'RESIDENTIAL', 'RESIDENTIAL USE' => 'RESIDENTIAL',
            'MIXED', 'MIXED USE' => 'MIXED',
            default => 'RESIDENTIAL'
        };
    }

    /**
     * Get prefix for land use type
     *
     * @param string $landUse
     * @return string
     */
    private function getLandUsePrefix(string $landUse): string
    {
        return match($landUse) {
            'COMMERCIAL' => 'ST-COM',
            'INDUSTRIAL' => 'ST-IND',
            'RESIDENTIAL' => 'ST-RES',
            'MIXED' => 'ST-MIXED',
            default => 'ST-RES'
        };
    }

    /**
     * Get land use code for file number
     *
     * @param string $landUse
     * @return string
     */
    private function getLandUseCode(string $landUse): string
    {
        return match($landUse) {
            'COMMERCIAL' => 'COM',
            'INDUSTRIAL' => 'IND',
            'RESIDENTIAL' => 'RES',
            'MIXED' => 'MIXED',
            default => 'RES'
        };
    }

    /**
     * Generate file number from components
     *
     * @param string $landUse
     * @param int $serial
     * @param int $year
     * @return string
     */
    private function generateFileNumber(string $landUse, int $serial, int $year): string
    {
        $landUseCode = $this->getLandUseCode($landUse);
        return sprintf('NPFN-%d-%s-%05d', $year, $landUseCode, $serial);
    }

    /**
     * Get reservation statistics
     *
     * @return array
     */
    public function getReservationStats(): array
    {
        return [
            'total' => FileNumberReservation::count(),
            'reserved' => FileNumberReservation::reserved()->count(),
            'used' => FileNumberReservation::used()->count(),
            'expired' => FileNumberReservation::where('status', FileNumberReservation::STATUS_EXPIRED)->count(),
            'released' => FileNumberReservation::where('status', FileNumberReservation::STATUS_RELEASED)->count(),
            'expiring_soon' => FileNumberReservation::reserved()
                ->where('expires_at', '<', now()->addHours(24))
                ->count(),
        ];
    }
}
