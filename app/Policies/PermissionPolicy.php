<?php

namespace App\Policies;

use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PermissionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any permissions.
     */
    public function viewAny(User $user): bool
    {
        // Only super admin and Diskominfo admin can list permissions
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Determine whether the user can view a specific permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        // Same as viewAny
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    // Read‑only UI – disallow all modifications
    public function create(User $user): bool { return false; }
    public function update(User $user, Permission $permission): bool { return false; }
    public function delete(User $user, Permission $permission): bool { return false; }
    public function restore(User $user, Permission $permission): bool { return false; }
    public function forceDelete(User $user, Permission $permission): bool { return false; }
}
