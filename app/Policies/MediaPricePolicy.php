<?php

namespace App\Policies;

use App\Enums\MediaPriceStatus;
use App\Models\MediaPrice;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaPricePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MediaPrice $mediaPrice): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership'])) {
            return true;
        }

        return $user->hasRole('media_partner') && $mediaPrice->media?->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin', 'media_partner']);
    }

    public function update(User $user, MediaPrice $mediaPrice): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }

        if ($user->hasRole('media_partner') && $mediaPrice->media?->user_id === $user->id) {
            return in_array($mediaPrice->status?->value, [
                MediaPriceStatus::DRAFT->value,
                MediaPriceStatus::PENDING->value,
                MediaPriceStatus::REJECTED->value,
            ], true);
        }

        return false;
    }

    public function delete(User $user, MediaPrice $mediaPrice): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }

        if ($user->hasRole('media_partner') && $mediaPrice->media?->user_id === $user->id) {
            return $mediaPrice->status?->value === MediaPriceStatus::DRAFT->value;
        }

        return false;
    }

    public function submit(User $user, MediaPrice $mediaPrice): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }

        if ($user->hasRole('media_partner') && $mediaPrice->media?->user_id === $user->id) {
            return in_array($mediaPrice->status?->value, [
                MediaPriceStatus::DRAFT->value,
                MediaPriceStatus::REJECTED->value,
            ], true);
        }

        return false;
    }

    public function approve(User $user, MediaPrice $mediaPrice): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function reject(User $user, MediaPrice $mediaPrice): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function restore(User $user, MediaPrice $mediaPrice): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    public function forceDelete(User $user, MediaPrice $mediaPrice): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }
}
