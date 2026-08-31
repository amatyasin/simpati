<?php

use App\Models\MediaPriceUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::firstOrCreate(['name' => 'super_admin']);
    $this->partnerRole = Role::firstOrCreate(['name' => 'media_partner']);

    $this->adminUser = User::create([
        'name' => 'Admin Unit',
        'email' => 'admin_unit@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->adminUser->assignRole($this->adminRole);

    $this->partnerUser = User::create([
        'name' => 'Partner Unit',
        'email' => 'partner_unit@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->partnerUser->assignRole($this->partnerRole);
});

test('Default price units can be seeded and managed by Admin', function () {
    MediaPriceUnit::seedDefaults();

    expect(MediaPriceUnit::count())->toBeGreaterThanOrEqual(8);
    expect(MediaPriceUnit::where('name', 'Per Publikasi')->exists())->toBeTrue();
    expect(MediaPriceUnit::where('name', 'Per Hari')->exists())->toBeTrue();

    // Admin can create unit
    $this->actingAs($this->adminUser);
    expect($this->adminUser->can('create', MediaPriceUnit::class))->toBeTrue();

    $unit = MediaPriceUnit::create([
        'name' => 'Per Eksplisit',
        'slug' => 'per-eksplisit',
        'is_active' => true,
    ]);

    expect($unit->name)->toBe('Per Eksplisit');

    // Media partner policy checks
    expect($this->partnerUser->can('viewAny', MediaPriceUnit::class))->toBeFalse();
    expect($this->partnerUser->can('create', MediaPriceUnit::class))->toBeFalse();
    expect($this->partnerUser->can('update', $unit))->toBeFalse();
    expect($this->partnerUser->can('delete', $unit))->toBeFalse();
});
