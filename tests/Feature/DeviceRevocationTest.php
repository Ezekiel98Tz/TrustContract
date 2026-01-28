<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRevocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_revoked_device_redirects_to_login()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $ip = '127.0.0.1';
        $ua = 'PHPUnit-Agent';
        $fp = hash('sha256', $ip . '|' . $ua);
        UserDevice::create([
            'user_id' => $user->id,
            'fingerprint_hash' => $fp,
            'revoked_at' => now(),
        ]);

        $resp = $this->withServerVariables(['REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => $ua])
            ->get(route('contracts.index'));
        $resp->assertRedirect(route('login'));
    }
}
