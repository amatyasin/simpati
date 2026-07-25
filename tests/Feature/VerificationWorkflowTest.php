<?php

use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\DocumentType;
use App\Models\User;
use App\Actions\VerifyDocumentAction;
use App\Actions\RejectDocumentAction;
use App\Actions\RequestRevisionAction;
use App\Actions\ExpireDocumentAction;
use App\Enums\DocumentVerificationStatus;
use App\Enums\MediaVerificationStatus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = MediaCategory::create([
        'name' => 'Cetak',
        'code' => 'CETAK',
        'is_active' => true,
    ]);

    $this->docType = DocumentType::create([
        'name' => 'SIUP',
        'code' => 'SIUP',
        'weight' => 100,
        'is_required' => true,
        'is_active' => true,
    ]);

    $this->partnerUser = User::create([
        'name' => 'Partner User',
        'email' => 'partner@simpati.id',
        'password' => bcrypt('password'),
    ]);

    $this->media = Media::create([
        'user_id' => $this->partnerUser->id,
        'company_name' => 'PT Test Media',
        'brand_name' => 'Test News',
        'media_category_id' => $this->category->id,
        'completeness_percentage' => 0,
        'verification_score' => 0,
        'verification_status' => MediaVerificationStatus::DRAFT->value,
    ]);

    $this->verifier = User::create([
        'name' => 'Verifier Admin',
        'email' => 'verifier@simpati.id',
        'password' => bcrypt('password'),
    ]);
});

test('document approval dispatches database notifications to partner', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType->id,
        'document_number' => '123/SIUP',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    app(VerifyDocumentAction::class)->execute($doc, $this->verifier->id, 'Disetujui');

    $doc->refresh();
    expect($doc->verification_status)->toBe(DocumentVerificationStatus::APPROVED);

    // Verify database notification is generated for partner user
    $unreadNotifications = $this->partnerUser->unreadNotifications;
    expect($unreadNotifications->count())->toBe(1);
    expect($unreadNotifications->first()->data['title'])->toContain('Dokumen Disetujui');
});

test('document rejection dispatches database notifications to partner', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType->id,
        'document_number' => '123/SIUP',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    app(RejectDocumentAction::class)->execute($doc, $this->verifier->id, 'Dokumen kedaluwarsa');

    $doc->refresh();
    expect($doc->verification_status)->toBe(DocumentVerificationStatus::REJECTED);

    $unreadNotifications = $this->partnerUser->unreadNotifications;
    expect($unreadNotifications->count())->toBe(1);
    expect($unreadNotifications->first()->data['title'])->toContain('Dokumen Ditolak');
});

test('request revision dispatches database notifications to partner', function () {
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType->id,
        'document_number' => '123/SIUP',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    app(RequestRevisionAction::class)->execute($doc, $this->verifier->id, 'Revisi scan file');

    $doc->refresh();
    expect($doc->verification_status)->toBe(DocumentVerificationStatus::REVISION);

    $unreadNotifications = $this->partnerUser->unreadNotifications;
    expect($unreadNotifications->count())->toBe(1);
    expect($unreadNotifications->first()->data['title'])->toContain('Revisi Dokumen Diperlukan');
});

test('document expiration updates score and sends warning notification', function () {
    // 1. Create document and approve it as NOT expired first (completeness 100%, score 100%)
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType->id,
        'document_number' => '123/SIUP',
        'issue_date' => now()->subMonths(6),
        'expiration_date' => now()->addDays(5), // Valid
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);

    // Pre-assert that media scores are recalculated to 100% since it was approved
    $this->media->refresh();
    expect($this->media->completeness_percentage)->toBe(100);
    expect($this->media->verification_score)->toBe(100);

    // 2. Set expiration date to the past in the database directly
    $doc->updateQuietly([
        'expiration_date' => now()->subDay(),
    ]);

    // 3. Trigger expiration scan action
    app(ExpireDocumentAction::class)->execute();

    $this->media->refresh();
    $doc->refresh();

    // Score drops since document expired
    expect($this->media->verification_score)->toBe(0);

    // Check expiration notification
    $unreadNotifications = $this->partnerUser->unreadNotifications;
    expect($unreadNotifications->count())->toBe(1);
    expect($unreadNotifications->first()->data['title'])->toContain('Dokumen Kedaluwarsa');
});

test('profile completeness drop triggers incomplete profile warning', function () {
    // 1. Upload file to reach 100% completeness
    $doc = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType->id,
        'document_number' => '123/SIUP',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::APPROVED->value,
    ]);

    $this->media->refresh();
    expect($this->media->completeness_percentage)->toBe(100);

    // Empty notifications first
    $this->partnerUser->unreadNotifications()->delete();

    // 2. Delete document so completeness drops to 0%
    $doc->delete();

    $this->media->refresh();
    expect($this->media->completeness_percentage)->toBe(0);

    // Warn notification sent
    $unreadNotifications = $this->partnerUser->unreadNotifications;
    expect($unreadNotifications->count())->toBe(1);
    expect($unreadNotifications->first()->data['title'])->toContain('Kelengkapan Profil Kurang');
});
