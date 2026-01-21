<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
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
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

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
