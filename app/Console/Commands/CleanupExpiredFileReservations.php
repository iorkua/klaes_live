<?php

namespace App\Console\Commands;

use App\Services\FileNumberReservationService;
use Illuminate\Console\Command;

class CleanupExpiredFileReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'filenumbers:cleanup
                            {--dry-run : Show what would be cleaned up without making changes}
                            {--force : Force cleanup without confirmation}
                            {--stats : Show reservation statistics}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired file number reservations (3+ days old)';

    /**
     * File number reservation service
     *
     * @var FileNumberReservationService
     */
    protected $reservationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(FileNumberReservationService $reservationService)
    {
        parent::__construct();
        $this->reservationService = $reservationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('File Number Reservation Cleanup Tool');
        $this->newLine();

        // Show stats if requested
        if ($this->option('stats')) {
            $this->showStatistics();
            return 0;
        }

        // Check for dry-run mode
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get statistics before cleanup
        $stats = $this->reservationService->getReservationStats();
        
        $this->info('Current Reservation Status:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Active Reservations', $stats['reserved']],
                ['Used Reservations', $stats['used']],
                ['Expired Reservations', $stats['expired']],
                ['Released Reservations', $stats['released']],
                ['Expiring Soon (< 24h)', $stats['expiring_soon']],
                ['Total', $stats['total']],
            ]
        );
        $this->newLine();

        // Find expired reservations
        $expiredReservations = \App\Models\FileNumberReservation::expired()->get();
        
        if ($expiredReservations->isEmpty()) {
            $this->info('✓ No expired reservations found. System is clean!');
            return 0;
        }

        $this->warn("Found {$expiredReservations->count()} expired reservation(s)");
        $this->newLine();

        // Show details of expired reservations
        if ($this->option('verbose') || $dryRun) {
            $this->info('Expired Reservations:');
            $data = [];
            foreach ($expiredReservations as $reservation) {
                $data[] = [
                    $reservation->file_number,
                    $reservation->land_use_type,
                    $reservation->reserved_at->format('Y-m-d H:i'),
                    $reservation->expires_at->format('Y-m-d H:i'),
                    $reservation->expires_at->diffForHumans(),
                ];
            }
            $this->table(
                ['File Number', 'Land Use', 'Reserved At', 'Expired At', 'Expired'],
                $data
            );
            $this->newLine();
        }

        if ($dryRun) {
            $this->info("Would mark {$expiredReservations->count()} reservation(s) as expired");
            return 0;
        }

        // Confirm cleanup
        if (!$force) {
            if (!$this->confirm("Mark {$expiredReservations->count()} reservation(s) as expired?", true)) {
                $this->warn('Cleanup cancelled by user');
                return 1;
            }
        }

        // Perform cleanup
        $this->info('Cleaning up expired reservations...');
        $progressBar = $this->output->createProgressBar($expiredReservations->count());
        $progressBar->start();

        $cleanedCount = 0;
        $failedCount = 0;

        foreach ($expiredReservations as $reservation) {
            try {
                if ($reservation->markAsExpired()) {
                    $cleanedCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                $this->error("Failed to mark {$reservation->file_number}: {$e->getMessage()}");
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show results
        if ($cleanedCount > 0) {
            $this->info("✓ Successfully cleaned up {$cleanedCount} expired reservation(s)");
        }
        if ($failedCount > 0) {
            $this->error("✗ Failed to clean up {$failedCount} reservation(s)");
        }

        // Show updated statistics
        $this->newLine();
        $this->info('Updated Reservation Status:');
        $statsAfter = $this->reservationService->getReservationStats();
        $this->table(
            ['Status', 'Count'],
            [
                ['Active Reservations', $statsAfter['reserved']],
                ['Used Reservations', $statsAfter['used']],
                ['Expired Reservations', $statsAfter['expired']],
                ['Released Reservations', $statsAfter['released']],
                ['Total', $statsAfter['total']],
            ]
        );

        return 0;
    }

    /**
     * Show reservation statistics
     */
    protected function showStatistics()
    {
        $stats = $this->reservationService->getReservationStats();
        
        $this->info('File Number Reservation Statistics');
        $this->newLine();
        
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Reservations', $stats['total']],
                ['Active Reservations', "<fg=green>{$stats['reserved']}</>"],
                ['Used Reservations', "<fg=blue>{$stats['used']}</>"],
                ['Expired Reservations', "<fg=red>{$stats['expired']}</>"],
                ['Released Reservations', "<fg=yellow>{$stats['released']}</>"],
                ['Expiring Soon (< 24h)', "<fg=yellow>{$stats['expiring_soon']}</>"],
            ]
        );
        
        $this->newLine();
        
        // Show breakdown by land use
        $byLandUse = \App\Models\FileNumberReservation::selectRaw('land_use_type, status, COUNT(*) as count')
            ->groupBy('land_use_type', 'status')
            ->orderBy('land_use_type')
            ->orderBy('status')
            ->get();
        
        if ($byLandUse->isNotEmpty()) {
            $this->info('Breakdown by Land Use Type:');
            $data = [];
            foreach ($byLandUse as $item) {
                $data[] = [
                    $item->land_use_type,
                    $item->status,
                    $item->count
                ];
            }
            $this->table(['Land Use', 'Status', 'Count'], $data);
        }
    }
}
