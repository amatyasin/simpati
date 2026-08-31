<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        // Super admin and Diskominfo admin can list users
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        // Everyone can view (e.g., for profile page)
        return true;
    }

    /**
     * Determine whether the user can create new users.
     */
    public function create(User $user): bool
    {
        // Only super admin and Diskominfo admin can create users
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Determine whether the user can update a user.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }
        // Media partners can edit their own profile
        if ($user->hasRole('media_partner') && $user->id === $model->id) {
            return true;
        }

        // Leadership (pimpinan) can view only, not update
        return false;
    }

    /**
     * Determine whether the user can delete a user.
     */
    public function delete(User $user, User $model): bool
    {
        // Only super admin and Diskominfo admin can delete users
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }
}
