<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalInformationUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class PersonalInformationController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $fields = [
            'phone', 'country', 'address_line1', 'city', 'state', 'postal_code', 'date_of_birth',
        ];
        $filled = 0;
        foreach ($fields as $f) {
            if (!empty($user->{$f})) {
                $filled++;
            }
        }
        $completion = [
            'filled' => $filled,
            'total' => count($fields),
            'percent' => (int) floor(($filled / max(1, count($fields))) * 100),
        ];

        return Inertia::render('Account/PersonalInformation', [
            'completion' => $completion,
            'status' => session('status'),
            'countries' => config('countries.list'),
        ]);
    }

    public function update(PersonalInformationUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $oldEmail = $user->email;
        $user->fill($request->validated());
        $user->save();
        if ($oldEmail !== $user->email) {
            $user->forceFill([
                'remember_token' => Str::random(60),
            ])->save();
        }

        return Redirect::route('account.personal-information.edit')->with('status', 'saved');
    }

    public function submitId(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
        ]);

        $path = $validated['document']->store('ids', 'public');

        \App\Models\Verification::create([
            'user_id' => $request->user()->id,
            'document_path' => $path,
            'status' => 'pending',
        ]);

        $request->user()->update([
            'id_document_path' => $path,
            'verification_status' => 'pending',
        ]);

        return Redirect::route('account.personal-information.edit')->with('status', 'verification-submitted');
    }
}
