<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractReview;
use App\Models\Dispute;
use App\Models\Transaction;
use App\Models\TrustSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase4FeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function seedUsers(): array
    {
        $buyer = User::factory()->create(['role' => 'Buyer', 'email_verified_at' => now()]);
        $seller = User::factory()->create(['role' => 'Seller', 'email_verified_at' => now()]);
        return [$buyer, $seller];
    }

    public function test_admin_can_set_dispute_rate_threshold()
    {
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $this->actingAs($admin);
        TrustSetting::query()->create([
            'min_for_contract' => 50,
            'min_for_high_value' => 80,
            'currency_thresholds' => ['USD' => 50000],
            'dispute_rate_warn_percent' => 5,
        ]);
        $resp = $this->patch(route('admin.trust-settings.update'), [
            'min_for_contract' => 50,
            'min_for_high_value' => 80,
            'dispute_rate_warn_percent' => 7,
            'currency_thresholds' => ['USD' => 50000],
            'require_business_verification' => false,
        ]);
        $resp->assertRedirect();
        $this->assertEquals(7, TrustSetting::first()->dispute_rate_warn_percent);
    }

    public function test_insights_include_dispute_rate_and_counts()
    {
        [$buyer, $seller] = $this->seedUsers();
        $contract = Contract::query()->create([
            'title' => 'T',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'price_cents' => 10000,
            'currency' => 'USD',
            'status' => 'finalized',
        ]);
        $txnPaid = Transaction::query()->create([
            'contract_id' => $contract->id,
            'payer_id' => $buyer->id,
            'payee_id' => $seller->id,
            'amount_cents' => 10000,
            'currency' => 'USD',
            'status' => 'paid',
        ]);
        $txnFailed = Transaction::query()->create([
            'contract_id' => $contract->id,
            'payer_id' => $buyer->id,
            'payee_id' => $seller->id,
            'amount_cents' => 10000,
            'currency' => 'USD',
            'status' => 'failed',
        ]);
        Dispute::query()->create([
            'transaction_id' => $txnFailed->id,
            'contract_id' => $contract->id,
            'status' => 'open',
            'provider' => 'sandbox',
            'external_event_id' => 'evt_1',
        ]);
        // Reviews to compute averages
        ContractReview::query()->create([
            'contract_id' => $contract->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 5,
        ]);
        $this->actingAs($buyer);
        $resp = $this->get(route('counterparties.insights', $seller->id));
        $resp->assertOk();
        $json = $resp->json();
        $this->assertEquals(1, $json['user']['paid_count']);
        $this->assertEquals(1, $json['user']['failed_count']);
        $this->assertEquals(1, $json['user']['dispute_count']);
        $this->assertTrue(isset($json['user']['dispute_rate']));
        $this->assertEquals(5.0, $json['user']['rating_avg']); // finalized-only review counted
    }

    public function test_search_aggregates_ratings_without_nplus1()
    {
        [$buyer, $seller] = $this->seedUsers();
        $contract = Contract::query()->create([
            'title' => 'T',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'price_cents' => 10000,
            'currency' => 'USD',
            'status' => 'finalized',
        ]);
        ContractReview::query()->create([
            'contract_id' => $contract->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 4,
        ]);
        $this->actingAs($buyer);
        $resp = $this->get(route('counterparties.search', ['q' => $seller->email]));
        $resp->assertOk();
        $json = $resp->json();
        $this->assertEquals(4.0, $json['results'][0]['rating_avg']);
        $this->assertEquals(1, $json['results'][0]['rating_count']);
    }

    public function test_reviews_only_allowed_post_finalization()
    {
        [$buyer, $seller] = $this->seedUsers();
        $contractSigned = Contract::query()->create([
            'title' => 'S',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'price_cents' => 10000,
            'currency' => 'USD',
            'status' => 'signed',
        ]);
        $contractFinalized = Contract::query()->create([
            'title' => 'F',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'price_cents' => 10000,
            'currency' => 'USD',
            'status' => 'finalized',
        ]);
        $this->actingAs($buyer);
        $respSigned = $this->post(route('contracts.reviews.store', $contractSigned), ['rating' => 5]);
        $respSigned->assertRedirect();
        $this->assertEquals(0, ContractReview::count());
        $respFinal = $this->post(route('contracts.reviews.store', $contractFinalized), ['rating' => 5]);
        $respFinal->assertRedirect();
        $this->assertEquals(1, ContractReview::count());
    }
}
