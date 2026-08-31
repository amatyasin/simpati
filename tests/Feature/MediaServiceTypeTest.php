<?php

use App\Models\MediaServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminRole = Role::firstOrCreate(['name' => 'super_admin']);
    $this->partnerRole = Role::firstOrCreate(['name' => 'media_partner']);

    $this->adminUser = User::create([
        'name' => 'Admin Service',
        'email' => 'admin_service@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->adminUser->assignRole($this->adminRole);

    $this->partnerUser = User::create([
        'name' => 'Partner Service',
        'email' => 'partner_service@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $this->partnerUser->assignRole($this->partnerRole);
});

test('Default media service types can be seeded and managed by Admin', function () {
    MediaServiceType::seedDefaults();

    expect(MediaServiceType::count())->toBeGreaterThanOrEqual(8);
    expect(MediaServiceType::where('name', 'Berita/Artikel')->exists())->toBeTrue();
    expect(MediaServiceType::where('name', 'Radio/Audio')->exists())->toBeTrue();

    // Admin can create new service type
    $this->actingAs($this->adminUser);
    expect($this->adminUser->can('create', MediaServiceType::class))->toBeTrue();

    $serviceType = MediaServiceType::create([
        'name' => 'Podcast Video',
        'slug' => 'podcast-video',
        'is_active' => true,
    ]);

    expect($serviceType->name)->toBe('Podcast Video');

    // Media partner policy checks
    expect($this->partnerUser->can('viewAny', MediaServiceType::class))->toBeFalse();
    expect($this->partnerUser->can('create', MediaServiceType::class))->toBeFalse();
    expect($this->partnerUser->can('update', $serviceType))->toBeFalse();
    expect($this->partnerUser->can('delete', $serviceType))->toBeFalse();
});
