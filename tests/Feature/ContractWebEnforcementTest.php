<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContractWebEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_create_requires_verified_email_and_min_profile()
    {
        $buyer = User::factory()->create([
            'role' => 'Buyer',
            'email_verified_at' => null,
            'phone' => null,
            'country' => null,
        ]);
        $seller = User::factory()->create(['role' => 'Seller']);

        $this->actingAs($buyer);

        $resp = $this->post(route('contracts.store'), [
            'title' => 'Test',
            'price_cents' => 1000,
            'currency' => 'USD',
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertSessionHasErrors(['email']);

        $buyer->email_verified_at = now();
        $buyer->phone = '123456789';
        $buyer->country = 'KE';
        $buyer->save();
        $this->actingAs($buyer->fresh());

        config()->set('currency.thresholds_cents.USD', 50000);
        config()->set('trust.profile.min_for_high_value', 80);

        $resp = $this->post(route('contracts.store'), [
            'title' => 'High Value',
            'price_cents' => 60000,
            'currency' => 'USD',
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertSessionHasErrors(['verification']);

        $buyer->verification_level = 'standard';
        $buyer->address_line1 = 'A';
        $buyer->city = 'B';
        $buyer->state = 'C';
        $buyer->postal_code = '00100';
        $buyer->date_of_birth = '1990-01-01';
        $buyer->save();
        $this->actingAs($buyer->fresh());

        $resp = $this->post(route('contracts.store'), [
            'title' => 'Allowed',
            'price_cents' => 10000,
            'currency' => 'USD',
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertRedirect();
    }

    public function test_web_sign_requires_verified_email_and_min_profile()
    {
        $buyer = User::factory()->create([
            'role' => 'Buyer',
            'email_verified_at' => now(),
            'phone' => '123456789',
            'country' => 'KE',
        ]);
        $seller = User::factory()->create(['role' => 'Seller']);

        $contract = Contract::create([
            'title' => 'To Sign',
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'draft',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
        ]);

        $buyer->email_verified_at = null;
        $buyer->save();
        $this->actingAs($buyer->fresh());
        $resp = $this->post(route('contracts.sign', $contract));
        $resp->assertSessionHas('error');

        $buyer->email_verified_at = now();
        $buyer->phone = null;
        $buyer->save();
        $this->actingAs($buyer->fresh());
        $resp = $this->post(route('contracts.sign', $contract));
        $resp->assertSessionHas('error');

        $buyer->phone = '123456789';
        $buyer->save();
        $this->actingAs($buyer->fresh());
        $resp = $this->post(route('contracts.sign', $contract));
        $resp->assertSessionHas('success');
    }
}
