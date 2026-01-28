<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        $table = config('session.table', 'sessions');
        $currentId = $request->session()->getId();
        $sessions = DB::table($table)
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) use ($currentId) {
                return [
                    'id' => $s->id,
                    'ip_address' => $s->ip_address,
                    'user_agent' => $s->user_agent,
                    'last_activity' => $s->last_activity,
                    'is_current' => $s->id === $currentId,
                ];
            });

        return Inertia::render('Account/Sessions', [
            'sessions' => $sessions,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $table = config('session.table', 'sessions');
        $currentId = $request->session()->getId();
        if ($id === $currentId) {
            return Redirect::route('account.sessions.index')->with('status', 'cannot-delete-current');
        }
        DB::table($table)
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();
        return Redirect::route('account.sessions.index')->with('status', 'session-deleted');
    }

    public function destroyOthers(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);
        try {
            Auth::logoutOtherDevices($validated['current_password']);
        } catch (\Throwable $e) {}
        $table = config('session.table', 'sessions');
        $currentId = $request->session()->getId();
        DB::table($table)
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $currentId)
            ->delete();
        return Redirect::route('account.sessions.index')->with('status', 'others-deleted');
    }
}
