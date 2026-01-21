<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Contract;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }
        
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $users = $query->latest()->paginate(15);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['role', 'search']),
        ]);
    }

    public function show(User $user)
    {
        // Load contracts where user is buyer or seller
        $contracts = Contract::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with(['buyer', 'seller'])
            ->latest()
            ->get();

        $contractsPayload = $contracts->map(function (Contract $contract) {
            $status = $this->presentStatus($contract->status);

            return [
                'id' => $contract->id,
                'buyer_id' => $contract->buyer_id,
                'seller_id' => $contract->seller_id,
                'title' => $contract->title,
                'created_at' => $contract->created_at,
                'status_label' => $status['label'],
                'status_tone' => $status['tone'],
                'buyer' => $contract->buyer ? [
                    'id' => $contract->buyer->id,
                    'name' => $contract->buyer->name,
                    'email' => $contract->buyer->email,
                ] : null,
                'seller' => $contract->seller ? [
                    'id' => $contract->seller->id,
                    'name' => $contract->seller->name,
                    'email' => $contract->seller->email,
                ] : null,
            ];
        })->values();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'contracts' => $contractsPayload,
            'stats' => [
                'total_contracts' => $contracts->count(),
                'active_contracts' => $contracts->whereIn('status', ['draft', 'pending_approval', 'signed'])->count(),
                'completed_contracts' => $contracts->where('status', 'finalized')->count(),
            ]
        ]);
    }

    public function verify(User $user)
    {
        $user->update(['verification_status' => 'verified']);
        return back()->with('success', 'User verified successfully.');
    }

    public function unverify(User $user)
    {
        $user->update(['verification_status' => 'unverified']);
        return back()->with('success', 'User unverified successfully.');
    }

    private function statusLabel(?string $status): string
    {
        $map = [
            'draft' => 'Draft',
            'pending_approval' => 'Pending approval',
            'signed' => 'Signed',
            'finalized' => 'Finalized',
            'cancelled' => 'Cancelled',
        ];

        return $map[$status] ?? 'Unknown';
    }

    private function presentStatus(?string $status): array
    {
        $toneMap = [
            'finalized' => 'success',
            'signed' => 'info',
            'cancelled' => 'danger',
            'pending_approval' => 'warning',
            'draft' => 'neutral',
        ];

        return [
            'label' => $this->statusLabel($status),
            'tone' => $toneMap[$status] ?? 'neutral',
        ];
    }
}
