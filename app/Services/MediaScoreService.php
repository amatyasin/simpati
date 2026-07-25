<?php

namespace App\Services;

use App\Models\Media;
use App\Models\DocumentType;
use App\Enums\DocumentVerificationStatus;
use Illuminate\Support\Collection;

class MediaScoreService
{
    /**
     * Calculate completeness percentage.
     * Based on the weighted sum of uploaded required document types.
     */
    public function calculateCompleteness(Media $media): int
    {
        $requiredDocs = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->get();

        if ($requiredDocs->isEmpty()) {
            return 100;
        }

        $totalWeight = $requiredDocs->sum('weight');
        if ($totalWeight === 0) {
            return 100;
        }

        $uploadedDocTypeIds = $media->mediaDocuments()
            ->pluck('document_type_id')
            ->unique()
            ->toArray();

        $uploadedWeight = $requiredDocs
            ->filter(fn ($docType) => in_array($docType->id, $uploadedDocTypeIds))
            ->sum('weight');

        return (int) min(100, round(($uploadedWeight / $totalWeight) * 100));
    }

    /**
     * Calculate verification score.
     * Based on the weighted sum of approved, non-expired required document types.
     */
    public function calculateVerificationScore(Media $media): int
    {
        $requiredDocs = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->get();

        if ($requiredDocs->isEmpty()) {
            return 100;
        }

        $totalWeight = $requiredDocs->sum('weight');
        if ($totalWeight === 0) {
            return 100;
        }

        $approvedDocTypeIds = $media->mediaDocuments()
            ->where('verification_status', DocumentVerificationStatus::APPROVED->value)
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', now()->startOfDay());
            })
            ->pluck('document_type_id')
            ->unique()
            ->toArray();

        $approvedWeight = $requiredDocs
            ->filter(fn ($docType) => in_array($docType->id, $approvedDocTypeIds))
            ->sum('weight');

        return (int) min(100, round(($approvedWeight / $totalWeight) * 100));
    }

    /**
     * Calculate ranking score.
     * Formula: (VerificationScore × 0.8) + (Completeness × 0.2)
     */
    public function calculateRankingScore(Media $media): float
    {
        return ($this->calculateVerificationScore($media) * 0.8)
            + ($this->calculateCompleteness($media) * 0.2);
    }

    /**
     * Return documents that are past their expiration date.
     */
    public function getExpiredDocuments(Media $media): Collection
    {
        return $media->mediaDocuments()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->startOfDay())
            ->with('documentType')
            ->get();
    }

    /**
     * Return documents expiring within the given number of days.
     */
    public function getExpiringSoonDocuments(Media $media, int $days = 30): Collection
    {
        return $media->mediaDocuments()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>=', now()->startOfDay())
            ->where('expiration_date', '<=', now()->addDays($days))
            ->with('documentType')
            ->get();
    }

    /**
     * Return required DocumentTypes that have not been uploaded yet.
     */
    public function getMissingDocuments(Media $media): Collection
    {
        $requiredDocIds = DocumentType::where('is_active', true)
            ->where('is_required', true)
            ->pluck('id')
            ->toArray();

        $uploadedDocIds = $media->mediaDocuments()
            ->pluck('document_type_id')
            ->unique()
            ->toArray();

        $missingDocIds = array_diff($requiredDocIds, $uploadedDocIds);

        return DocumentType::whereIn('id', $missingDocIds)->get();
    }
}
