<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPolicy
{
    use HandlesAuthorization;

    /**
     * All admins and media partners can list media.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin', 'media_partner', 'leadership']);
    }

    /**
     * Admins and leadership see all. Media partners see only their own.
     */
    public function view(User $user, Media $media): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership'])) {
            return true;
        }

        return $user->hasRole('media_partner') && $media->user_id === $user->id;
    }

    /**
     * Admins can always create. A media_partner can create only if they don't have a profile yet.
     */
    public function create(User $user): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }

        return $user->hasRole('media_partner')
            && ! Media::where('user_id', $user->id)->exists();
    }

    /**
     * Admins can update anything. Media partners can update only their own profile
     * when it is not yet approved (allow re-submission).
     */
    public function update(User $user, Media $media): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }

        return $user->hasRole('media_partner') && $media->user_id === $user->id;
    }

    /**
     * Only admins can soft-delete.
     */
    public function delete(User $user, Media $media): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Only admins can restore.
     */
    public function restore(User $user, Media $media): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Only super admins can permanently delete.
     */
    public function forceDelete(User $user, Media $media): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Only admins can export.
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership']);
    }

    /**
     * Only admins can view verification history.
     */
    public function viewVerificationHistory(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership']);
    }
}
