<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTrustSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_trust_settings()
    {
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $this->actingAs($admin);

        $resp = $this->get(route('admin.trust-settings.index'));
        $resp->assertStatus(200);

        $payload = [
            'min_for_contract' => 60,
            'min_for_high_value' => 90,
            'currency_thresholds' => [
                'USD' => 25000,
                'EUR' => 25000,
                'TZS' => 130000000,
            ],
        ];
        $resp = $this->patch(route('admin.trust-settings.update'), $payload);
        $resp->assertRedirect();

        $this->assertDatabaseHas('trust_settings', [
            'min_for_contract' => 60,
            'min_for_high_value' => 90,
        ]);
    }

    public function test_settings_affect_high_value_enforcement_on_web_create()
    {
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $this->actingAs($admin);
        $this->patch(route('admin.trust-settings.update'), [
            'min_for_contract' => 50,
            'min_for_high_value' => 90,
            'currency_thresholds' => ['USD' => 10000, 'EUR' => 50000, 'TZS' => 130000000],
        ]);

        $buyer = User::factory()->create([
            'role' => 'Buyer',
            'email_verified_at' => now(),
            'phone' => '123456789',
            'country' => 'KE',
            'verification_level' => 'standard',
        ]);
        $seller = User::factory()->create(['role' => 'Seller']);

        $this->actingAs($buyer);

        $resp = $this->post(route('contracts.store'), [
            'title' => 'HV Test',
            'description' => 'Terms',
            'price_cents' => 15000,
            'currency' => 'USD',
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertSessionHasErrors(['profile']);
    }
}
