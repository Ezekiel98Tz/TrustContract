<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        try {
            Auth::logoutOtherDevices($validated['password']);
        } catch (\Throwable $e) {}

        $request->user()->forceFill([
            'remember_token' => Str::random(60),
        ])->save();

        $currentId = $request->session()->getId();
        $table = config('session.table', 'sessions');
        DB::table($table)
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $currentId)
            ->delete();

        return back();
    }
}
