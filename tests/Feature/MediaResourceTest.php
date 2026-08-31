<?php

use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('media resource edit page loads for authorized users', function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // 1. Create role and admin user
    $superAdminRole = Role::create(['name' => 'super_admin']);
    $admin = User::create([
        'name' => 'Super Admin',
        'email' => 'superadmin@simpati.id',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole($superAdminRole);

    // 2. Create media category and category
    $category = MediaCategory::create([
        'name' => 'Cetak',
        'code' => 'CETAK',
        'is_active' => true,
    ]);

    // 3. Create a media partner user
    $partnerUser = User::create([
        'name' => 'Partner User',
        'email' => 'partner@simpati.id',
        'password' => bcrypt('password'),
    ]);

    // 4. Create media
    $media = Media::create([
        'user_id' => $partnerUser->id,
        'media_category_id' => $category->id,
        'company_name' => 'PT Test Media',
        'brand_name' => 'Test News',
        'verification_status' => 'draft',
    ]);

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    // 5. Test accessing the edit page
    $this->actingAs($admin)
        ->get("/admin/media/{$media->id}/edit")
        ->assertStatus(200);
});
