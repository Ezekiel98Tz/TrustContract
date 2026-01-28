<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceLastSeenUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_seen_updates_on_protected_request()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $ip = '203.0.113.1';
        $ua = 'TestAgent/1.0';
        $fp = hash('sha256', $ip . '|' . $ua);
        $device = UserDevice::create([
            'user_id' => $user->id,
            'fingerprint_hash' => $fp,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'first_seen_at' => now()->subHour(),
            'last_seen_at' => null,
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeader('User-Agent', $ua)
            ->get(route('profile.edit'))
            ->assertStatus(200);

        $device->refresh();
        $this->assertNotNull($device->last_seen_at);
        $this->assertEquals($ip, $device->ip_address);
        $this->assertEquals($ua, $device->user_agent);
    }
}
