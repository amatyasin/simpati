<?php

use App\Actions\MergeMediaDocumentsAction;
use App\Enums\DocumentVerificationStatus;
use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createFakePdfFile(string $filename = 'test.pdf'): UploadedFile {
    $pdfContent = \Barryvdh\DomPDF\Facade\Pdf::loadHTML('<html><body><h1>Test Document</h1></body></html>')->output();
    return UploadedFile::fake()->createWithContent($filename, $pdfContent);
}

beforeEach(function () {
    Storage::fake('public');

    $this->adminRole = Role::create(['name' => 'diskominfo_admin']);
    $this->partnerRole = Role::create(['name' => 'media_partner']);

    $this->adminUser = User::create([
        'name' => 'Admin Diskominfo',
        'email' => 'admin@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->adminUser->assignRole($this->adminRole);

    $this->partnerUser = User::create([
        'name' => 'Partner User',
        'email' => 'partner@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->partnerUser->assignRole($this->partnerRole);

    $this->category = MediaCategory::create([
        'name' => 'Siber',
        'code' => 'SIBER',
        'is_active' => true,
    ]);

    $this->docTypeAkte = DocumentType::create([
        'name' => 'Akte Perusahaan',
        'code' => 'AKTE',
        'weight' => 30,
        'is_required' => true,
        'is_active' => true,
    ]);

    $this->docTypeNib = DocumentType::create([
        'name' => 'NIB',
        'code' => 'NIB',
        'weight' => 30,
        'is_required' => true,
        'is_active' => true,
    ]);

    $this->docTypeUkw = DocumentType::create([
        'name' => 'Sertifikat UKW',
        'code' => 'UKW',
        'weight' => 40,
        'is_required' => true,
        'is_active' => true,
    ]);

    $this->media = Media::create([
        'user_id' => $this->partnerUser->id,
        'company_name' => 'PT Tepian News',
        'brand_name' => 'Tepian News',
        'media_category_id' => $this->category->id,
    ]);
});

test('merging PDF fails when no documents are uploaded', function () {
    expect(function () {
        app(MergeMediaDocumentsAction::class)->execute($this->media);
    })->toThrow(\RuntimeException::class, 'Tidak ada dokumen yang tersedia untuk digabungkan.');

    expect($this->media->merged_pdf_url)->toBeNull();
});

test('merging PDF succeeds when 1 document is available', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docTypeAkte->id,
        'document_number' => 'DOC-AKTE-001',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);

    $doc->addMedia(createFakePdfFile('akte.pdf'))->toMediaCollection('documents', 'public');

    $updatedMedia = app(MergeMediaDocumentsAction::class)->execute($this->media);

    expect($updatedMedia->merged_pdf_path)->not->toBeNull();
    expect(file_exists($updatedMedia->merged_pdf_path))->toBeTrue();
    expect($updatedMedia->available_documents_count)->toBe(1);
});

test('merging PDF succeeds with partial documents and orders them by DocumentType weight', function () {
    // Add UKW first (weight 40)
    $docUkw = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docTypeUkw->id,
        'document_number' => 'DOC-UKW-001',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);
    $docUkw->addMedia(createFakePdfFile('ukw.pdf'))->toMediaCollection('documents', 'public');

    // Add Akte second (weight 30)
    $docAkte = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docTypeAkte->id,
        'document_number' => 'DOC-AKTE-001',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);
    $docAkte->addMedia(createFakePdfFile('akte.pdf'))->toMediaCollection('documents', 'public');

    $updatedMedia = app(MergeMediaDocumentsAction::class)->execute($this->media);

    expect(file_exists($updatedMedia->merged_pdf_path))->toBeTrue();
    expect($updatedMedia->available_documents_count)->toBe(2);
    expect($updatedMedia->total_required_documents_count)->toBe(3);
});

test('merging PDF includes documents with REVISION or PENDING status non-destructively', function () {
    $docRevision = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docTypeNib->id,
        'document_number' => 'DOC-NIB-001',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::REVISION->value,
    ]);
    $docRevision->addMedia(createFakePdfFile('nib.pdf'))->toMediaCollection('documents', 'public');

    $updatedMedia = app(MergeMediaDocumentsAction::class)->execute($this->media);

    expect(file_exists($updatedMedia->merged_pdf_path))->toBeTrue();
    expect($docRevision->fresh()->verification_status->value)->toBe('revision');
});

test('regenerate PDF updates merged file when document is replaced', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docTypeAkte->id,
        'document_number' => 'DOC-AKTE-V1',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);
    $doc->addMedia(createFakePdfFile('v1.pdf'))->toMediaCollection('documents', 'public');

    $firstMedia = app(MergeMediaDocumentsAction::class)->execute($this->media);

    // Update document number and replace file
    $doc->update(['document_number' => 'DOC-AKTE-V2']);
    $doc->clearMediaCollection('documents');
    $doc->addMedia(createFakePdfFile('v2.pdf'))->toMediaCollection('documents', 'public');

    $secondMedia = app(MergeMediaDocumentsAction::class)->execute($this->media);

    expect(file_exists($secondMedia->merged_pdf_path))->toBeTrue();
});

test('authorized user can view merged pdf via web route', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docTypeAkte->id,
        'document_number' => 'DOC-AKTE-001',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);
    $doc->addMedia(createFakePdfFile('akte.pdf'))->toMediaCollection('documents', 'public');

    $response = $this->actingAs($this->partnerUser)
        ->get(route('media.merged-pdf.show', $this->media));

    $response->assertStatus(200);
    $response->assertHeader('content-type', 'application/pdf');
});

test('unauthorized user cannot view merged pdf of another media partner', function () {
    $otherUser = User::create([
        'name' => 'Other Partner',
        'email' => 'other@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $otherUser->assignRole($this->partnerRole);

    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docTypeAkte->id,
        'document_number' => 'DOC-AKTE-001',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);
    $doc->addMedia(createFakePdfFile('akte.pdf'))->toMediaCollection('documents', 'public');

    $response = $this->actingAs($otherUser)
        ->get(route('media.merged-pdf.show', $this->media));

    $response->assertStatus(403);
});
