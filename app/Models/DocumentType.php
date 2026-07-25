<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'allowed_extensions',
        'max_file_size_mb',
        'icon',
        'weight',
        'is_required',
        'validity_days',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_extensions' => 'array',
        'max_file_size_mb' => 'integer',
        'weight' => 'integer',
        'is_required' => 'boolean',
        'validity_days' => 'integer',
    ];

    public function mediaDocuments(): HasMany
    {
        return $this->hasMany(MediaDocument::class);
    }
}
