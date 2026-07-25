<?php

namespace Database\Seeders;

use App\Actions\RecalculateMediaScoreAction;
use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\User;
use App\Models\VerificationLog;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $categories = MediaCategory::all();
        $documentTypes = DocumentType::all();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'diskominfo_admin'))->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))->first();

        if ($categories->isEmpty() || $documentTypes->isEmpty()) {
            return;
        }

        $partnersData = [
            [
                'company_name' => 'PT Samarinda Media Promosindo',
                'brand_name' => 'Samarinda Pos',
                'website' => 'https://samarindapos.co.id',
                'email' => 'redaksi@samarindapos.co.id',
                'phone' => '0541-735222',
                'director' => 'H. Abdurrahman, M.Si',
                'chief_editor' => 'Syahruji, S.Sos',
            ],
            [
                'company_name' => 'PT Kaltim Media Utama',
                'brand_name' => 'Kaltim Post',
                'website' => 'https://kaltimpost.id',
                'email' => 'info@kaltimpost.id',
                'phone' => '0541-743555',
                'director' => 'Rusdiansyah',
                'chief_editor' => 'Imron Rosadi',
            ],
            [
                'company_name' => 'PT Mahakam Radio Swara',
                'brand_name' => 'Mahakam FM',
                'website' => 'https://mahakamfm.com',
                'email' => 'marketing@mahakamfm.com',
                'phone' => '0541-765444',
                'director' => 'Hj. Farida',
                'chief_editor' => 'Dwi Lestari',
            ],
            [
                'company_name' => 'PT Samarinda Televisi',
                'brand_name' => 'Samarinda TV',
                'website' => 'https://samarindatv.co.id',
                'email' => 'contact@samarindatv.co.id',
                'phone' => '0541-778999',
                'director' => 'Budi Santoso',
                'chief_editor' => 'Hendra Wijaya',
            ],
            [
                'company_name' => 'PT Media Siber Mahakam',
                'brand_name' => 'Mahakam News',
                'website' => 'https://mahakamnews.id',
                'email' => 'redaksi@mahakamnews.id',
                'phone' => '0812-5555-4444',
                'director' => 'Rahmat Hidayat',
                'chief_editor' => 'Andi Pratama',
            ],
            [
                'company_name' => 'PT Borneo Media Mandiri',
                'brand_name' => 'Borneo Daily',
                'website' => 'https://borneodaily.co.id',
                'email' => 'hello@borneodaily.co.id',
                'phone' => '0811-3333-2222',
                'director' => 'Sri Wahyuni',
                'chief_editor' => 'Ahmad Fauzi',
            ],
            [
                'company_name' => 'PT Swara Samarinda Indah',
                'brand_name' => 'RRI Samarinda',
                'website' => 'https://rri.co.id/samarinda',
                'email' => 'rri.samarinda@rri.co.id',
                'phone' => '0541-741122',
                'director' => 'Drs. H. Suharman',
                'chief_editor' => 'Endang Sulastri',
            ],
            [
                'company_name' => 'PT Media Khatulistiwa',
                'brand_name' => 'Khatulistiwa Online',
                'website' => 'https://khatulistiwa.online',
                'email' => 'news@khatulistiwa.online',
                'phone' => '0813-9999-8888',
                'director' => 'M. Yusuf',
                'chief_editor' => 'Riza Pahlevi',
            ],
            [
                'company_name' => 'PT Etam Portal Mediatama',
                'brand_name' => 'Portal Etam',
                'website' => 'https://portaletam.com',
                'email' => 'admin@portaletam.com',
                'phone' => '0821-4444-5555',
                'director' => 'Dewi Lestari',
                'chief_editor' => 'Aris Munandar',
            ],
            [
                'company_name' => 'PT Suara Tepian Samarinda',
                'brand_name' => 'Tepian Pos',
                'website' => 'https://tepianpos.id',
                'email' => 'contact@tepianpos.id',
                'phone' => '0541-776655',
                'director' => 'Edy Suwito',
                'chief_editor' => 'Surya Dinata',
            ],
        ];

        foreach ($partnersData as $index => $data) {
            $email = 'partner'.($index + 1).'@simpati.id';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $data['brand_name'].' Rep',
                    'email' => $email,
                    'password' => bcrypt('password'),
                ]
            );
            $user->assignRole('media_partner');

            $category = $categories->get($index % $categories->count());
            $media = Media::firstOrCreate(
                ['company_name' => $data['company_name']],
                [
                    'user_id' => $user->id,
                    'media_category_id' => $category->id,
                    'brand_name' => $data['brand_name'],
                    'website' => $data['website'],
                    'address' => 'Jl. Tepian Mahakam No. '.($index + 12).', Samarinda, Kalimantan Timur',
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'director' => $data['director'],
                    'chief_editor' => $data['chief_editor'],
                    'description' => 'Media partner resmi Samarinda yang menyajikan informasi aktual dan terpercaya.',
                ]
            );

            // Add mock logo via Spatie Media Library
            $tempLogoPath = tempnam(sys_get_temp_dir(), 'logo');
            file_put_contents($tempLogoPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
            $media->clearMediaCollection('logos');
            $media->addMedia($tempLogoPath)
                ->usingFileName('logo_'.strtolower(str_replace(' ', '_', $media->brand_name)).'.png')
                ->usingName($media->brand_name.' Logo')
                ->toMediaCollection('logos');

            $numDocsToCreate = rand(3, count($documentTypes));
            $selectedTypes = $documentTypes->shuffle()->take($numDocsToCreate);

            foreach ($selectedTypes as $docType) {
                $statuses = ['approved', 'approved', 'approved', 'pending', 'revision', 'rejected'];
                $status = $statuses[rand(0, count($statuses) - 1)];

                $issueDate = now()->subMonths(rand(1, 12));

                $expType = rand(1, 3);
                if ($expType === 1 && $docType->validity_days) {
                    $expirationDate = now()->subDays(rand(1, 60)); // Expired
                } elseif ($expType === 2 && $docType->validity_days) {
                    $expirationDate = now()->addDays(rand(5, 25)); // Expiring soon
                } elseif ($docType->validity_days) {
                    $expirationDate = now()->addDays($docType->validity_days - rand(10, 100)); // Valid
                } else {
                    $expirationDate = null; // Lifetime
                }

                $notes = null;
                if ($status === 'revision') {
                    $notes = 'Lampiran dokumen kurang jelas atau buram. Harap unggah ulang dokumen yang di-scan dengan kualitas tinggi.';
                } elseif ($status === 'rejected') {
                    $notes = 'Dokumen tidak valid atau nomor registrasi tidak terdaftar.';
                }

                $doc = MediaDocument::create([
                    'media_id' => $media->id,
                    'document_type_id' => $docType->id,
                    'document_number' => 'DOC/'.strtoupper($docType->code).'/'.rand(10000, 99999),
                    'issue_date' => $issueDate,
                    'expiration_date' => $expirationDate,
                    'verification_status' => $status,
                    'verification_notes' => $notes,
                    'verifier_id' => in_array($status, ['approved', 'rejected', 'revision']) ? $admin?->id : null,
                    'verified_at' => in_array($status, ['approved', 'rejected', 'revision']) ? now()->subDays(rand(1, 5)) : null,
                ]);

                // Attach mock document via Spatie Media Library
                $tempPath = tempnam(sys_get_temp_dir(), 'doc');
                file_put_contents($tempPath, '%PDF-1.5');
                $doc->clearMediaCollection('documents');
                $doc->addMedia($tempPath)
                    ->usingFileName('sample_'.strtolower($docType->code).'.pdf')
                    ->usingName($docType->name)
                    ->toMediaCollection('documents');

                if (in_array($status, ['approved', 'rejected', 'revision'])) {
                    VerificationLog::create([
                        'media_document_id' => $doc->id,
                        'user_id' => $admin?->id ?? 1,
                        'status' => $status,
                        'notes' => $notes,
                    ]);
                }
            }

            // Recalculate metrics for parent Media partner using the Action class
            app(RecalculateMediaScoreAction::class)->execute($media);
        }
    }
}
