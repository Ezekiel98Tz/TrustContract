<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Dispute;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisputeController extends Controller
{
    public function store(Request $request, Contract $contract)
    {
        $user = Auth::user();
        if ($contract->buyer_id !== $user->id && $contract->seller_id !== $user->id && $user->role !== 'Admin') {
            abort(403);
        }
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:2000'],
        ]);
        $txn = Transaction::where('contract_id', $contract->id)->latest()->first();
        $dispute = Dispute::create([
            'transaction_id' => $txn?->id,
            'contract_id' => $contract->id,
            'initiator_id' => $user->id,
            'provider' => 'internal',
            'external_event_id' => null,
            'status' => 'open',
            'reason' => $data['reason'],
        ]);
        // Notify counterparty
        try {
            $otherId = $user->id === $contract->buyer_id ? $contract->seller_id : $contract->buyer_id;
            $other = \App\Models\User::find($otherId);
            if ($other) {
                $other->notify(new \App\Notifications\DisputeCreatedNotification($dispute));
            }
        } catch (\Throwable $e) {}
        return redirect()->route('contracts.show', $contract->id)->with('success', 'Issue reported. We recorded your dispute for follow-up.');
    }
}
