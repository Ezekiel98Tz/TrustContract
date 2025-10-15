<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use App\Notifications\VerificationReviewedNotification;

class VerificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $status = $request->query('status');
        $query = Verification::query()->with('user')->latest();
        if ($status) {
            $query->where('status', $status);
        }

        $verifications = $query->paginate(15);
        return response()->json($verifications);
    }

    public function submit(Request $request, $id)
    {
        $user = $request->user();
        if ((int) $id !== $user->id && $user->role !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'document' => ['required', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
        ]);

        $path = $validated['document']->store('ids', 'public');

        $verification = Verification::create([
            'user_id' => (int) $id,
            'document_path' => $path,
            'status' => 'pending',
        ]);

        // Update user convenience fields
        User::where('id', (int) $id)->update([
            'id_document_path' => $path,
            'verification_status' => 'pending',
        ]);

        return response()->json(['verification' => $verification], 201);
    }

    public function review(Request $request, $id)
    {
        $admin = $request->user();
        if ($admin->role !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $verification = Verification::find($id);
        if (!$verification) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($verification->status !== 'pending') {
            return response()->json(['message' => 'Verification already reviewed'], 422);
        }

        $verification->status = $validated['status'];
        $verification->reviewed_by = $admin->id;
        $verification->reviewed_at = now();
        $verification->notes = $validated['notes'] ?? null;
        $verification->save();

        // Update user convenience status
        User::where('id', $verification->user_id)->update([
            'verification_status' => $validated['status'] === 'approved' ? 'verified' : 'rejected',
        ]);

        // Notify the user about the decision
        try {
            if ($verification->user) {
                $verification->user->notify(new VerificationReviewedNotification($verification));
            }
        } catch (\Throwable $e) {
            // Do not block primary flow due to notification failures
        }

        return response()->json(['verification' => $verification]);
    }
}