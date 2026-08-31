<?php

namespace App\Policies;

use App\Models\MediaServiceType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaServiceTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function view(User $user, MediaServiceType $serviceType): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function update(User $user, MediaServiceType $serviceType): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function delete(User $user, MediaServiceType $serviceType): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function restore(User $user, MediaServiceType $serviceType): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function forceDelete(User $user, MediaServiceType $serviceType): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }
}
