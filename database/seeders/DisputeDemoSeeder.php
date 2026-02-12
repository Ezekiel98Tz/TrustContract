<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Contract;
use App\Models\Dispute;
use Illuminate\Support\Facades\Hash;

class DisputeDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure baseline users
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('secret123'),
                'role' => 'Admin',
                'email_verified_at' => now(),
                'verification_status' => 'verified',
                'verification_level' => 'standard',
                'phone' => '123456789',
                'country' => 'KE',
            ]
        );
        $buyer = User::firstOrCreate(
            ['email' => 'buyer@example.com'],
            [
                'name' => 'Buyer User',
                'password' => Hash::make('secret123'),
                'role' => 'Buyer',
                'email_verified_at' => now(),
                'verification_status' => 'verified',
                'verification_level' => 'standard',
                'phone' => '111111111',
                'country' => 'KE',
            ]
        );
        $seller = User::firstOrCreate(
            ['email' => 'seller@example.com'],
            [
                'name' => 'Seller User',
                'password' => Hash::make('secret123'),
                'role' => 'Seller',
                'email_verified_at' => now(),
                'verification_status' => 'verified',
                'verification_level' => 'standard',
                'phone' => '222222222',
                'country' => 'KE',
            ]
        );

        // Create a few contracts
        $c1 = Contract::firstOrCreate([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'title' => 'Logo Design',
            'price_cents' => 5000,
            'currency' => 'USD',
            'status' => 'finalized',
        ]);
        $c2 = Contract::firstOrCreate([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'title' => 'Website Build',
            'price_cents' => 25000,
            'currency' => 'USD',
            'status' => 'signed',
        ]);
        $c3 = Contract::firstOrCreate([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'title' => 'Maintenance Retainer',
            'price_cents' => 12000,
            'currency' => 'USD',
            'status' => 'finalized',
        ]);

        // Disputes: open, mediate, resolved (won/lost/cancelled)
        Dispute::firstOrCreate([
            'contract_id' => $c1->id,
            'initiator_id' => $buyer->id,
            'reason' => 'Deliverables quality concerns',
            'status' => 'open',
        ]);

        Dispute::firstOrCreate([
            'contract_id' => $c2->id,
            'initiator_id' => $seller->id,
            'reason' => 'Scope creep without payment',
            'status' => 'mediate',
            'mediator_id' => $admin->id,
            'mediation_notes' => 'Collecting evidence from both parties',
        ]);

        Dispute::firstOrCreate([
            'contract_id' => $c3->id,
            'initiator_id' => $buyer->id,
            'reason' => 'Delayed response times',
            'status' => 'resolved',
            'resolution' => 'won',
            'resolved_at' => now()->subDay(),
        ]);

        Dispute::firstOrCreate([
            'contract_id' => $c3->id,
            'initiator_id' => $seller->id,
            'reason' => 'Buyer cancelled without reason',
            'status' => 'resolved',
            'resolution' => 'cancelled',
            'resolved_at' => now()->subDays(2),
        ]);
    }
}
