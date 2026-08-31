<?php

namespace App\Actions;

use App\Enums\DocumentVerificationStatus;
use App\Models\MediaDocument;
use App\Models\VerificationLog;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class RejectDocumentAction
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Reject the document and log the action.
     */
    public function execute(MediaDocument $document, int $verifierId, string $notes): void
    {
        DB::transaction(function () use ($document, $verifierId, $notes) {
            $document->update([
                'verification_status' => DocumentVerificationStatus::REJECTED,
                'verifier_id' => $verifierId,
                'verified_at' => now(),
                'verification_notes' => $notes,
            ]);

            VerificationLog::create([
                'media_document_id' => $document->id,
                'user_id' => $verifierId,
                'status' => DocumentVerificationStatus::REJECTED->value,
                'notes' => $notes,
            ]);
        });
    }
}
