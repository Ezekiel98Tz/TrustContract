<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        if (!$user->two_factor_enabled || $request->session()->get('two_factor_passed', false)) {
            return \Inertia\Inertia::location(route('dashboard'));
        }
        return Inertia::render('Auth/TwoFactorChallenge', [
            'status' => session('status'),
            'expires_at' => $user->two_factor_expires_at,
        ]);
    }

    public function send(Request $request)
    {
        $user = $request->user();
        if (!$user->two_factor_enabled) {
            return Redirect::route('dashboard');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->two_factor_code_hash = hash('sha256', $code);
        $user->two_factor_expires_at = now()->addMinutes(10);
        $user->two_factor_last_sent_at = now();
        $user->save();

        try {
            $user->notify(new \App\Notifications\TwoFactorCodeNotification($code));
        } catch (\Throwable $e) {}

        return Redirect::route('twofactor.challenge')->with('status', 'code-sent');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        if (!$user->two_factor_enabled) {
            return Redirect::route('dashboard');
        }

        $valid = $user->two_factor_code_hash
            && $user->two_factor_expires_at
            && now()->lte($user->two_factor_expires_at)
            && hash_equals($user->two_factor_code_hash, hash('sha256', $request->input('code')));

        if (!$valid) {
            return Redirect::route('twofactor.challenge')->with('status', 'invalid-code');
        }

        $request->session()->put('two_factor_passed', true);
        $user->two_factor_code_hash = null;
        $user->two_factor_expires_at = null;
        $user->save();

        return Redirect::intended(route('dashboard'));
    }
}
