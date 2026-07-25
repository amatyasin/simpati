<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LoginListener;
use App\Listeners\FailedLoginListener;
use App\Listeners\LogoutListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     */
    protected $listen = [
        Login::class => [
            LoginListener::class,
        ],
        Failed::class => [
            FailedLoginListener::class,
        ],
        Logout::class => [
            LogoutListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();
    }
}
