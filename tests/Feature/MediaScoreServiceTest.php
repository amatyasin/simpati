<?php

use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\DocumentType;
use App\Models\User;
use App\Services\MediaScoreService;
use App\Enums\DocumentVerificationStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('media score service calculates completeness based on document weights', function () {
    // 1. Create a media category
    $category = MediaCategory::create([
        'name' => 'Cetak',
        'code' => 'CETAK',
        'is_active' => true,
    ]);

    // 2. Create required document types with weights
    $docType1 = DocumentType::create([
        'name' => 'SIUP',
        'code' => 'SIUP',
        'weight' => 60,
        'is_required' => true,
        'is_active' => true,
    ]);

    $docType2 = DocumentType::create([
        'name' => 'Akta Pendirian',
        'code' => 'AKTA',
        'weight' => 40,
        'is_required' => true,
        'is_active' => true,
    ]);

    // 3. Create media
    $media = Media::create([
        'company_name' => 'PT Test Media',
        'brand_name' => 'Test News',
        'media_category_id' => $category->id,
        'verification_status' => 'draft',
    ]);

    $service = new MediaScoreService();

    // No documents uploaded -> completeness = 0%
    expect($service->calculateCompleteness($media))->toBe(0);

    // Upload SIUP (60% weight) -> completeness should be 60%
    $doc1 = MediaDocument::create([
        'media_id' => $media->id,
        'document_type_id' => $docType1->id,
        'document_number' => '123/SIUP',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    expect($service->calculateCompleteness($media))->toBe(60);

    // Upload Akta (40% weight) -> completeness should be 100%
    $doc2 = MediaDocument::create([
        'media_id' => $media->id,
        'document_type_id' => $docType2->id,
        'document_number' => '456/AKTA',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    expect($service->calculateCompleteness($media))->toBe(100);
});

test('media score service calculates verification score based on approved documents and validity', function () {
    $category = MediaCategory::create([
        'name' => 'Cetak',
        'code' => 'CETAK',
        'is_active' => true,
    ]);

    $docType1 = DocumentType::create([
        'name' => 'SIUP',
        'code' => 'SIUP',
        'weight' => 60,
        'is_required' => true,
        'is_active' => true,
    ]);

    $docType2 = DocumentType::create([
        'name' => 'Akta Pendirian',
        'code' => 'AKTA',
        'weight' => 40,
        'is_required' => true,
        'is_active' => true,
    ]);

    $media = Media::create([
        'company_name' => 'PT Test Media',
        'brand_name' => 'Test News',
        'media_category_id' => $category->id,
    ]);

    $service = new MediaScoreService();

    // Create SIUP (60% weight) but PENDING status -> verification score = 0%
    $doc1 = MediaDocument::create([
        'media_id' => $media->id,
        'document_type_id' => $docType1->id,
        'document_number' => '123/SIUP',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    expect($service->calculateVerificationScore($media))->toBe(0);

    // Approve SIUP -> verification score = 60%
    $doc1->update(['verification_status' => DocumentVerificationStatus::APPROVED->value]);
    expect($service->calculateVerificationScore($media))->toBe(60);

    // Add Akta (40% weight) and approve, but make it EXPIRED -> verification score remains 60%
    $doc2 = MediaDocument::create([
        'media_id' => $media->id,
        'document_type_id' => $docType2->id,
        'document_number' => '456/AKTA',
        'issue_date' => now()->subYears(2),
        'expiration_date' => now()->subDay(),
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);

    expect($service->calculateVerificationScore($media))->toBe(60);

    // Make Akta active -> verification score = 100%
    $doc2->update(['expiration_date' => now()->addYear()]);
    expect($service->calculateVerificationScore($media))->toBe(100);
});

test('media score service computes ranking score using formula (Verification × 0.8) + (Completeness × 0.2)', function () {
    $category = MediaCategory::create([
        'name' => 'Cetak',
        'code' => 'CETAK',
        'is_active' => true,
    ]);

    $docType1 = DocumentType::create([
        'name' => 'SIUP',
        'code' => 'SIUP',
        'weight' => 70,
        'is_required' => true,
        'is_active' => true,
    ]);

    $docType2 = DocumentType::create([
        'name' => 'Akta',
        'code' => 'AKTA',
        'weight' => 30,
        'is_required' => true,
        'is_active' => true,
    ]);

    $media = Media::create([
        'company_name' => 'PT Test Media',
        'brand_name' => 'Test News',
        'media_category_id' => $category->id,
    ]);

    $service = new MediaScoreService();

    // Upload SIUP, not yet approved.
    // Completeness = 70%
    // Verification Score = 0%
    // Ranking Score = (0 * 0.8) + (70 * 0.2) = 14.0
    MediaDocument::create([
        'media_id' => $media->id,
        'document_type_id' => $docType1->id,
        'document_number' => '123',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    expect($service->calculateCompleteness($media))->toBe(70);
    expect($service->calculateVerificationScore($media))->toBe(0);
    expect($service->calculateRankingScore($media))->toBe(14.0);

    // Approve SIUP.
    // Completeness = 70%
    // Verification Score = 70%
    // Ranking Score = (70 * 0.8) + (70 * 0.2) = 70.0
    $media->mediaDocuments()->first()->update(['verification_status' => DocumentVerificationStatus::APPROVED->value]);
    expect($service->calculateRankingScore($media))->toBe(70.0);
});
