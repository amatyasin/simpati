<?php

namespace App\Policies;

use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        // Only super admin and Diskominfo admin can list roles
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Determine whether the user can view a specific role.
     */
    public function view(User $user, Role $role): bool
    {
        // Same as viewAny
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    // No create, update, delete actions – UI is read‑only
    public function create(User $user): bool { return false; }
    public function update(User $user, Role $role): bool { return false; }
    public function delete(User $user, Role $role): bool { return false; }
    public function restore(User $user, Role $role): bool { return false; }
    public function forceDelete(User $user, Role $role): bool { return false; }
}
