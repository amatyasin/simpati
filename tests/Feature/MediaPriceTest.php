<?php

use App\Enums\MediaPriceStatus;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\MediaPrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = MediaCategory::create([
        'name' => 'Online',
        'slug' => 'online',
        'is_active' => true,
    ]);

    $this->adminRole = Role::firstOrCreate(['name' => 'super_admin']);
    $this->partnerRole = Role::firstOrCreate(['name' => 'media_partner']);

    // Admin User
    $this->adminUser = User::create([
        'name' => 'Admin User',
        'email' => 'admin_price@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->adminUser->assignRole($this->adminRole);

    // Media Partner A
    $this->partnerAUser = User::create([
        'name' => 'Partner A User',
        'email' => 'partner_a@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->partnerAUser->assignRole($this->partnerRole);

    $this->mediaA = Media::create([
        'company_name' => 'PT Media A',
        'brand_name' => 'Tribun A News',
        'user_id' => $this->partnerAUser->id,
        'media_category_id' => $this->category->id,
    ]);

    // Media Partner B
    $this->partnerBUser = User::create([
        'name' => 'Partner B User',
        'email' => 'partner_b@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->partnerBUser->assignRole($this->partnerRole);

    $this->mediaB = Media::create([
        'company_name' => 'PT Media B',
        'brand_name' => 'Tribun B News',
        'user_id' => $this->partnerBUser->id,
        'media_category_id' => $this->category->id,
    ]);
});

test('MediaPrice formats rupiah correctly and records audit log', function () {
    $this->actingAs($this->adminUser);

    $price = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 750000,
        'unit' => 'Per Publikasi',
        'effective_from' => now()->startOfDay(),
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    expect($price->formatted_price)->toBe('Rp 750.000');
    expect($price->created_by)->toBe($this->adminUser->id);
    expect($price->status)->toBe(MediaPriceStatus::ACTIVE);

    // Verify Spatie Activity Log recorded
    $log = Activity::where('log_name', 'media_price')->first();
    expect($log)->not->toBeNull();
    expect($log->causer_id)->toBe($this->adminUser->id);
});

test('Media Partner can create price proposal for owned media in DRAFT status', function () {
    $this->actingAs($this->partnerAUser);

    $price = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 500000,
        'unit' => 'Per Publikasi',
        'effective_from' => now(),
        'status' => MediaPriceStatus::DRAFT->value,
    ]);

    expect($price->status)->toBe(MediaPriceStatus::DRAFT);
    expect($price->media_id)->toBe($this->mediaA->id);
});

test('Media Partner can view owned media price but CANNOT view another partner media price', function () {
    $priceA = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Banner',
        'price' => 1000000,
        'unit' => 'Per Hari',
        'effective_from' => now(),
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    $priceB = MediaPrice::create([
        'media_id' => $this->mediaB->id,
        'service_type' => 'Banner',
        'price' => 1200000,
        'unit' => 'Per Hari',
        'effective_from' => now(),
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    // Partner A policy checks
    expect($this->partnerAUser->can('view', $priceA))->toBeTrue();
    expect($this->partnerAUser->can('view', $priceB))->toBeFalse();
    expect($this->partnerAUser->can('update', $priceB))->toBeFalse();
    expect($this->partnerAUser->can('delete', $priceB))->toBeFalse();

    // Admin policy checks (Admin can view & update all)
    expect($this->adminUser->can('view', $priceA))->toBeTrue();
    expect($this->adminUser->can('view', $priceB))->toBeTrue();
});

test('Media Partner can submit price proposal changing status to PENDING', function () {
    $this->actingAs($this->partnerAUser);

    $price = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Video',
        'price' => 2000000,
        'unit' => 'Per Video',
        'effective_from' => now(),
        'status' => MediaPriceStatus::DRAFT->value,
    ]);

    expect($this->partnerAUser->can('submit', $price))->toBeTrue();

    $price->submitForApproval();

    expect($price->fresh()->status)->toBe(MediaPriceStatus::PENDING);
    expect($price->fresh()->submitted_at)->not->toBeNull();
});

test('Media Partner CANNOT approve or activate prices independently', function () {
    $price = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Banner',
        'price' => 1500000,
        'unit' => 'Per Hari',
        'effective_from' => now(),
        'status' => MediaPriceStatus::PENDING->value,
    ]);

    expect($this->partnerAUser->can('approve', $price))->toBeFalse();
    expect($this->partnerAUser->can('reject', $price))->toBeFalse();

    expect($this->adminUser->can('approve', $price))->toBeTrue();
    expect($this->adminUser->can('reject', $price))->toBeTrue();
});

test('Admin approval changes status to ACTIVE and updates approval timestamps', function () {
    $this->actingAs($this->adminUser);

    $price = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 750000,
        'unit' => 'Per Publikasi',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::PENDING->value,
    ]);

    $price->approve($this->adminUser->id);

    $freshPrice = $price->fresh();
    expect($freshPrice->status)->toBe(MediaPriceStatus::ACTIVE);
    expect($freshPrice->approved_by)->toBe($this->adminUser->id);
    expect($freshPrice->approved_at)->not->toBeNull();
});

test('Admin rejection sets status to REJECTED with rejection reason', function () {
    $this->actingAs($this->adminUser);

    $price = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Banner',
        'price' => 3000000,
        'unit' => 'Per Hari',
        'effective_from' => now(),
        'status' => MediaPriceStatus::PENDING->value,
    ]);

    $reason = 'Harga melebihi batas standar penawaran media.';
    $price->reject($this->adminUser->id, $reason);

    $freshPrice = $price->fresh();
    expect($freshPrice->status)->toBe(MediaPriceStatus::REJECTED);
    expect($freshPrice->rejected_by)->toBe($this->adminUser->id);
    expect($freshPrice->rejected_at)->not->toBeNull();
    expect($freshPrice->rejection_reason)->toBe($reason);
});

test('Invalid state transitions are rejected by model guards', function () {
    $activePrice = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 750000,
        'unit' => 'Per Publikasi',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    // Submitting an active price returns false
    expect($activePrice->submitForApproval())->toBeFalse();

    $rejectedPrice = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Video',
        'price' => 5000000,
        'unit' => 'Per Video',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::REJECTED->value,
    ]);

    // Approving a rejected price without re-submitting returns false
    expect($rejectedPrice->approve($this->adminUser->id))->toBeFalse();

    $pendingPrice = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Sosial Media',
        'price' => 1000000,
        'unit' => 'Per Post',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::PENDING->value,
    ]);

    // Rejecting with empty reason returns false
    expect($pendingPrice->reject($this->adminUser->id, '   '))->toBeFalse();
});

test('Transaction helpers only resolve ACTIVE and CURRENT prices', function () {
    // Draft price
    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 100000,
        'unit' => 'Per Artikel',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::DRAFT->value,
    ]);

    // Pending price
    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Banner',
        'price' => 200000,
        'unit' => 'Per Hari',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::PENDING->value,
    ]);

    // Rejected price
    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Video',
        'price' => 300000,
        'unit' => 'Per Video',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::REJECTED->value,
    ]);

    // Active price
    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Sosial Media',
        'price' => 500000,
        'unit' => 'Per Post',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    // Draft, Pending, Rejected prices must return NULL for transaction snapshot
    expect($this->mediaA->getActivePriceFor('Berita/Artikel'))->toBeNull();
    expect($this->mediaA->getActivePriceFor('Banner'))->toBeNull();
    expect($this->mediaA->getActivePriceFor('Video'))->toBeNull();

    // Active price returns valid snapshot
    $activePrice = $this->mediaA->getActivePriceFor('Sosial Media');
    expect($activePrice)->not->toBeNull();
    expect((float) $activePrice->price)->toBe(500000.0);

    $snapshot = $this->mediaA->getPriceSnapshotFor('Sosial Media');
    expect($snapshot)->toBeArray();
    expect((float) $snapshot['unit_price'])->toBe(500000.0);
    expect($snapshot['formatted_unit_price'])->toBe('Rp 500.000');
});

test('Expired and future prices are excluded from current date resolution', function () {
    // Expired price (effective_until in past)
    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 400000,
        'unit' => 'Per Artikel',
        'effective_from' => '2025-01-01',
        'effective_until' => '2025-12-31',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    // Future price (effective_from in future)
    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Banner',
        'price' => 800000,
        'unit' => 'Per Hari',
        'effective_from' => '2027-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    $currentDate = Carbon::parse('2026-06-01');

    expect($this->mediaA->getActivePriceFor('Berita/Artikel', $currentDate))->toBeNull();
    expect($this->mediaA->getActivePriceFor('Banner', $currentDate))->toBeNull();
});

test('Server side price resolution throws exception if no active price exists', function () {
    expect(fn () => $this->mediaA->resolveServerSidePrice('Radio', Carbon::parse('2026-06-01')))
        ->toThrow(InvalidArgumentException::class);
});

test('Multiple service types can coexist actively for the same media', function () {
    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 750000,
        'unit' => 'Per Artikel',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Banner',
        'price' => 1500000,
        'unit' => 'Per Hari',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Video',
        'price' => 2000000,
        'unit' => 'Per Video',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    expect($this->mediaA->getActivePriceFor('Berita/Artikel'))->not->toBeNull();
    expect($this->mediaA->getActivePriceFor('Banner'))->not->toBeNull();
    expect($this->mediaA->getActivePriceFor('Video'))->not->toBeNull();
});

test('Price changes replace old active price and maintain historical transaction snapshot integrity', function () {
    $oldPrice = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 500000,
        'unit' => 'Per Artikel',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    // Transaction snapshot created on 2026-02-01
    $transactionSnapshot = $this->mediaA->getPriceSnapshotFor('Berita/Artikel', Carbon::parse('2026-02-01'));
    expect((float) $transactionSnapshot['unit_price'])->toBe(500000.0);

    // Media Partner submits new price on 2026-07-01
    $newPriceProposal = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 750000,
        'unit' => 'Per Artikel',
        'effective_from' => '2026-07-01',
        'status' => MediaPriceStatus::PENDING->value,
    ]);

    // Admin approves new price
    $newPriceProposal->approve($this->adminUser->id);

    // Old price becomes inactive
    expect($oldPrice->fresh()->status)->toBe(MediaPriceStatus::INACTIVE);

    // Transaction snapshot on 2026-08-01 yields new price (750.000)
    $newSnapshot = $this->mediaA->getPriceSnapshotFor('Berita/Artikel', Carbon::parse('2026-08-01'));
    expect((float) $newSnapshot['unit_price'])->toBe(750000.0);

    // But historical transaction snapshot from 2026-02-01 remains 500.000!
    expect((float) $transactionSnapshot['unit_price'])->toBe(500000.0);
});

test('MediaPrice description field can be saved and included in price snapshot', function () {
    $price = MediaPrice::create([
        'media_id' => $this->mediaA->id,
        'service_type' => 'Berita/Artikel',
        'price' => 750000,
        'unit' => 'Per Publikasi',
        'description' => 'Paket berita tayang 24 jam di halaman utama, include 1 foto & link sosial media.',
        'effective_from' => '2026-01-01',
        'status' => MediaPriceStatus::ACTIVE->value,
    ]);

    expect($price->description)->toBe('Paket berita tayang 24 jam di halaman utama, include 1 foto & link sosial media.');

    $snapshot = $this->mediaA->getPriceSnapshotFor('Berita/Artikel');
    expect($snapshot['description'])->toBe('Paket berita tayang 24 jam di halaman utama, include 1 foto & link sosial media.');
});
