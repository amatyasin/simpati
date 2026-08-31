<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaServiceType extends Model
{
    use HasFactory;

    protected $table = 'media_service_types';

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
        $defaultTypes = [
            'Berita/Artikel',
            'Banner',
            'Video',
            'Sosial Media',
            'Publikasi Lainnya',
            'Radio/Audio',
            'Infografis',
            'Press Release',
        ];

        foreach ($defaultTypes as $name) {
            self::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name), 'is_active' => true]
            );
        }
    }
}
