<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin/owner user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@tbs-system.local',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create manager
        User::create([
            'name' => 'Manager PKS',
            'email' => 'manager@tbs-system.local',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create supervisor
        User::create([
            'name' => 'Supervisor Produksi',
            'email' => 'supervisor@tbs-system.local',
            'password' => Hash::make('password'),
            'role' => 'supervisor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create operator
        User::create([
            'name' => 'Operator Timbangan',
            'email' => 'operator@tbs-system.local',
            'password' => Hash::make('password'),
            'role' => 'operator',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create staff
        User::create([
            'name' => 'Staff Administrasi',
            'email' => 'staff@tbs-system.local',
            'password' => Hash::make('password'),
            'role' => 'staff',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create mandor
        User::create([
            'name' => 'Mandor Sortasi',
            'email' => 'mandor@tbs-system.local',
            'password' => Hash::make('password'),
            'role' => 'mandor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
