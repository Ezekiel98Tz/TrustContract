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
        // Admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'Admin',
            'verification_status' => 'approved',
        ]);

        // Buyer
        User::create([
            'name' => 'Buyer User',
            'email' => 'buyer@example.com',
            'password' => 'password',
            'role' => 'Buyer',
            'verification_status' => 'unverified',
        ]);

        // Seller
        User::create([
            'name' => 'Seller User',
            'email' => 'seller@example.com',
            'password' => 'password',
            'role' => 'Seller',
            'verification_status' => 'unverified',
        ]);
    }
}
