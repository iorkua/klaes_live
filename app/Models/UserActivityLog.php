<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserActivityLog extends Model
{
    use HasFactory;

    // Specify SQL Server connection
    protected $connection = 'sqlsrv';
    
    protected $table = 'user_activity_logs';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'login_time',
        'logout_time',
        'is_online',
        'session_id',
        'device_type',
        'browser',
        'platform',
        'location',
        'activity_type',
        'activity_description'
    ];

    protected $casts = [
        'login_time' => 'datetime',
        'logout_time' => 'datetime',
        'is_online' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the activity log
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted login time
     */
    public function getFormattedLoginTimeAttribute()
    {
        return $this->login_time ? $this->login_time->format('Y-m-d H:i:s') : '-';
    }

    /**
     * Get formatted logout time
     */
    public function getFormattedLogoutTimeAttribute()
    {
        return $this->logout_time ? $this->logout_time->format('Y-m-d H:i:s') : '-';
    }

    /**
     * Get session duration
     */
    public function getSessionDurationAttribute()
    {
        if (!$this->login_time) {
            return '-';
        }

        $endTime = $this->logout_time ?: now();
        $duration = $this->login_time->diffInMinutes($endTime);
        
        if ($duration < 60) {
            return $duration . ' min';
        } else {
            $hours = floor($duration / 60);
            $minutes = $duration % 60;
            return $hours . 'h ' . $minutes . 'm';
        }
    }

    /**
     * Get online status badge
     */
    public function getOnlineStatusBadgeAttribute()
    {
        if ($this->is_online) {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <span class="w-2 h-2 bg-green-400 rounded-full mr-1"></span>
                        Online
                    </span>';
        } else {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                        Offline
                    </span>';
        }
    }

    /**
     * Scope for online users
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    /**
     * Scope for offline users
     */
    public function scopeOffline($query)
    {
        return $query->where('is_online', false);
    }

    /**
     * Scope for recent activities
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Create or update activity log
     */
    public static function logActivity($userId, $activityType = 'login', $additionalData = [])
    {
        $userAgent = request()->header('User-Agent');
        $ipAddress = request()->ip();
        
        // Parse user agent for device info
        $deviceInfo = self::parseUserAgent($userAgent);
        
        $data = array_merge([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceInfo['device'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'session_id' => session()->getId(),
            'activity_type' => $activityType,
            'is_online' => $activityType === 'login',
        ], $additionalData);

        if ($activityType === 'login') {
            $data['login_time'] = now();
            
            // Mark previous sessions as offline
            self::where('user_id', $userId)
                ->where('is_online', true)
                ->update(['is_online' => false, 'logout_time' => now()]);
        } elseif ($activityType === 'logout') {
            $data['logout_time'] = now();
            $data['is_online'] = false;
        }

        return self::create($data);
    }

    /**
     * Parse user agent string
     */
    private static function parseUserAgent($userAgent)
    {
        $device = 'desktop';
        $browser = 'Unknown';
        $platform = 'Unknown';

        // Detect device type
        if (preg_match('/mobile|android|iphone|ipad|phone/i', $userAgent)) {
            $device = 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $userAgent)) {
            $device = 'tablet';
        }

        // Detect browser
        if (preg_match('/chrome/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/edge/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/opera/i', $userAgent)) {
            $browser = 'Opera';
        }

        // Detect platform
        if (preg_match('/windows/i', $userAgent)) {
            $platform = 'Windows';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $platform = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $platform = 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            $platform = 'Android';
        } elseif (preg_match('/ios|iphone|ipad/i', $userAgent)) {
            $platform = 'iOS';
        }

        return [
            'device' => $device,
            'browser' => $browser,
            'platform' => $platform
        ];
    }

    /**
     * Get activity statistics
     */
    public static function getActivityStats($days = 30)
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_sessions' => self::where('created_at', '>=', $startDate)->count(),
            'unique_users' => self::where('created_at', '>=', $startDate)->distinct('user_id')->count(),
            'online_users' => self::where('is_online', true)->count(),
            'avg_session_duration' => self::getAverageSessionDuration($days),
            'top_browsers' => self::getTopBrowsers($days),
            'top_platforms' => self::getTopPlatforms($days),
        ];
    }

    /**
     * Get average session duration
     */
    private static function getAverageSessionDuration($days = 30)
    {
        $sessions = self::where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('login_time')
            ->whereNotNull('logout_time')
            ->get();

        if ($sessions->isEmpty()) {
            return 0;
        }

        $totalMinutes = $sessions->sum(function ($session) {
            return $session->login_time->diffInMinutes($session->logout_time);
        });

        return round($totalMinutes / $sessions->count(), 2);
    }

    /**
     * Get top browsers
     */
    private static function getTopBrowsers($days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('browser, COUNT(*) as count')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }

    /**
     * Get top platforms
     */
    private static function getTopPlatforms($days = 30)
    {
        return self::where('created_at', '>=', now()->subDays($days))
            ->selectRaw('platform, COUNT(*) as count')
            ->groupBy('platform')
            ->orderByDesc('count')
            ->limit(5)
            ->get();
    }
}