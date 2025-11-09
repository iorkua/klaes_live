<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FileTracker extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'file_tracker';
    
    protected $fillable = [
        'tracking_id',
        'file_number',
        'file_title',
        'file_type',
        'priority',
        'created_by',
        'created_by_name',
        'department',
        'description',
        'status',
        'date_created',
        'deadline',
        'movement_log',
        'current_office_code',
        'current_office_name',
        'total_offices',
        'completed_offices',
        'notes',
    ];

    protected $casts = [
        'date_created' => 'datetime',
        'deadline' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'movement_log' => 'array',
    ];

    // Priority constants
    const PRIORITY_LOW = 'LOW';
    const PRIORITY_MEDIUM = 'MEDIUM';
    const PRIORITY_HIGH = 'HIGH';

    // Status constants
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_CANCELLED = 'CANCELLED';

    /**
     * Generate a unique tracking ID
     */
    public static function generateTrackingId()
    {
        $timestamp = now()->format('ymdHis');
        $random = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4));
        return "TRK-{$timestamp}-{$random}";
    }

    /**
     * Add a movement log entry
     */
    public function addMovementLog($officeCode, $officeName, $logInTime, $logInDate, $notes = null, $userId = null, $userName = null)
    {
        $currentLog = $this->movement_log ?: [];
        
        // Generate log ID
        $logId = 'LOG-' . now()->format('YmdHis') . '-' . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        $logEntry = [
            'log_id' => $logId,
            'office_code' => $officeCode,
            'office_name' => $officeName,
            'log_in_time' => $logInTime,
            'log_in_date' => $logInDate,
            'notes' => $notes,
            'user_id' => $userId ?: auth()->id(),
            'user_name' => $userName ?: auth()->user()->name ?? 'System',
            'timestamp' => now()->toISOString(),
            'status' => 'active'
        ];
        
        $currentLog[] = $logEntry;
        
        $this->movement_log = $currentLog;
        $this->current_office_code = $officeCode;
        $this->current_office_name = $officeName;
        $this->completed_offices = count($currentLog);
        
        return $this->save();
    }

    /**
     * Complete the current movement log entry
     */
    public function completeCurrentMovement($logOutTime = null, $notes = null)
    {
        $currentLog = $this->movement_log ?: [];
        
        if (!empty($currentLog)) {
            $lastIndex = count($currentLog) - 1;
            $currentLog[$lastIndex]['log_out_time'] = $logOutTime ?: now()->format('H:i');
            $currentLog[$lastIndex]['log_out_date'] = now()->format('Y-m-d');
            $currentLog[$lastIndex]['status'] = 'completed';
            if ($notes) {
                $currentLog[$lastIndex]['completion_notes'] = $notes;
            }
            
            $this->movement_log = $currentLog;
            return $this->save();
        }
        
        return false;
    }

    /**
     * Get the current active movement
     */
    public function getCurrentMovement()
    {
        $log = $this->movement_log ?: [];
        
        foreach (array_reverse($log) as $entry) {
            if ($entry['status'] === 'active') {
                return $entry;
            }
        }
        
        return null;
    }

    /**
     * Check if tracker is overdue
     */
    public function getIsOverdueAttribute()
    {
        return $this->deadline && Carbon::parse($this->deadline)->isPast();
    }

    /**
     * Get days until deadline
     */
    public function getDaysUntilDeadlineAttribute()
    {
        if (!$this->deadline) {
            return null;
        }
        
        return Carbon::now()->diffInDays(Carbon::parse($this->deadline), false);
    }

    /**
     * Get completion percentage
     */
    public function getCompletionPercentageAttribute()
    {
        if ($this->total_offices === 0) {
            return 0;
        }
        
        return round(($this->completed_offices / $this->total_offices) * 100);
    }

    /**
     * Scope for active trackers
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for completed trackers
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for overdue trackers
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
                    ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for priority filtering
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for user filtering
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope for department filtering
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Get user who created this tracker
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}