<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Dispute;
use App\Models\Contract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisputeLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_move_dispute_through_mediate_and_resolved()
    {
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $buyer = User::factory()->create(['role' => 'Buyer']);
        $seller = User::factory()->create(['role' => 'Seller']);
        $contract = Contract::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'title' => 'Agreement',
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'finalized',
        ]);

        $dispute = Dispute::create([
            'contract_id' => $contract->id,
            'initiator_id' => $buyer->id,
            'reason' => 'Item not delivered',
            'status' => 'open',
        ]);

        $this->actingAs($admin);
        // Move to mediate
        $resp = $this->patch(route('admin.disputes.review', $dispute->id), [
            'status' => 'mediate',
            'mediator_id' => $admin->id,
            'mediation_notes' => 'Investigating both sides',
        ]);
        $resp->assertRedirect();
        $dispute = $dispute->fresh();
        $this->assertEquals('mediate', $dispute->status);
        $this->assertNull($dispute->resolved_at);
        $this->assertEquals($admin->id, $dispute->mediator_id);
        $this->assertNotEmpty($dispute->mediation_notes);

        // Resolve with outcome
        $resp = $this->patch(route('admin.disputes.review', $dispute->id), [
            'status' => 'resolved',
            'resolution' => 'won',
        ]);
        $resp->assertRedirect();
        $dispute = $dispute->fresh();
        $this->assertEquals('resolved', $dispute->status);
        $this->assertEquals('won', $dispute->resolution);
        $this->assertNotNull($dispute->resolved_at);
    }
}
