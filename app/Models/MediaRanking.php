<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaRanking extends Model
{
    protected $table = 'view_media_ranking';

    public $incrementing = false;

    public $timestamps = false;

    // Read-only model safety
    protected $fillable = [];

    protected $guarded = ['*'];
}
