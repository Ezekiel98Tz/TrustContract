<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Verification;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorizeAdmin($request);

        $status = $request->query('status');
        $query = Verification::query()->with('user')->latest();
        if ($status) {
            $query->where('status', $status);
        }
        $verifications = $query->paginate(15);

        return Inertia::render('Admin/Verifications/Index', [
            'verifications' => $verifications,
            'filters' => ['status' => $status],
        ]);
    }

    public function review(Request $request, Verification $verification)
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

        User::where('id', $verification->user_id)->update([
            'verification_status' => $validated['status'] === 'approved' ? 'verified' : 'rejected',
            'verification_level' => $validated['status'] === 'approved' ? 'standard' : (new \Illuminate\Database\Query\Expression('verification_level')),
        ]);

        try {
            if ($verification->user) {
                $verification->user->notify(new \App\Notifications\VerificationReviewedNotification($verification));
            }
        } catch (\Throwable $e) {}

        return back()->with('success', 'Verification reviewed.');
    }

    private function authorizeAdmin(Request $request): void
    {
        if ($request->user()->role !== 'Admin') {
            abort(403);
        }
    }
}
