<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivityLogSetting extends Model
{
    use HasFactory;

    // Specify SQL Server connection
    protected $connection = 'sqlsrv';
    
    protected $table = 'user_activity_log_settings';

    protected $fillable = [
        'user_id',
        'cleanup_interval',
        'retention_days',
        'refresh_interval',
        'records_per_page',
        'auto_logout_inactive',
        'track_failed_logins',
        'ip_based_alerts',
        'email_notifications',
        'settings_data'
    ];

    protected $casts = [
        'retention_days' => 'integer',
        'refresh_interval' => 'integer',
        'records_per_page' => 'integer',
        'auto_logout_inactive' => 'boolean',
        'track_failed_logins' => 'boolean',
        'ip_based_alerts' => 'boolean',
        'email_notifications' => 'boolean',
        'settings_data' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the settings
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get default settings
     */
    public static function getDefaults()
    {
        return [
            'cleanup_interval' => 'weekly',
            'retention_days' => 90,
            'refresh_interval' => 30,
            'records_per_page' => 25,
            'auto_logout_inactive' => false,
            'track_failed_logins' => true,
            'ip_based_alerts' => false,
            'email_notifications' => false,
        ];
    }

    /**
     * Get settings for a user (or global if no user specified)
     */
    public static function getSettings($userId = null)
    {
        $settings = self::where('user_id', $userId)->first();
        
        if (!$settings) {
            return self::getDefaults();
        }

        return array_merge(self::getDefaults(), $settings->toArray());
    }

    /**
     * Save settings for a user (or global if no user specified)
     */
    public static function saveSettings($data, $userId = null)
    {
        $settings = self::updateOrCreate(
            ['user_id' => $userId],
            array_merge($data, ['user_id' => $userId])
        );

        return $settings;
    }

    /**
     * Get global settings (system-wide defaults)
     */
    public static function getGlobalSettings()
    {
        return self::getSettings(null);
    }

    /**
     * Save global settings (system-wide defaults)
     */
    public static function saveGlobalSettings($data)
    {
        return self::saveSettings($data, null);
    }

    /**
     * Get user-specific settings with fallback to global
     */
    public static function getUserSettings($userId)
    {
        $userSettings = self::where('user_id', $userId)->first();
        $globalSettings = self::getGlobalSettings();

        if (!$userSettings) {
            return $globalSettings;
        }

        // Merge global settings with user-specific overrides
        return array_merge($globalSettings, $userSettings->toArray());
    }

    /**
     * Check if cleanup should run based on interval
     */
    public static function shouldRunCleanup()
    {
        $settings = self::getGlobalSettings();
        $lastCleanup = cache('activity_logs_last_cleanup', now()->subDays(1));
        
        switch ($settings['cleanup_interval']) {
            case 'daily':
                return $lastCleanup->diffInHours(now()) >= 24;
            case 'weekly':
                return $lastCleanup->diffInDays(now()) >= 7;
            case 'monthly':
                return $lastCleanup->diffInDays(now()) >= 30;
            default:
                return false;
        }
    }

    /**
     * Mark cleanup as completed
     */
    public static function markCleanupCompleted()
    {
        cache(['activity_logs_last_cleanup' => now()], now()->addDays(31));
    }

    /**
     * Get cleanup settings
     */
    public static function getCleanupSettings()
    {
        $settings = self::getGlobalSettings();
        
        return [
            'interval' => $settings['cleanup_interval'],
            'retention_days' => $settings['retention_days'],
            'should_run' => self::shouldRunCleanup(),
            'last_run' => cache('activity_logs_last_cleanup', 'Never'),
        ];
    }

    /**
     * Validate settings data
     */
    public static function validateSettings($data)
    {
        $rules = [
            'cleanup_interval' => 'in:daily,weekly,monthly',
            'retention_days' => 'integer|min:1|max:365',
            'refresh_interval' => 'integer|min:10|max:300',
            'records_per_page' => 'integer|min:10|max:100',
            'auto_logout_inactive' => 'boolean',
            'track_failed_logins' => 'boolean',
            'ip_based_alerts' => 'boolean',
            'email_notifications' => 'boolean',
        ];

        return validator($data, $rules);
    }

    /**
     * Get settings for display
     */
    public function getDisplaySettings()
    {
        return [
            'cleanup_interval' => ucfirst($this->cleanup_interval),
            'retention_days' => $this->retention_days . ' days',
            'refresh_interval' => $this->refresh_interval . ' seconds',
            'records_per_page' => $this->records_per_page . ' records',
            'auto_logout_inactive' => $this->auto_logout_inactive ? 'Enabled' : 'Disabled',
            'track_failed_logins' => $this->track_failed_logins ? 'Enabled' : 'Disabled',
            'ip_based_alerts' => $this->ip_based_alerts ? 'Enabled' : 'Disabled',
            'email_notifications' => $this->email_notifications ? 'Enabled' : 'Disabled',
        ];
    }
}