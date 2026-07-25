<?php

namespace App\Models;

use App\Enums\DocumentVerificationStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationLog extends Model
{
    use HasFactory;

    protected $table = 'verification_logs';

    protected $fillable = [
        'media_document_id',
        'user_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => DocumentVerificationStatus::class,
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function mediaDocument(): BelongsTo
    {
        return $this->belongsTo(MediaDocument::class, 'media_document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /**
     * Human-readable status label.
     */
    protected function statusLabel(): Attribute
    {
        return Attribute::get(function () {
            return match ($this->status) {
                DocumentVerificationStatus::APPROVED => 'Disetujui',
                DocumentVerificationStatus::REJECTED => 'Ditolak',
                DocumentVerificationStatus::REVISION => 'Butuh Revisi',
                DocumentVerificationStatus::PENDING  => 'Menunggu Verifikasi',
                default                              => ucfirst($this->status?->value ?? ''),
            };
        });
    }
}
