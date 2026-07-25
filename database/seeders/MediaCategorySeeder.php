<?php

namespace Database\Seeders;

use App\Models\MediaCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MediaCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Siaran Pers',
                'description' => 'Siaran pers resmi untuk publik dan media massa.',
                'color' => '#3B82F6',
                'icon' => 'heroicon-o-megaphone',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Laporan Tahunan',
                'description' => 'Laporan kinerja dan keuangan tahunan.',
                'color' => '#10B981',
                'icon' => 'heroicon-o-document-chart-bar',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Infografis',
                'description' => 'Penyajian data dan informasi dalam bentuk visual.',
                'color' => '#F59E0B',
                'icon' => 'heroicon-o-chart-pie',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Foto Kegiatan',
                'description' => 'Dokumentasi foto kegiatan resmi.',
                'color' => '#8B5CF6',
                'icon' => 'heroicon-o-camera',
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Video Profil',
                'description' => 'Video pengenalan dan profil lembaga.',
                'color' => '#EF4444',
                'icon' => 'heroicon-o-video-camera',
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'name' => 'Regulasi Digital',
                'description' => 'Peraturan dan kebijakan di bidang digital.',
                'color' => '#06B6D4',
                'icon' => 'heroicon-o-shield-check',
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'name' => 'Data Statistik',
                'description' => 'Publikasi data statistik nasional.',
                'color' => '#84CC16',
                'icon' => 'heroicon-o-table-cells',
                'sort_order' => 7,
                'is_active' => true,
            ],
            [
                'name' => 'Panduan Kesehatan',
                'description' => 'Panduan dan protokol kesehatan resmi.',
                'color' => '#F97316',
                'icon' => 'heroicon-o-heart',
                'sort_order' => 8,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            $category['slug'] = Str::slug($category['name']);
            MediaCategory::firstOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
