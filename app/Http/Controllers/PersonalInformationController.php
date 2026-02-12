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
        $completion = $user->profileCompletion();
        $verifications = \App\Models\Verification::where('user_id', $user->id)->latest()->paginate(10);

        return Inertia::render('Account/PersonalInformation', [
            'completion' => $completion,
            'status' => session('status'),
            'countries' => config('countries.list'),
            'verifications' => $verifications,
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
            'document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:1024'],
            'document_type' => ['sometimes', 'in:passport,national_id,driver_license,voters_id'],
        ]);

        $path = $validated['document']->store('ids', 'public');
        $type = $validated['document_type'] ?? 'passport';

        \App\Models\Verification::create([
            'user_id' => $request->user()->id,
            'document_path' => $path,
            'document_type' => $type,
            'status' => 'pending',
        ]);

        $request->user()->update([
            'id_document_path' => $path,
            'verification_status' => 'pending',
        ]);

        return Redirect::route('account.personal-information.edit')->with('status', 'verification-submitted')->with('success', 'Verification submitted.');
    }
}
