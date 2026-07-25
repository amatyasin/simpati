<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->superAdminRole = Role::create(['name' => 'super_admin']);
    $this->diskominfoAdminRole = Role::create(['name' => 'diskominfo_admin']);
    $this->leadershipRole = Role::create(['name' => 'leadership']);
    $this->mediaPartnerRole = Role::create(['name' => 'media_partner']);
});

test('super admin can view monitoring page', function () {
    $superAdmin = User::create([
        'name' => 'Super Admin',
        'email' => 'superadmin@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $superAdmin->assignRole($this->superAdminRole);

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($superAdmin)
        ->get('/admin/monitoring-page')
        ->assertStatus(200);
});

test('diskominfo admin can view monitoring page', function () {
    $diskominfoAdmin = User::create([
        'name' => 'Diskominfo Admin',
        'email' => 'diskominfo@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $diskominfoAdmin->assignRole($this->diskominfoAdminRole);

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($diskominfoAdmin)
        ->get('/admin/monitoring-page')
        ->assertStatus(200);
});

test('leadership can view monitoring page', function () {
    $leadership = User::create([
        'name' => 'Leadership User',
        'email' => 'leadership@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $leadership->assignRole($this->leadershipRole);

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($leadership)
        ->get('/admin/monitoring-page')
        ->assertStatus(200);
});

test('media partner cannot view monitoring page', function () {
    $partner = User::create([
        'name' => 'Media Partner',
        'email' => 'partner@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $partner->assignRole($this->mediaPartnerRole);

    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($partner)
        ->get('/admin/monitoring-page')
        ->assertStatus(403);
});
