<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorChallengeGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_challenge_redirects_when_2fa_disabled()
    {
        $user = User::factory()->create(['two_factor_enabled' => false]);
        $this->actingAs($user);
        $this->get(route('twofactor.challenge'))->assertRedirect(route('dashboard'));
    }

    public function test_challenge_redirects_when_2fa_already_passed()
    {
        $user = User::factory()->create(['two_factor_enabled' => true]);
        $this->actingAs($user);
        session()->put('two_factor_passed', true);
        $this->get(route('twofactor.challenge'))->assertRedirect(route('dashboard'));
    }
}
