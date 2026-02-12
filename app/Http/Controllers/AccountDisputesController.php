<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountDisputesController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $status = (string) $request->query('status', '');
        $query = Dispute::query()
            ->with(['contract', 'initiator', 'mediator'])
            ->where(function ($q) use ($user) {
                $q->where('initiator_id', $user->id)
                  ->orWhereHas('contract', function ($qc) use ($user) {
                      $qc->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
                  });
            })
            ->orderByDesc('created_at');
        if (in_array($status, ['open','mediate','resolved','cancelled'], true)) {
            $query->where('status', $status);
        }
        $disputes = $query->paginate(10)->appends(['status' => $status]);
        return Inertia::render('Account/Disputes', [
            'disputes' => $disputes,
            'filters' => ['status' => $status ?: null],
        ]);
    }
}
