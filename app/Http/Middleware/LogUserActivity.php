<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserActivityLog;
use Illuminate\Support\Facades\Auth;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log for authenticated users
        if (Auth::check()) {
            $this->logUserActivity($request);
        }

        return $response;
    }

    /**
     * Log user activity
     */
    private function logUserActivity(Request $request)
    {
        try {
            $user = Auth::user();
            $sessionId = session()->getId();
            
            // Check if there's already an active session for this user
            $existingLog = UserActivityLog::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->where('is_online', true)
                ->first();

            if (!$existingLog) {
                // Create new activity log entry
                UserActivityLog::logActivity($user->id, 'activity', [
                    'activity_description' => 'User activity: ' . $request->method() . ' ' . $request->path()
                ]);
            } else {
                // Update last activity time
                $existingLog->touch();
            }

            // Mark old sessions as offline (sessions older than 30 minutes without activity)
            UserActivityLog::where('user_id', $user->id)
                ->where('is_online', true)
                ->where('updated_at', '<', now()->subMinutes(30))
                ->update([
                    'is_online' => false,
                    'logout_time' => now()
                ]);

        } catch (\Exception $e) {
            // Log the error but don't break the application
            \Log::error('Failed to log user activity: ' . $e->getMessage());
        }
    }
}