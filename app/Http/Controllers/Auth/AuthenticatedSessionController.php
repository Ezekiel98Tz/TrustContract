<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();
        if ($user->two_factor_enabled) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->two_factor_code_hash = hash('sha256', $code);
            $user->two_factor_expires_at = now()->addMinutes(10);
            $user->two_factor_last_sent_at = now();
            $user->save();
            try {
                $user->notify(new \App\Notifications\TwoFactorCodeNotification($code));
            } catch (\Throwable $e) {}
            $request->session()->put('two_factor_passed', false);
            return redirect()->route('twofactor.challenge');
        }

        // Record device (only if table exists)
        if (\Illuminate\Support\Facades\Schema::hasTable('user_devices')) {
            $fingerprint = hash('sha256', ($request->ip() ?? 'unknown') . '|' . ($request->header('User-Agent') ?? 'unknown'));
            $device = \App\Models\UserDevice::firstOrCreate(
                ['user_id' => $user->id, 'fingerprint_hash' => $fingerprint],
                [
                    'ip_address' => (string) $request->ip(),
                    'user_agent' => (string) $request->header('User-Agent'),
                    'first_seen_at' => now(),
                ]
            );
            $device->last_seen_at = now();
            $device->save();
            if ($device->wasRecentlyCreated) {
                try {
                    $user->notify(new \App\Notifications\NewLoginNotification($device));
                } catch (\Throwable $e) {}
            }
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
