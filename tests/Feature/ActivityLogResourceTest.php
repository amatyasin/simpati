<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->superAdminRole = Role::create(['name' => 'super_admin']);
    $this->diskominfoAdminRole = Role::create(['name' => 'diskominfo_admin']);
    $this->mediaPartnerRole = Role::create(['name' => 'media_partner']);

    // Create mock activity
    activity()
        ->useLog('default')
        ->log('Test activity log entry');
});

test('super admin can view activity logs', function () {
    $superAdmin = User::create([
        'name' => 'Super Admin',
        'email' => 'superadmin@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $superAdmin->assignRole($this->superAdminRole);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($superAdmin)
        ->get('/admin/activity-logs')
        ->assertStatus(200);
});

test('diskominfo admin can view activity logs', function () {
    $diskominfoAdmin = User::create([
        'name' => 'Diskominfo Admin',
        'email' => 'diskominfo@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $diskominfoAdmin->assignRole($this->diskominfoAdminRole);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($diskominfoAdmin)
        ->get('/admin/activity-logs')
        ->assertStatus(200);
});

test('media partner cannot view activity logs', function () {
    $partner = User::create([
        'name' => 'Media Partner',
        'email' => 'partner@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $partner->assignRole($this->mediaPartnerRole);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($partner)
        ->get('/admin/activity-logs')
        ->assertStatus(403);
});
