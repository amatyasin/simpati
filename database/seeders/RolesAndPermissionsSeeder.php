<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Define standard resource permissions
        $resources = [
            'media_category',
            'document_type',
            'media',
            'media_document',
            'activity_log',
        ];

        $abilities = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
        ];

        // Create standard permissions
        foreach ($resources as $resource) {
            foreach ($abilities as $ability) {
                Permission::firstOrCreate(['name' => "{$ability}_{$resource}"]);
            }
        }

        // Add custom abilities for media_document and verification
        Permission::firstOrCreate(['name' => 'verify_media_document']);

        // Custom module permissions
        $modules = [
            'dashboard',
            'reports',
            'settings',
            'verification',
        ];

        $moduleAbilities = [
            'view',
            'manage',
            'export',
        ];

        foreach ($modules as $module) {
            foreach ($moduleAbilities as $ability) {
                Permission::firstOrCreate(['name' => "{$ability}_{$module}"]);
            }
        }

        // 2. Create Roles and Assign Permissions

        // Role: super_admin (all permissions)
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Role: diskominfo_admin
        $diskominfoAdmin = Role::firstOrCreate(['name' => 'diskominfo_admin']);
        $diskominfoPermissions = [
            // Master Data
            'view_any_media_category', 'view_media_category', 'create_media_category', 'update_media_category', 'delete_media_category', 'restore_media_category',
            'view_any_document_type', 'view_document_type', 'create_document_type', 'update_document_type', 'delete_document_type', 'restore_document_type',

            // Media & Documents
            'view_any_media', 'view_media', 'create_media', 'update_media', 'delete_media', 'restore_media',
            'view_any_media_document', 'view_media_document', 'create_media_document', 'update_media_document', 'delete_media_document', 'restore_media_document',
            'verify_media_document',

            // Log Monitoring
            'view_any_activity_log', 'view_activity_log',

            // Module level permissions
            'view_dashboard',
            'view_reports', 'export_reports',
            'view_verification', 'manage_verification',
        ];
        $diskominfoAdmin->syncPermissions($diskominfoPermissions);

        // Role: media_partner
        $mediaPartner = Role::firstOrCreate(['name' => 'media_partner']);
        $mediaPermissions = [
            'view_media', 'update_media',
            'view_any_media_document', 'view_media_document', 'create_media_document', 'update_media_document', 'delete_media_document',
            'view_dashboard',
        ];
        $mediaPartner->syncPermissions($mediaPermissions);

        // Role: leadership
        $leadership = Role::firstOrCreate(['name' => 'leadership']);
        $leadershipPermissions = [
            'view_any_media', 'view_media',
            'view_any_media_category', 'view_media_category',
            'view_any_document_type', 'view_document_type',
            'view_any_media_document', 'view_media_document',
            'view_dashboard',
            'view_reports',
        ];
        $leadership->syncPermissions($leadershipPermissions);
    }
}
