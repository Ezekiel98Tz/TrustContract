<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PasswordChangeInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_change_purges_other_sessions_and_rotates_token()
    {
        $user = User::factory()->create(['password' => bcrypt('OldPass123!')]);
        $this->actingAs($user);

        $table = config('session.table', 'sessions');
        $currentId = session()->getId();
        // Simulate another session for the same user
        DB::table($table)->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => '198.51.100.2',
            'user_agent' => 'OtherAgent/1.0',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
        $rememberBefore = $user->remember_token;

        $this->put(route('password.update'), [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ])->assertStatus(302);

        $user->refresh();
        $this->assertNotEquals($rememberBefore, $user->remember_token);
        // No other sessions should remain (current session may or may not be persisted)
        $others = DB::table($table)
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentId)
            ->count();
        $this->assertSame(0, $others);
    }
}
