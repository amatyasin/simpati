<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use App\Enums\LoginStatus;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Request;

class FailedLoginListener
{
    /**
     * Handle the failed login event.
     */
    public function handle(Failed $event): void
    {
        $user = $event->user;
        if ($user) {
            // Increment failed login count
            $user->increment('failed_login_count');
        }

        // Record login activity (user may be null if email not found)
        LoginActivity::create([
            'user_id' => $user ? $user->id : null,
            'login_at' => now(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'status' => LoginStatus::Failed->value,
        ]);
    }
}
