<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;

class LogoutListener
{
    /**
     * Handle the logout event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;
        if (!$user) {
            return;
        }

        // Find the latest login activity without a logout timestamp and close it
        $activity = LoginActivity::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->orderByDesc('login_at')
            ->first();

        if ($activity) {
            $activity->update([
                'logout_at' => now(),
            ]);
        }
    }
}
