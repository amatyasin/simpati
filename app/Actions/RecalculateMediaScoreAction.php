<?php

namespace App\Actions;

use App\Enums\DocumentVerificationStatus;
use App\Enums\MediaVerificationStatus;
use App\Models\DocumentType;
use App\Models\Media;
use App\Services\MediaScoreService;

class RecalculateMediaScoreAction
{
    public function __construct(
        protected MediaScoreService $scoreService
    ) {}

    /**
     * Recalculate and persist completeness, verification score, and status
     * for the given Media record.
     */
    public function execute(Media $media): void
    {
        $oldCompleteness = $media->completeness_percentage;

        $completeness = $this->scoreService->calculateCompleteness($media);
        $score        = $this->scoreService->calculateVerificationScore($media);
        $status       = $this->resolveStatus($media);

        $media->updateQuietly([
            'completeness_percentage' => $completeness,
            'verification_score'      => $score,
            'verification_status'     => $status,
        ]);

        if ($completeness < 100 && $oldCompleteness == 100) {
            app(\App\Services\NotificationService::class)->notifyProfileIncomplete($media);
        }
    }

    /**
     * Derive the Media's verification_status from its documents' collective statuses.
     */
    protected function resolveStatus(Media $media): MediaVerificationStatus
    {
        $totalRequired = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->count();

        $uploadedCount = $media->mediaDocuments()->count();

        if ($uploadedCount === 0) {
            return MediaVerificationStatus::DRAFT;
        }

        $hasPending = $media->mediaDocuments()
            ->where('verification_status', DocumentVerificationStatus::PENDING->value)
            ->exists();

        $hasRevision = $media->mediaDocuments()
            ->where('verification_status', DocumentVerificationStatus::REVISION->value)
            ->exists();

        $hasRejected = $media->mediaDocuments()
            ->where('verification_status', DocumentVerificationStatus::REJECTED->value)
            ->exists();

        // Count approved required document types (not expired)
        $approvedRequiredCount = $media->mediaDocuments()
            ->where('verification_status', DocumentVerificationStatus::APPROVED->value)
            ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', now()->startOfDay()))
            ->whereHas('documentType', fn ($q) => $q->where('is_active', true)->where('is_required', true))
            ->distinct('document_type_id')
            ->count('document_type_id');

        if (! $hasPending && ! $hasRevision && ! $hasRejected && $approvedRequiredCount >= $totalRequired) {
            return MediaVerificationStatus::APPROVED;
        }

        if ($hasRevision) {
            return MediaVerificationStatus::REVISION;
        }

        if ($hasRejected && ! $hasPending) {
            return MediaVerificationStatus::REJECTED;
        }

        return MediaVerificationStatus::PENDING;
    }
}
