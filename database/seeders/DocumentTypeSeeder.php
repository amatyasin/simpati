<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Akte Pendirian Perusahaan & Pengesahan Kemenkumham',
                'code' => 'AKTE',
                'description' => 'Akte pendirian perusahaan pers beserta surat keputusan pengesahan dari Kemenkumham.',
                'allowed_extensions' => ['pdf'],
                'max_file_size_mb' => 10,
                'icon' => 'heroicon-o-document-text',
                'weight' => 25,
                'is_required' => true,
                'validity_days' => null, // Berlaku selamanya
                'is_active' => true,
            ],
            [
                'name' => 'NPWP Perusahaan',
                'code' => 'NPWP',
                'description' => 'Nomor Pokok Wajib Pajak atas nama badan hukum/perusahaan pers.',
                'allowed_extensions' => ['pdf', 'jpg', 'png'],
                'max_file_size_mb' => 2,
                'icon' => 'heroicon-o-credit-card',
                'weight' => 15,
                'is_required' => true,
                'validity_days' => null, // Berlaku selamanya
                'is_active' => true,
            ],
            [
                'name' => 'Nomor Induk Berusaha (NIB) / SIUP',
                'code' => 'NIB',
                'description' => 'Tanda daftar bisnis aktif dari OSS (Online Single Submission).',
                'allowed_extensions' => ['pdf'],
                'max_file_size_mb' => 5,
                'icon' => 'heroicon-o-briefcase',
                'weight' => 20,
                'is_required' => true,
                'validity_days' => null,
                'is_active' => true,
            ],
            [
                'name' => 'Surat Tugas & Kartu Pers Wartawan / Redaktur',
                'code' => 'KARTUPERS',
                'description' => 'Surat tugas liputan di wilayah Samarinda beserta kartu pers wartawan.',
                'allowed_extensions' => ['pdf'],
                'max_file_size_mb' => 5,
                'icon' => 'heroicon-o-identification',
                'weight' => 15,
                'is_required' => true,
                'validity_days' => 365, // Berakhir dalam 1 tahun
                'is_active' => true,
            ],
            [
                'name' => 'Sertifikat Uji Kompetensi Wartawan (UKW) Pemimpin Redaksi',
                'code' => 'UKW',
                'description' => 'Sertifikat kelayakan dan kompetensi wartawan utama dari Dewan Pers.',
                'allowed_extensions' => ['pdf'],
                'max_file_size_mb' => 5,
                'icon' => 'heroicon-o-academic-cap',
                'weight' => 15,
                'is_required' => false, // Pendukung / opsional
                'validity_days' => 1825, // 5 tahun
                'is_active' => true,
            ],
            [
                'name' => 'Bukti SPT Tahunan Terakhir',
                'code' => 'SPT',
                'description' => 'Surat pemberitahuan tahunan perpajakan perusahaan tahun terakhir.',
                'allowed_extensions' => ['pdf'],
                'max_file_size_mb' => 5,
                'icon' => 'heroicon-o-document-duplicate',
                'weight' => 10,
                'is_required' => true,
                'validity_days' => 365, // 1 tahun
                'is_active' => true,
            ],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
