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
            'dispute_rate_warn_percent' => 5,
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

    public function test_api_sign_requires_business_verification_when_setting_true()
    {
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $this->actingAs($admin);
        $this->patch(route('admin.trust-settings.update'), [
            'min_for_contract' => 50,
            'min_for_high_value' => 80,
            'dispute_rate_warn_percent' => 5,
            'currency_thresholds' => ['USD' => 20000],
            'require_business_verification' => true,
        ]);

        $buyer = User::factory()->create([
            'role' => 'Buyer',
            'email_verified_at' => now(),
            'phone' => '111',
            'country' => 'KE',
            'verification_level' => 'standard',
            'verification_status' => 'verified',
            'address_line1' => 'Road',
            'city' => 'Nairobi',
            'state' => 'Nairobi',
            'postal_code' => '00100',
            'date_of_birth' => '1990-01-01',
        ]);
        $seller = User::factory()->create([
            'role' => 'Seller',
            'email_verified_at' => now(),
            'phone' => '222',
            'country' => 'KE',
            'verification_level' => 'standard',
            'verification_status' => 'verified',
            'address_line1' => 'Ave',
            'city' => 'Mombasa',
            'state' => 'Mombasa',
            'postal_code' => '80100',
            'date_of_birth' => '1988-02-02',
        ]);

        // Create an unverified business for seller
        \App\Models\Business::create([
            'user_id' => $seller->id,
            'company_name' => 'Seller Co',
            'registration_number' => 'REG123',
            'jurisdiction' => 'KE',
            'address_line1' => 'HQ',
            'city' => 'Mombasa',
            'postal_code' => '80100',
            'verification_status' => 'unverified',
        ]);

        // Buyer creates high-value contract
        \Laravel\Sanctum\Sanctum::actingAs($buyer);
        $resp = $this->postJson('/api/v1/contracts', [
            'title' => 'HV',
            'price_cents' => 25000,
            'currency' => 'USD',
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertStatus(201);
        $contractId = $resp->json('contract.id');

        // Seller attempts to sign — should be blocked due to business not verified
        \Laravel\Sanctum\Sanctum::actingAs($seller->fresh());
        $resp = $this->patchJson("/api/v1/contracts/{$contractId}/sign", []);
        $resp->assertStatus(403);
        $resp->assertJson(['message' => 'Business verification required to sign high-value contracts']);

        // Verify seller business, then signing should pass
        $business = \App\Models\Business::where('user_id', $seller->id)->first();
        $business->update(['verification_status' => 'verified']);
        \Laravel\Sanctum\Sanctum::actingAs($seller->fresh());
        $resp = $this->patchJson("/api/v1/contracts/{$contractId}/sign", []);
        $resp->assertStatus(200);
    }
}
