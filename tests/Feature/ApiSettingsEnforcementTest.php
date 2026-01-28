<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class ApiSettingsEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_create_uses_settings_thresholds_and_profile_percent()
    {
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $this->actingAs($admin);
        $this->patch(route('admin.trust-settings.update'), [
            'min_for_contract' => 60,
            'min_for_high_value' => 90,
            'currency_thresholds' => ['USD' => 20000, 'EUR' => 50000, 'TZS' => 130000000],
            'require_business_verification' => false,
        ]);

        $buyer = User::factory()->create([
            'role' => 'Buyer',
            'email_verified_at' => now(),
            'phone' => '123',
            'country' => 'KE',
            'verification_level' => 'standard',
        ]);
        $seller = User::factory()->create(['role' => 'Seller']);

        Sanctum::actingAs($buyer);
        // Completion below 60% should block any contract
        $resp = $this->postJson('/api/v1/contracts', [
            'title' => 'Low Completion',
            'price_cents' => 1000,
            'currency' => 'USD',
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertStatus(422);

        // Raise completion by adding address fields
        $buyer->update([
            'address_line1' => 'Road',
            'city' => 'Nairobi',
            'postal_code' => '00100',
            'date_of_birth' => '1990-01-01',
        ]);
        Sanctum::actingAs($buyer->fresh());

        // High value now: threshold 20000 cents; block until 90%
        $resp = $this->postJson('/api/v1/contracts', [
            'title' => 'HV',
            'price_cents' => 25000,
            'currency' => 'USD',
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertStatus(422);
    }
}
