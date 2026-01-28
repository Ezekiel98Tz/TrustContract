<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionsUiActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sessions_index_and_delete_single_session()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $table = config('session.table', 'sessions');
        $currentId = session()->getId();
        $otherId = Str::random(40);
        DB::table($table)->insert([
            'id' => $otherId,
            'user_id' => $user->id,
            'ip_address' => '203.0.113.55',
            'user_agent' => 'OtherAgent/2.0',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
        $this->get(route('account.sessions.index'))->assertStatus(200);
        $this->delete(route('account.sessions.destroy', $otherId))->assertStatus(302);
        $others = DB::table($table)
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentId)
            ->count();
        $this->assertSame(0, $others);
    }

    public function test_logout_other_sessions_action()
    {
        $user = User::factory()->create(['password' => bcrypt('Pass123!')]);
        $this->actingAs($user);
        $table = config('session.table', 'sessions');
        $currentId = session()->getId();
        DB::table($table)->insert([
            'id' => Str::random(40),
            'user_id' => $user->id,
            'ip_address' => '203.0.113.99',
            'user_agent' => 'Another/3.0',
            'payload' => 'x',
            'last_activity' => time(),
        ]);
        $this->post(route('account.sessions.destroy_others'), [
            'current_password' => 'Pass123!',
        ])->assertStatus(302);
        $others = DB::table($table)
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentId)
            ->count();
        $this->assertSame(0, $others);
    }
}
