<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DevUserSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local') && !app()->environment('testing')) {
            return;
        }

        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('secret123'),
                'role' => 'Admin',
                'email_verified_at' => now(),
                'verification_status' => 'verified',
                'verification_level' => 'standard',
                'phone' => '123456789',
                'country' => 'KE',
            ],
            [
                'name' => 'Buyer User',
                'email' => 'buyer@example.com',
                'password' => Hash::make('secret123'),
                'role' => 'Buyer',
                'email_verified_at' => now(),
                'verification_status' => 'verified',
                'verification_level' => 'basic',
                'phone' => '123456789',
                'country' => 'KE',
            ],
            [
                'name' => 'Seller User',
                'email' => 'seller@example.com',
                'password' => Hash::make('secret123'),
                'role' => 'Seller',
                'email_verified_at' => now(),
                'verification_status' => 'verified',
                'verification_level' => 'basic',
                'phone' => '123456789',
                'country' => 'KE',
            ],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], $u);
        }
    }
}
