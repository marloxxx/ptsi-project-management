<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@ptsi.co.id',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $superAdmin->assignRole('super_admin');

        // Create test users
        $admin = User::create([
            'name' => 'Admin PTSI',
            'email' => 'admin.test@ptsi.co.id',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole('admin');

        $manager = User::create([
            'name' => 'Manager PTSI',
            'email' => 'manager@ptsi.co.id',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $manager->assignRole('manager');
    }
}
