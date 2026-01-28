<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $avg = \App\Models\ContractReview::where('reviewee_id', $user->id)->avg('rating');
        $count = \App\Models\ContractReview::where('reviewee_id', $user->id)->count();
        $recent = \App\Models\ContractReview::where('reviewee_id', $user->id)
            ->with(['reviewer'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'reputation' => [
                'avg' => $avg ? round($avg, 2) : null,
                'count' => $count,
                'recent' => $recent->map(function ($rv) {
                    return [
                        'id' => $rv->id,
                        'rating' => $rv->rating,
                        'comment' => $rv->comment,
                        'created_at' => $rv->created_at,
                        'reviewer' => $rv->reviewer ? [
                            'id' => $rv->reviewer->id,
                            'name' => $rv->reviewer->name,
                        ] : null,
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('avatars', 'public');
            if (!empty($user->profile_photo_path)) {
                try {
                    Storage::disk('public')->delete($user->profile_photo_path);
                } catch (\Throwable $e) {}
            }
            $user->profile_photo_path = $path;
        }

        $user->fill([
            'name' => $data['name'] ?? $user->name,
            'email' => $data['email'] ?? $user->email,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
