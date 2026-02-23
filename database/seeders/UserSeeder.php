<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create manager
        User::create([
            'name' => 'Manager PKS',
            'email' => 'manager@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create accounting
        User::create([
            'name' => 'Accounting',
            'email' => 'accounting@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'accounting',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create mandor
        User::create([
            'name' => 'Mandor Sortasi',
            'email' => 'mandor@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'mandor',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
    }
}
