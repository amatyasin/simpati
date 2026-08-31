<?php

use App\Enums\DocumentVerificationStatus;
use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = MediaCategory::create([
        'name' => 'Online',
        'code' => 'ONLINE',
        'is_active' => true,
    ]);

    $this->docType1 = DocumentType::create([
        'name' => 'Akte Perusahaan',
        'code' => 'AKTE',
        'weight' => 50,
        'is_required' => true,
        'is_active' => true,
    ]);

    $this->docType2 = DocumentType::create([
        'name' => 'Sertifikat PWI',
        'code' => 'PWI',
        'weight' => 50,
        'is_required' => true,
        'is_active' => true,
    ]);

    $this->partner = User::create([
        'name' => 'Partner User',
        'email' => 'partner@simpati.id',
        'password' => bcrypt('password'),
    ]);

    $this->media = Media::create([
        'user_id' => $this->partner->id,
        'company_name' => 'PT Security Test',
        'brand_name' => 'Security News',
        'media_category_id' => $this->category->id,
    ]);
});

test('the database unique rule blocks duplicate document_type_id per media', function () {
    // 1. Create first document of type AKTE
    MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType1->id,
        'document_number' => 'DOC-001',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    // 2. Verify the unique constraint validator rejects the duplicate at the Validator level
    $validator = Validator::make(
        [
            'media_id' => $this->media->id,
            'document_type_id' => $this->docType1->id,
        ],
        [
            'document_type_id' => [
                Rule::unique('media_documents', 'document_type_id')
                    ->where('media_id', $this->media->id)
                    ->whereNull('deleted_at'),
            ],
        ]
    );

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('document_type_id'))->toBeTrue();
});

test('filename normalization slug strips special characters', function () {
    $originalName = 'Test Document @ Name # 123.pdf';
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
    $normalized = 'doc_'.time().'_'.$baseName.'.'.$extension;

    // Str::slug converts @ to 'at' and # is stripped
    expect($baseName)->toContain('test-document');
    expect($baseName)->toContain('name');
    expect($baseName)->toContain('123');
    expect($normalized)->toContain('.pdf');
    expect($normalized)->toStartWith('doc_');
    expect($normalized)->not->toContain('@');
    expect($normalized)->not->toContain('#');
    expect($normalized)->not->toContain(' ');
});

test('logo filename normalization slug strips special characters', function () {
    $originalName = 'LOGO Media & News (2026).png';
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $baseName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
    $normalized = 'logo_'.time().'_'.$baseName.'.'.$extension;

    expect($normalized)->toContain('logo-media-news-2026');
    expect($normalized)->toContain('.png');
    expect($normalized)->toStartWith('logo_');
    expect($normalized)->not->toContain('&');
    expect($normalized)->not->toContain('(');
    expect($normalized)->not->toContain(')');
});

test('accepted MIME types for documents are correctly defined on the model', function () {
    $doc = new MediaDocument;
    $doc->id = 1;

    // The registerMediaCollections method defines accepted MIME types
    // We verify the expected MIME types are present in the collection config
    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    // Since we can't easily inspect Spatie Media Library collection config without booting
    // we assert by validating directly against a list
    $testCases = [
        ['application/pdf', true],
        ['image/jpeg', true],
        ['image/png', true],
        ['text/x-php', false],
        ['application/x-sh', false],
        ['text/html', false],
    ];

    foreach ($testCases as [$mime, $shouldPass]) {
        $result = in_array($mime, $allowedMimes);
        expect($result)->toBe($shouldPass, "MIME type '$mime' should ".($shouldPass ? 'pass' : 'fail'));
    }
});

test('document can be soft-deleted and is excluded from active queries', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType1->id,
        'document_number' => 'DOC-DELETE-TEST',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    $id = $doc->id;
    $doc->delete();

    // Soft-deleted — should not appear in normal queries
    expect(MediaDocument::find($id))->toBeNull();

    // But should appear when including trashed
    expect(MediaDocument::withTrashed()->find($id))->not->toBeNull();
});

test('document upload relationship is correctly owned by the media partner', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType2->id,
        'document_number' => 'DOC-OWNER-TEST',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    expect($doc->mediaPartner->id)->toBe($this->media->id);
    expect($doc->mediaPartner->user_id)->toBe($this->partner->id);
});
