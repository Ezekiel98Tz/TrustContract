<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class BusinessInformationController extends Controller
{
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $business = Business::firstOrCreate(['user_id' => $user->id], ['company_name' => $user->name . ' Company']);
        $verifications = BusinessVerification::where('business_id', $business->id)->latest()->paginate(10);

        return Inertia::render('Account/BusinessInformation', [
            'business' => $business,
            'verifications' => $verifications,
            'status' => session('status'),
            'countries' => config('countries.list'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'jurisdiction' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:255'],
            'lei' => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
        ]);

        $business = Business::firstOrCreate(['user_id' => $request->user()->id], ['company_name' => $validated['company_name'] ?? $request->user()->name . ' Company']);
        $business->fill($validated);
        $business->save();

        return Redirect::route('account.business-information.edit')->with('status', 'saved');
    }

    public function submitDocument(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => ['required', 'in:business_registration,business_license,tax_certificate'],
            'document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
        ]);

        $business = Business::firstOrCreate(['user_id' => $request->user()->id], ['company_name' => $request->user()->name . ' Company']);
        $path = $validated['document']->store('kyb', 'public');

        BusinessVerification::create([
            'business_id' => $business->id,
            'document_type' => $validated['document_type'],
            'document_path' => $path,
            'status' => 'pending',
        ]);

        $business->update(['verification_status' => 'pending']);

        return Redirect::route('account.business-information.edit')->with('status', 'verification-submitted');
    }
}
