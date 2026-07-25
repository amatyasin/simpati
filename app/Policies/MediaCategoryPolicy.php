<?php

namespace App\Policies;

use App\Models\MediaCategory;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_media_category');
    }

    public function view(User $user, MediaCategory $mediaCategory): bool
    {
        return $user->can('view_media_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_media_category');
    }

    public function update(User $user, MediaCategory $mediaCategory): bool
    {
        return $user->can('update_media_category');
    }

    public function delete(User $user, MediaCategory $mediaCategory): bool
    {
        return $user->can('delete_media_category');
    }

    public function restore(User $user, MediaCategory $mediaCategory): bool
    {
        return $user->can('restore_media_category');
    }

    public function forceDelete(User $user, MediaCategory $mediaCategory): bool
    {
        return $user->can('force_delete_media_category');
    }
}
