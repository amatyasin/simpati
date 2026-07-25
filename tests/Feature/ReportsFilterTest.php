<?php

use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaDocument;
use App\Models\DocumentType;
use App\Exports\MediaExport;
use App\Exports\VerificationExport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = MediaCategory::create([
        'name' => 'Cetak',
        'code' => 'CETAK',
        'is_active' => true,
    ]);

    $this->media1 = Media::create([
        'company_name' => 'PT Media Satu',
        'brand_name' => 'Media Satu',
        'media_category_id' => $this->category->id,
        'verification_score' => 90,
        'completeness_percentage' => 100,
        'verification_status' => 'approved',
        'created_at' => now()->subDays(5),
    ]);

    $this->media2 = Media::create([
        'company_name' => 'PT Media Dua',
        'brand_name' => 'Media Dua',
        'media_category_id' => $this->category->id,
        'verification_score' => 45,
        'completeness_percentage' => 60,
        'verification_status' => 'pending',
        'created_at' => now(),
    ]);
});

test('MediaExport filters by min and max verification score', function () {
    // 1. Min score 80
    $export1 = new MediaExport(status: 'all', categoryId: null, minScore: 80);
    $records1 = $export1->collection();
    expect($records1->count())->toBe(1);
    expect($records1->first()->brand_name)->toBe('Media Satu');

    // 2. Max score 50
    $export2 = new MediaExport(status: 'all', categoryId: null, maxScore: 50);
    $records2 = $export2->collection();
    expect($records2->count())->toBe(1);
    expect($records2->first()->brand_name)->toBe('Media Dua');
});

test('MediaExport filters by completeness percentage range', function () {
    // 1. Min completeness 80
    $export1 = new MediaExport(status: 'all', categoryId: null, minScore: null, maxScore: null, minCompleteness: 80);
    $records1 = $export1->collection();
    expect($records1->count())->toBe(1);
    expect($records1->first()->brand_name)->toBe('Media Satu');

    // 2. Max completeness 70
    $export2 = new MediaExport(status: 'all', categoryId: null, minScore: null, maxScore: null, maxCompleteness: 70);
    $records2 = $export2->collection();
    expect($records2->count())->toBe(1);
    expect($records2->first()->brand_name)->toBe('Media Dua');
});

test('MediaExport filters by registration date range', function () {
    // Force media1 to a deterministic past date via DB to bypass Eloquent timestamps
    \Illuminate\Support\Facades\DB::table('media')
        ->where('id', $this->media1->id)
        ->update(['created_at' => '2025-01-01 12:00:00', 'updated_at' => '2025-01-01 12:00:00']);

    // media2 was created 'now()' which is definitely > 2026-06-01
    $export = new MediaExport(
        status: 'all',
        categoryId: null,
        minScore: null,
        maxScore: null,
        minCompleteness: null,
        maxCompleteness: null,
        startDate: '2026-06-01'
    );
    $records = $export->collection();
    expect($records->count())->toBe(1);
    expect($records->first()->brand_name)->toBe('Media Dua');
});

test('VerificationExport filters documents by date range', function () {
    $docType = DocumentType::create([
        'name' => 'Surat Tugas',
        'code' => 'TUGAS',
        'weight' => 50,
        'is_required' => true,
        'is_active' => true,
    ]);

    $doc1 = MediaDocument::create([
        'media_id' => $this->media1->id,
        'document_type_id' => $docType->id,
        'document_number' => '123-TUGAS',
        'issue_date' => now(),
        'expiration_date' => now()->addDays(5),
    ]);

    $doc2 = MediaDocument::create([
        'media_id' => $this->media2->id,
        'document_type_id' => $docType->id,
        'document_number' => '456-TUGAS',
        'issue_date' => now(),
        'expiration_date' => now()->addDays(40),
    ]);

    // Filter documents expiring in less than 10 days
    $export = new VerificationExport(
        status: 'all', 
        documentTypeId: null, 
        startDate: now()->format('Y-m-d'),
        endDate: now()->addDays(10)->format('Y-m-d')
    );
    
    $records = $export->collection();
    expect($records->count())->toBe(1);
    expect($records->first()->document_number)->toBe('123-TUGAS');
});
