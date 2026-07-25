<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use App\Enums\LoginStatus;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

class LoginListener
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        // Update user last login fields
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => Request::ip(),
            'failed_login_count' => 0,
        ]);

        // Create login activity record
        LoginActivity::create([
            'user_id' => $user->id,
            'login_at' => now(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'status' => LoginStatus::Successful->value,
        ]);
    }
}
