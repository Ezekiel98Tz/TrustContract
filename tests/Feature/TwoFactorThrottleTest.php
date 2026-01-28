<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_is_throttled_after_limit()
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->actingAs($user);
        // First 3 requests within window should pass (302 redirect back to challenge)
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('twofactor.send'))->assertStatus(302);
        }
        // 4th should be throttled
        $this->post(route('twofactor.send'))->assertStatus(429);
    }

    public function test_verify_is_throttled_after_limit()
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->actingAs($user);
        // Attempt invalid codes repeatedly
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('twofactor.verify'), ['code' => '000000'])->assertStatus(302);
        }
        $this->post(route('twofactor.verify'), ['code' => '000000'])->assertStatus(429);
    }
}
