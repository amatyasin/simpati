<?php

namespace App\Policies;

use App\Models\MediaDocument;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MediaDocumentPolicy
{
    use HandlesAuthorization;

    /**
     * All authenticated roles can list documents.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin', 'media_partner', 'leadership']);
    }

    /**
     * Admins and leadership see all. Media partners see only their own.
     */
    public function view(User $user, MediaDocument $document): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin', 'leadership'])) {
            return true;
        }

        return $user->hasRole('media_partner')
            && $document->mediaPartner?->user_id === $user->id;
    }

    /**
     * Admins and media partners can upload documents.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin', 'media_partner']);
    }

    /**
     * Admins can update any. Media partners can only update their own
     * documents when in pending/revision status.
     */
    public function update(User $user, MediaDocument $document): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }

        if ($user->hasRole('media_partner') && $document->mediaPartner?->user_id === $user->id) {
            // Media partners can only re-upload/edit documents in pending or revision
            return in_array($document->verification_status?->value, ['pending', 'revision'], true);
        }

        return false;
    }

    /**
     * Admins can soft-delete. Media partners can delete their own pending/revision docs.
     */
    public function delete(User $user, MediaDocument $document): bool
    {
        if ($user->hasAnyRole(['super_admin', 'diskominfo_admin'])) {
            return true;
        }

        return $user->hasRole('media_partner')
            && $document->mediaPartner?->user_id === $user->id
            && in_array($document->verification_status?->value, ['pending', 'revision'], true);
    }

    /**
     * Only admins can restore.
     */
    public function restore(User $user, MediaDocument $document): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Only super admin can permanently delete.
     */
    public function forceDelete(User $user, MediaDocument $document): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Only admins can verify/reject/request revision.
     */
    public function verify(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'diskominfo_admin']);
    }

    /**
     * Anyone who can view can also download the document file.
     */
    public function download(User $user, MediaDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
