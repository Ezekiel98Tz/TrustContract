<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessVerification;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessVerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $status = $request->query('status');
        $query = BusinessVerification::query()->with('business')->latest();
        if ($status) {
            $query->where('status', $status);
        }
        $verifications = $query->paginate(15);

        return Inertia::render('Admin/BusinessVerifications/Index', [
            'verifications' => $verifications,
            'filters' => ['status' => $status],
        ]);
    }

    public function review(Request $request, BusinessVerification $verification)
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($verification->status !== 'pending') {
            return back()->with('error', 'Verification already reviewed.');
        }

        $verification->status = $validated['status'];
        $verification->reviewed_by = $request->user()->id;
        $verification->reviewed_at = now();
        $verification->notes = $validated['notes'] ?? null;
        $verification->save();

        if ($verification->business) {
            $verification->business->update([
                'verification_status' => $validated['status'] === 'approved' ? 'verified' : 'rejected',
                'verification_level' => $validated['status'] === 'approved' ? 'standard' : ($verification->business->verification_level),
            ]);
            try {
                $owner = \App\Models\User::find($verification->business->user_id);
                if ($owner) {
                    $owner->notify(new \App\Notifications\BusinessVerificationReviewedNotification($verification));
                }
            } catch (\Throwable $e) {}
        }

        return back()->with('success', 'Business verification reviewed.');
    }

    private function authorizeAdmin(Request $request): void
    {
        if ($request->user()->role !== 'Admin') {
            abort(403);
        }
    }
}
