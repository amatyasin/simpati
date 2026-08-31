<?php

namespace App\Policies;

use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class LoginActivityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any login activities.
     */
    public function viewAny(User $user): bool
    {
        // Super admin and Diskominfo admin can view logs
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Determine whether the user can view a specific login activity.
     */
    public function view(User $user, LoginActivity $activity): bool
    {
        // Same rule as viewAny
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    // No create, update, delete – read‑only UI
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LoginActivity $activity): bool
    {
        return false;
    }

    public function delete(User $user, LoginActivity $activity): bool
    {
        return false;
    }

    public function restore(User $user, LoginActivity $activity): bool
    {
        return false;
    }

    public function forceDelete(User $user, LoginActivity $activity): bool
    {
        return false;
    }
}
