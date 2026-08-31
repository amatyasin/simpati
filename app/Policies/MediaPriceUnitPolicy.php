<?php

namespace App\Policies;

use App\Models\MediaPriceUnit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPriceUnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function view(User $user, MediaPriceUnit $unit): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function update(User $user, MediaPriceUnit $unit): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function delete(User $user, MediaPriceUnit $unit): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function restore(User $user, MediaPriceUnit $unit): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function forceDelete(User $user, MediaPriceUnit $unit): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }
}
