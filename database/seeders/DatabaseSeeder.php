<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // 2. Super Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@simpati.id'],
            [
                'name' => 'Super Administrator',
                'email' => 'admin@simpati.id',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('super_admin');

        // Diskominfo Admin user
        $diskominfo = User::firstOrCreate(
            ['email' => 'diskominfo@simpati.id'],
            [
                'name' => 'Diskominfo Admin',
                'email' => 'diskominfo@simpati.id',
                'password' => bcrypt('password'),
            ]
        );
        $diskominfo->assignRole('diskominfo_admin');

        // Media Partner user
        $mediaUser = User::firstOrCreate(
            ['email' => 'media@simpati.id'],
            [
                'name' => 'Media Partner',
                'email' => 'media@simpati.id',
                'password' => bcrypt('password'),
            ]
        );
        $mediaUser->assignRole('media_partner');

        // Leadership user
        $leadership = User::firstOrCreate(
            ['email' => 'leadership@simpati.id'],
            [
                'name' => 'Leadership User',
                'email' => 'leadership@simpati.id',
                'password' => bcrypt('password'),
            ]
        );
        $leadership->assignRole('leadership');

        // 3. Master data
        $this->call([
            MediaCategorySeeder::class,
            DocumentTypeSeeder::class,
        ]);

        // 4. Media Partners & Documents
        $this->call(MediaSeeder::class);
    }
}
