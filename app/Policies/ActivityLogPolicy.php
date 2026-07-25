<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function view(User $user, Activity $activity): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Activity $activity): bool
    {
        return false;
    }

    public function delete(User $user, Activity $activity): bool
    {
        return false;
    }

    public function restore(User $user, Activity $activity): bool
    {
        return false;
    }

    public function forceDelete(User $user, Activity $activity): bool
    {
        return false;
    }
}
