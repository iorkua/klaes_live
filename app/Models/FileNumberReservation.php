<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FileNumberReservation extends Model
{
    /**
     * The database connection to use
     *
     * @var string
     */
    protected $connection = 'sqlsrv';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'file_number_reservations';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'file_number',
        'land_use_type',
        'serial_number',
        'year',
        'status',
        'draft_id',
        'application_id',
        'reserved_by',
        'reserved_at',
        'expires_at',
        'used_at',
        'released_at',
        'metadata',
        'client_ip',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'serial_number' => 'integer',
        'year' => 'integer',
        'reserved_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'released_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Reservation statuses
     */
    const STATUS_RESERVED = 'reserved';
    const STATUS_USED = 'used';
    const STATUS_RELEASED = 'released';
    const STATUS_EXPIRED = 'expired';

    /**
     * Reservation expiry duration (3 days)
     */
    const EXPIRY_DAYS = 3;

    /**
     * Get the user who reserved this file number.
     */
    public function reservedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserved_by');
    }

    /**
     * Get the draft associated with this reservation.
     */
    public function draft(): BelongsTo
    {
        return $this->belongsTo(MotherApplicationDraft::class, 'draft_id', 'draft_id');
    }

    /**
     * Get the final application associated with this reservation.
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(MotherApplication::class, 'application_id');
    }

    /**
     * Scope to get only reserved (active) reservations
     */
    public function scopeReserved($query)
    {
        return $query->where('status', self::STATUS_RESERVED);
    }

    /**
     * Scope to get expired reservations
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_RESERVED)
                     ->where('expires_at', '<', now());
    }

    /**
     * Scope to get reservations by land use and year
     */
    public function scopeForLandUseYear($query, string $landUse, int $year)
    {
        return $query->where('land_use_type', $landUse)
                     ->where('year', $year);
    }

    /**
     * Scope to get used reservations
     */
    public function scopeUsed($query)
    {
        return $query->where('status', self::STATUS_USED);
    }

    /**
     * Check if reservation is expired
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_RESERVED && $this->expires_at < now();
    }

    /**
     * Check if reservation is active (reserved and not expired)
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_RESERVED && $this->expires_at >= now();
    }

    /**
     * Mark reservation as used
     */
    public function markAsUsed(int $applicationId): bool
    {
        $this->status = self::STATUS_USED;
        $this->application_id = $applicationId;
        $this->used_at = now();
        return $this->save();
    }

    /**
     * Mark reservation as released (manually cancelled)
     */
    public function markAsReleased(): bool
    {
        $this->status = self::STATUS_RELEASED;
        $this->released_at = now();
        return $this->save();
    }

    /**
     * Mark reservation as expired
     */
    public function markAsExpired(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        return $this->save();
    }

    /**
     * Extend reservation expiry by specified days
     */
    public function extendExpiry(int $days = 3): bool
    {
        if ($this->status === self::STATUS_RESERVED) {
            $this->expires_at = now()->addDays($days);
            return $this->save();
        }
        return false;
    }

    /**
     * Boot method to set default values
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            // Set defaults on creation
            if (!$reservation->reserved_at) {
                $reservation->reserved_at = now();
            }
            if (!$reservation->expires_at) {
                $reservation->expires_at = now()->addDays(self::EXPIRY_DAYS);
            }
            if (!$reservation->reserved_by) {
                $reservation->reserved_by = Auth::id();
            }
            if (!$reservation->client_ip) {
                $reservation->client_ip = request()->ip();
            }
            if (!$reservation->user_agent) {
                $reservation->user_agent = request()->userAgent();
            }
        });
    }
}
