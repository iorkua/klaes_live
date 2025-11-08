<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use App\Models\UserActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogUserLogout
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \Illuminate\Auth\Events\Logout  $event
     * @return void
     */
    public function handle(Logout $event)
    {
        try {
            if ($event->user) {
                // Mark current session as logged out
                UserActivityLog::where('user_id', $event->user->id)
                    ->where('is_online', true)
                    ->update([
                        'is_online' => false,
                        'logout_time' => now()
                    ]);

                // Log the logout activity
                UserActivityLog::logActivity($event->user->id, 'logout', [
                    'activity_description' => 'User logged out successfully'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to log user logout: ' . $e->getMessage());
        }
    }
}