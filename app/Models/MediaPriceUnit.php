<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaPriceUnit extends Model
{
    use HasFactory;

    protected $table = 'media_price_units';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function seedDefaults(): void
    {
        $defaultUnits = [
            'Per Publikasi',
            'Per Hari',
            'Per Tayang',
            'Per Artikel',
            'Per Konten',
            'Per Bulan',
            'Per Post',
            'Per Slot',
        ];

        foreach ($defaultUnits as $name) {
            self::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name), 'is_active' => true]
            );
        }
    }
}
