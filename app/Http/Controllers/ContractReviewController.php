<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class ContractReviewController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        $user = Auth::user();
        if ($contract->buyer_id !== $user->id && $contract->seller_id !== $user->id) {
            abort(403);
        }

        if (!in_array($contract->status, ['signed','finalized'], true)) {
            return Redirect::back()->with('error', 'You can only review signed or finalized contracts.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $revieweeId = $user->id === $contract->buyer_id ? $contract->seller_id : $contract->buyer_id;

        $existing = ContractReview::where('contract_id', $contract->id)->where('reviewer_id', $user->id)->first();
        if ($existing) {
            return Redirect::back()->with('error', 'You already reviewed this contract.');
        }

        ContractReview::create([
            'contract_id' => $contract->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $revieweeId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return Redirect::route('contracts.show', $contract->id)->with('success', 'Review submitted.');
    }
}
