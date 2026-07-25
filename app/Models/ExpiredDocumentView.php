<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpiredDocumentView extends Model
{
    protected $table = 'view_expired_documents';

    public $incrementing = false;

    public $timestamps = false;

    // Read-only model safety
    protected $fillable = [];

    protected $guarded = ['*'];

    protected $casts = [
        'expiration_date' => 'date',
    ];
}
