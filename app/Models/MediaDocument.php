<?php

namespace App\Models;

use App\Enums\DocumentVerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class MediaDocument extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, LogsActivity, SoftDeletes;

    protected $table = 'media_documents';

    protected $fillable = [
        'media_id',
        'document_type_id',
        'document_number',
        'issue_date',
        'expiration_date',
        'verification_status',
        'verification_notes',
        'verifier_id',
        'verified_at',
    ];

    protected $casts = [
        'issue_date'          => 'date',
        'expiration_date'     => 'date',
        'verified_at'         => 'datetime',
        'verification_status' => DocumentVerificationStatus::class,
    ];

    // -------------------------------------------------------------------------
    // Spatie Media Library
    // -------------------------------------------------------------------------

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->useDisk('public')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/pdf',
                'image/jpeg',
                'image/png',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]);

        $this->addMediaCollection('verification-files')
            ->useDisk('public')
            ->singleFile();
    }

    // -------------------------------------------------------------------------
    // Spatie Activity Log
    // -------------------------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('media_document')
            ->dontLogEmptyChanges();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function mediaPartner(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }

    public function verificationLogs(): HasMany
    {
        return $this->hasMany(VerificationLog::class, 'media_document_id');
    }

    // -------------------------------------------------------------------------
    // Eloquent Scopes
    // -------------------------------------------------------------------------

    public function scopePending(Builder $query): Builder
    {
        return $query->where('verification_status', DocumentVerificationStatus::PENDING->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('verification_status', DocumentVerificationStatus::APPROVED->value);
    }

    public function scopeRevision(Builder $query): Builder
    {
        return $query->where('verification_status', DocumentVerificationStatus::REVISION->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('verification_status', DocumentVerificationStatus::REJECTED->value);
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '<', now()->startOfDay());
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->whereNotNull('expiration_date')
            ->where('expiration_date', '>=', now()->startOfDay())
            ->where('expiration_date', '<=', now()->addDays($days));
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->expiration_date !== null
            && ! $this->expiration_date->isPast()
            && $this->expiration_date->diffInDays(now()) <= 30;
    }

    public function getDocumentUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('documents') ?: '';
    }
}
