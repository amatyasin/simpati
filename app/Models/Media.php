<?php

namespace App\Models;

use App\Enums\MediaVerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Media extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $table = 'media';

    protected $fillable = [
        'user_id',
        'media_category_id',
        'company_name',
        'brand_name',
        'website',
        'address',
        'phone',
        'email',
        'director',
        'chief_editor',
        'description',
        'verification_status',
        'verification_score',
        'completeness_percentage',
    ];

    protected $casts = [
        'verification_score' => 'integer',
        'completeness_percentage' => 'integer',
        'verification_status' => MediaVerificationStatus::class,
    ];

    // -------------------------------------------------------------------------
    // Spatie Media Library
    // -------------------------------------------------------------------------

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logos')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml']);

        $this->addMediaCollection('merged_documents')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf']);
    }

    // -------------------------------------------------------------------------
    // Spatie Activity Log
    // -------------------------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('media_partner')
            ->dontLogEmptyChanges();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediaCategory(): BelongsTo
    {
        return $this->belongsTo(MediaCategory::class);
    }

    public function mediaDocuments(): HasMany
    {
        return $this->hasMany(MediaDocument::class, 'media_id');
    }

    public function approvedDocuments(): HasMany
    {
        return $this->hasMany(MediaDocument::class, 'media_id')
            ->where('verification_status', 'approved');
    }

    public function pendingDocuments(): HasMany
    {
        return $this->hasMany(MediaDocument::class, 'media_id')
            ->where('verification_status', 'pending');
    }

    public function rejectedDocuments(): HasMany
    {
        return $this->hasMany(MediaDocument::class, 'media_id')
            ->where('verification_status', 'rejected');
    }

    // -------------------------------------------------------------------------
    // Eloquent Scopes
    // -------------------------------------------------------------------------

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('verification_status', MediaVerificationStatus::DRAFT->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('verification_status', MediaVerificationStatus::PENDING->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('verification_status', MediaVerificationStatus::APPROVED->value);
    }

    public function scopeRevision(Builder $query): Builder
    {
        return $query->where('verification_status', MediaVerificationStatus::REVISION->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('verification_status', MediaVerificationStatus::REJECTED->value);
    }

    public function scopeOwnedBy(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeCompleteAbove(Builder $query, int $threshold): Builder
    {
        return $query->where('completeness_percentage', '>=', $threshold);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Get the logo URL, falling back to UI Avatars if no logo is uploaded.
     */
    public function getLogoUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('logos')
            ?: 'https://ui-avatars.com/api/?name='.urlencode($this->brand_name).'&color=7F9CF5&background=EBF4FF&size=128';
    }

    /**
     * Indicates whether the media profile is fully verified.
     */
    public function getIsFullyVerifiedAttribute(): bool
    {
        return $this->verification_status === MediaVerificationStatus::APPROVED;
    }

    /**
     * Indicates whether any documents are expiring within 30 days.
     */
    public function getHasExpiringSoonDocumentsAttribute(): bool
    {
        return $this->mediaDocuments()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '>=', now()->startOfDay())
            ->where('expiration_date', '<=', now()->addDays(30))
            ->exists();
    }

    /**
     * Indicates whether any documents are already expired.
     */
    public function getHasExpiredDocumentsAttribute(): bool
    {
        return $this->mediaDocuments()
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->startOfDay())
            ->exists();
    }

    /**
     * Get URL of generated merged PDF document.
     */
    public function getMergedPdfUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('merged_documents') ?: null;
    }

    /**
     * Get absolute local path of generated merged PDF document.
     */
    public function getMergedPdfPathAttribute(): ?string
    {
        return $this->getFirstMediaPath('merged_documents') ?: null;
    }

    /**
     * Get count of uploaded available documents for this media profile.
     */
    public function getAvailableDocumentsCountAttribute(): int
    {
        return $this->mediaDocuments()->count();
    }

    /**
     * Get total count of required active document types in the system.
     */
    public function getTotalRequiredDocumentsCountAttribute(): int
    {
        return DocumentType::where('is_active', true)->where('is_required', true)->count();
    }
}
