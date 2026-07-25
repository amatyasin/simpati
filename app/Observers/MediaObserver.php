<?php

namespace App\Observers;

use App\Models\Media;
use Illuminate\Support\Facades\Log;

class MediaObserver
{
    /**
     * Handle the Media "creating" event.
     * Ensure default verification_status is applied.
     */
    public function creating(Media $media): void
    {
        if (empty($media->verification_status)) {
            $media->verification_status = \App\Enums\MediaVerificationStatus::DRAFT;
        }
    }

    /**
     * Handle the Media "deleting" event.
     * Soft-delete all associated documents.
     */
    public function deleting(Media $media): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($media) {
            $media->mediaDocuments()->each(function ($doc) {
                $doc->delete();
            });
        });
    }

    /**
     * Handle the Media "restored" event.
     * Restore all associated soft-deleted documents.
     */
    public function restored(Media $media): void
    {
        $media->mediaDocuments()->onlyTrashed()->each(function ($doc) {
            $doc->restore();
        });
    }

    /**
     * Handle the Media "force deleted" event.
     * Permanently delete all associated documents and media files.
     */
    public function forceDeleted(Media $media): void
    {
        try {
            $media->mediaDocuments()->withTrashed()->each(function ($doc) {
                $doc->clearMediaCollection('documents');
                $doc->clearMediaCollection('verification-files');
                $doc->forceDelete();
            });
            $media->clearMediaCollection('logos');
        } catch (\Throwable $e) {
            Log::error('MediaObserver: Error during forceDeleted cleanup', [
                'media_id' => $media->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
