<?php

namespace App\Http\Controllers;

use App\Models\ContractReview;
use App\Models\User;
use Illuminate\Http\Request;

class CounterpartyController extends Controller
{
    public function search(Request $request)
    {
        $q = (string) $request->query('q', '');
        $role = (string) $request->query('role', '');
        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('name', 'ilike', "%{$q}%")
                       ->orWhere('email', 'ilike', "%{$q}%");
                });
            })
            ->when(in_array($role, ['Buyer','Seller'], true), function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->where('id', '!=', $request->user()->id)
            ->take(10)
            ->get(['id','name','email','role','verification_status','verification_level']);

        $payload = $users->map(function ($u) {
            $avg = ContractReview::where('reviewee_id', $u->id)->avg('rating');
            $count = ContractReview::where('reviewee_id', $u->id)->count();
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'verification_status' => $u->verification_status,
                'verification_level' => $u->verification_level ?? 'none',
                'rating_avg' => $avg ? round($avg, 2) : null,
                'rating_count' => $count,
            ];
        });

        return response()->json(['results' => $payload]);
    }

    public function insights(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $avg = ContractReview::where('reviewee_id', $user->id)->avg('rating');
        $count = ContractReview::where('reviewee_id', $user->id)->count();
        $recentReviews = ContractReview::where('reviewee_id', $user->id)->with(['reviewer'])->latest()->limit(5)->get();
        $recentContracts = \App\Models\Contract::where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })->whereIn('status', ['signed','finalized'])->latest()->limit(5)->get(['id','title','created_at','price_cents','currency','status']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'verification_status' => $user->verification_status,
                'verification_level' => $user->verification_level ?? 'none',
                'rating_avg' => $avg ? round($avg, 1) : null,
                'rating_count' => $count,
            ],
            'recent_reviews' => $recentReviews->map(function ($rv) {
                return [
                    'id' => $rv->id,
                    'rating' => $rv->rating,
                    'comment' => $rv->comment,
                    'created_at' => $rv->created_at,
                    'reviewer' => $rv->reviewer ? ['id' => $rv->reviewer->id, 'name' => $rv->reviewer->name] : null,
                ];
            })->values(),
            'recent_contracts' => $recentContracts->map(function ($c) {
                return [
                    'id' => $c->id,
                    'title' => $c->title,
                    'status' => $c->status,
                    'price_cents' => $c->price_cents,
                    'currency' => $c->currency,
                    'created_at' => $c->created_at,
                ];
            })->values(),
        ]);
    }
}
