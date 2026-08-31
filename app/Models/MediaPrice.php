<?php

namespace App\Models;

use App\Enums\MediaPriceStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class MediaPrice extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'media_prices';

    protected $fillable = [
        'media_id',
        'service_type',
        'price',
        'unit',
        'description',
        'effective_from',
        'effective_until',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'status' => MediaPriceStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (auth()->check() && ! $model->created_by) {
                $model->created_by = auth()->id();
            }
            if (auth()->check() && ! $model->updated_by) {
                $model->updated_by = auth()->id();
            }
        });

        static::updating(function (self $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    // -------------------------------------------------------------------------
    // Spatie Activity Log
    // -------------------------------------------------------------------------

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('media_price')
            ->dontLogEmptyChanges();
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', MediaPriceStatus::DRAFT->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', MediaPriceStatus::PENDING->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', MediaPriceStatus::ACTIVE->value);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', MediaPriceStatus::REJECTED->value);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', MediaPriceStatus::INACTIVE->value);
    }

    public function scopeOwnedByMediaPartner(Builder $query, int $userId): Builder
    {
        return $query->whereHas('media', fn ($q) => $q->where('user_id', $userId));
    }

    public function scopeCurrent(Builder $query, ?Carbon $date = null): Builder
    {
        $targetDate = ($date ?? now())->format('Y-m-d');

        return $query->active()
            ->where('effective_from', '<=', $targetDate)
            ->where(function (Builder $q) use ($targetDate) {
                $q->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $targetDate);
            });
    }

    // -------------------------------------------------------------------------
    // Workflow Helpers
    // -------------------------------------------------------------------------

    public function submitForApproval(): bool
    {
        if (! in_array($this->status?->value, [MediaPriceStatus::DRAFT->value, MediaPriceStatus::REJECTED->value], true)) {
            return false;
        }

        return $this->update([
            'status' => MediaPriceStatus::PENDING->value,
            'submitted_at' => now(),
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);
    }

    public function approve(int $approvedById): bool
    {
        if (! in_array($this->status?->value, [MediaPriceStatus::PENDING->value, MediaPriceStatus::DRAFT->value], true)) {
            return false;
        }

        // Deactivate previous active price for same media + service_type if effective
        self::where('media_id', $this->media_id)
            ->where('service_type', $this->service_type)
            ->where('status', MediaPriceStatus::ACTIVE->value)
            ->where('id', '!=', $this->id)
            ->each(function (self $oldPrice) {
                $oldPrice->update([
                    'status' => MediaPriceStatus::INACTIVE->value,
                    'effective_until' => $this->effective_from->copy()->subDay(),
                ]);
            });

        return $this->update([
            'status' => MediaPriceStatus::ACTIVE->value,
            'approved_at' => now(),
            'approved_by' => $approvedById,
            'rejection_reason' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);
    }

    public function reject(int $rejectedById, string $reason): bool
    {
        if (! in_array($this->status?->value, [MediaPriceStatus::PENDING->value, MediaPriceStatus::DRAFT->value], true)) {
            return false;
        }

        if (trim($reason) === '') {
            return false;
        }

        return $this->update([
            'status' => MediaPriceStatus::REJECTED->value,
            'rejected_at' => now(),
            'rejected_by' => $rejectedById,
            'rejection_reason' => trim($reason),
        ]);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format((float) $this->price, 0, ',', '.');
    }
}
