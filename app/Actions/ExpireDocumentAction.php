<?php

namespace App\Actions;

use App\Enums\DocumentVerificationStatus;
use App\Models\Media;
use App\Models\MediaDocument;
use App\Services\NotificationService;

class ExpireDocumentAction
{
    protected RecalculateMediaScoreAction $recalculateScoreAction;

    public function __construct(RecalculateMediaScoreAction $recalculateScoreAction)
    {
        $this->recalculateScoreAction = $recalculateScoreAction;
    }

    /**
     * Scan and handle expired documents, triggering score updates on affected media partners.
     */
    public function execute(): int
    {
        $expiredDocs = MediaDocument::where('verification_status', DocumentVerificationStatus::APPROVED->value)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->startOfDay())
            ->get();

        $count = 0;
        foreach ($expiredDocs as $doc) {
            $media = $doc->mediaPartner;
            if ($media) {
                $this->recalculateScoreAction->execute($media);
                app(NotificationService::class)->notifyDocumentExpired($doc);
                $count++;
            }
        }

        return $count;
    }
}
