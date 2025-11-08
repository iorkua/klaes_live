<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Models\UserActivityLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogUserLogin
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
     * @param  \Illuminate\Auth\Events\Login  $event
     * @return void
     */
    public function handle(Login $event)
    {
        try {
            // Log the login activity
            UserActivityLog::logActivity($event->user->id, 'login', [
                'activity_description' => 'User logged in successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log user login: ' . $e->getMessage());
        }
    }
}