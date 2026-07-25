<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon\Carbon;

class LoginActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'login_at',
        'logout_at',
        'ip_address',
        'browser',
        'platform',
        'user_agent',
        'status',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
