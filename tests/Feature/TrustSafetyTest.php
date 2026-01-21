<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Verification;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrustSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_contract_actions_require_email_and_profile()
    {
        $buyer = User::factory()->create([
            'role' => 'Buyer',
            'email_verified_at' => null,
            'phone' => null,
            'country' => null,
        ]);
        $seller = User::factory()->create(['role' => 'Seller']);

        Sanctum::actingAs($buyer);

        $resp = $this->postJson('/api/v1/contracts', [
            'title' => 'Test',
            'price_cents' => 1000,
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertStatus(422);

        $buyer->update(['email_verified_at' => now()]);
        Sanctum::actingAs($buyer->fresh());

        $resp = $this->postJson('/api/v1/contracts', [
            'title' => 'Test',
            'price_cents' => 1000,
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertStatus(422);

        $buyer->update(['phone' => '123456789', 'country' => 'KE']);
        Sanctum::actingAs($buyer->fresh());

        $resp = $this->postJson('/api/v1/contracts', [
            'title' => 'Test',
            'price_cents' => 60000,
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertStatus(403);

        $buyer->update(['verification_level' => 'standard']);
        Sanctum::actingAs($buyer->fresh());

        $resp = $this->postJson('/api/v1/contracts', [
            'title' => 'Test',
            'price_cents' => 10000,
            'counterparty_id' => $seller->id,
        ]);
        $resp->assertStatus(201);
    }

    public function test_kyc_submission_and_admin_review_promotes_level()
    {
        Storage::fake('public');
        $user = User::factory()->create(['verification_status' => 'unverified']);
        Sanctum::actingAs($user);

        $file = \Illuminate\Http\UploadedFile::fake()->image('id.jpg');
        $resp = $this->post('/account/personal-information/submit-id', ['document' => $file]);
        $resp->assertRedirect();

        $user->refresh();
        $this->assertEquals('pending', $user->verification_status);
        $verification = Verification::first();
        $this->assertNotNull($verification);

        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $resp = $this->patch(route('admin.verifications.review', $verification), ['status' => 'approved']);
        $resp->assertRedirect();

        $user->refresh();
        $this->assertEquals('verified', $user->verification_status);
        $this->assertEquals('standard', $user->verification_level);
    }

    public function test_two_factor_flow_on_login()
    {
        Notification::fake();
        $password = 'secret123';
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make($password),
            'two_factor_enabled' => true,
        ]);

        $resp = $this->post('/login', ['email' => $user->email, 'password' => $password]);
        $resp->assertRedirect(route('twofactor.challenge'));

        Notification::assertSentTo($user, TwoFactorCodeNotification::class, function ($n) use ($user) {
            // Submit the same code that was sent
            $code = (new \ReflectionClass($n))->getProperty('code');
            $code->setAccessible(true);
            $value = $code->getValue($n);
            $verify = $this->post(route('twofactor.verify'), ['code' => $value]);
            $verify->assertRedirect(route('dashboard'));
            return true;
        });
    }
}
