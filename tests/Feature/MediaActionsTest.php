<?php

use App\Actions\RejectDocumentAction;
use App\Actions\RequestRevisionAction;
use App\Actions\VerifyDocumentAction;
use App\Enums\DocumentVerificationStatus;
use App\Models\DocumentType;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\User;
use App\Models\VerificationLog;
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

    $this->media = Media::create([
        'company_name' => 'PT Test Media',
        'brand_name' => 'Test News',
        'media_category_id' => $this->category->id,
    ]);

    $this->document = MediaDocument::create([
        'media_id' => $this->media->id,
        'document_type_id' => $this->docType->id,
        'document_number' => '123/SIUP',
        'issue_date' => now(),
        'verification_status' => DocumentVerificationStatus::PENDING->value,
    ]);

    $this->verifier = User::create([
        'name' => 'Verifier Admin',
        'email' => 'verifier@simpati.id',
        'password' => bcrypt('password'),
    ]);
});

test('VerifyDocumentAction updates document status, creates log and recalculates score', function () {
    $action = app(VerifyDocumentAction::class);

    $action->execute($this->document, $this->verifier->id, 'Dokumen lengkap dan valid');

    // Fresh instance
    $this->document->refresh();
    $this->media->refresh();

    expect($this->document->verification_status)->toBe(DocumentVerificationStatus::APPROVED);
    expect($this->document->verifier_id)->toBe($this->verifier->id);
    expect($this->document->verification_notes)->toBe('Dokumen lengkap dan valid');
    expect($this->document->verified_at)->not->toBeNull();

    // Check log
    $log = VerificationLog::where('media_document_id', $this->document->id)->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(DocumentVerificationStatus::APPROVED);
    expect($log->user_id)->toBe($this->verifier->id);
    expect($log->notes)->toBe('Dokumen lengkap dan valid');

    // Check score recalculation (completeness 100%, verification score 100%)
    expect($this->media->completeness_percentage)->toBe(100);
    expect($this->media->verification_score)->toBe(100);
});

test('RejectDocumentAction updates document status, creates log and recalculates score', function () {
    $action = app(RejectDocumentAction::class);

    $action->execute($this->document, $this->verifier->id, 'Dokumen palsu');

    $this->document->refresh();
    $this->media->refresh();

    expect($this->document->verification_status)->toBe(DocumentVerificationStatus::REJECTED);
    expect($this->document->verifier_id)->toBe($this->verifier->id);
    expect($this->document->verification_notes)->toBe('Dokumen palsu');
    expect($this->document->verified_at)->not->toBeNull();

    // Check log
    $log = VerificationLog::where('media_document_id', $this->document->id)->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(DocumentVerificationStatus::REJECTED);
    expect($log->notes)->toBe('Dokumen palsu');

    // Check score (completeness 100%, but verification score is 0% since rejected)
    expect($this->media->completeness_percentage)->toBe(100);
    expect($this->media->verification_score)->toBe(0);
});

test('RequestRevisionAction updates document status, creates log and recalculates score', function () {
    $action = app(RequestRevisionAction::class);

    $action->execute($this->document, $this->verifier->id, 'Tolong upload scan yang lebih jelas');

    $this->document->refresh();
    $this->media->refresh();

    expect($this->document->verification_status)->toBe(DocumentVerificationStatus::REVISION);
    expect($this->document->verifier_id)->toBe($this->verifier->id);
    expect($this->document->verification_notes)->toBe('Tolong upload scan yang lebih jelas');
    expect($this->document->verified_at)->not->toBeNull();

    // Check log
    $log = VerificationLog::where('media_document_id', $this->document->id)->first();
    expect($log)->not->toBeNull();
    expect($log->status)->toBe(DocumentVerificationStatus::REVISION);
    expect($log->notes)->toBe('Tolong upload scan yang lebih jelas');

    // Check score (completeness 100%, but verification score is 0% since revision needed)
    expect($this->media->completeness_percentage)->toBe(100);
    expect($this->media->verification_score)->toBe(0);
});
