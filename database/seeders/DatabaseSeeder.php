<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed settings
        $this->call(SettingSeeder::class);
        // Seed roles and permissions
        $this->call(RolePermissionSeeder::class);

        // Create default admin user
        $admin = \App\Models\User::firstOrCreate([
            'email' => 'admin@travelify.com',
        ], [
            'name' => 'Admin',
            'password' => bcrypt('admin123'),
        ]);
        if (class_exists('Spatie\\Permission\\Models\\Role')) {
            $admin->assignRole('Travel Manager');
        }
    }
}
