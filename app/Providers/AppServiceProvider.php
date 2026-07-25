<?php

namespace App\Providers;

use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\User;
use App\Models\LoginActivity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Policies\DocumentTypePolicy;
use App\Policies\MediaCategoryPolicy;
use App\Policies\MediaPolicy;
use App\Policies\MediaDocumentPolicy;
use App\Policies\ActivityLogPolicy;
use App\Policies\UserPolicy;
use App\Policies\RolePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\LoginActivityPolicy;
use App\Observers\MediaDocumentObserver;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        MediaCategory::class => MediaCategoryPolicy::class,
        DocumentType::class => DocumentTypePolicy::class,
        Media::class => MediaPolicy::class,
        MediaDocument::class => MediaDocumentPolicy::class,
        \Spatie\Activitylog\Models\Activity::class => ActivityLogPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        LoginActivity::class => LoginActivityPolicy::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Register Observers
        \App\Models\Media::observe(\App\Observers\MediaObserver::class);
        \App\Models\MediaDocument::observe(MediaDocumentObserver::class);

        // super_admin bypasses all policy checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
