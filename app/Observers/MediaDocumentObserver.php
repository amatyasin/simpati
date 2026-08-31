<?php

namespace App\Observers;

use App\Actions\RecalculateMediaScoreAction;
use App\Models\MediaDocument;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class MediaDocumentObserver
{
    public function __construct(
        protected RecalculateMediaScoreAction $recalculateAction,
        protected NotificationService $notificationService,
    ) {}

    /**
     * Handle the MediaDocument "created" event.
     */
    public function created(MediaDocument $mediaDocument): void
    {
        $this->recalculate($mediaDocument);

        if ($mediaDocument->verification_status?->value === 'pending') {
            try {
                $this->notificationService->notifyAdminsOfNewDocument($mediaDocument);
            } catch (\Throwable $e) {
                Log::error('MediaDocumentObserver: Failed to send new document notification to admins', [
                    'document_id' => $mediaDocument->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the MediaDocument "updated" event.
     */
    public function updated(MediaDocument $mediaDocument): void
    {
        $this->recalculate($mediaDocument);

        // Notify on status changes
        if ($mediaDocument->wasChanged('verification_status')) {
            try {
                $this->notificationService->notifyDocumentStatusChange($mediaDocument);

                if ($mediaDocument->verification_status?->value === 'pending') {
                    $this->notificationService->notifyAdminsOfNewDocument($mediaDocument);
                }
            } catch (\Throwable $e) {
                Log::error('MediaDocumentObserver: Failed to send notification', [
                    'document_id' => $mediaDocument->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the MediaDocument "deleted" event.
     */
    public function deleted(MediaDocument $mediaDocument): void
    {
        $this->recalculate($mediaDocument);
    }

    /**
     * Handle the MediaDocument "restored" event.
     */
    public function restored(MediaDocument $mediaDocument): void
    {
        $this->recalculate($mediaDocument);
    }

    /**
     * Trigger score recalculation on the parent Media record.
     */
    protected function recalculate(MediaDocument $mediaDocument): void
    {
        if ($mediaDocument->mediaPartner) {
            $this->recalculateAction->execute($mediaDocument->mediaPartner);
        }
    }
}
