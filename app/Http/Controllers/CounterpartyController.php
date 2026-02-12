<?php

namespace App\Http\Controllers;

use App\Models\ContractReview;
use App\Models\Transaction;
use App\Models\Dispute;
use App\Models\WebhookEvent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CounterpartyController extends Controller
{
    public function search(Request $request)
    {
        $q = (string) $request->query('q', '');
        $role = (string) $request->query('role', '');
        $op = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $users = User::query()
            ->when($q !== '', function ($query) use ($q, $op) {
                $query->where(function ($q2) use ($q, $op) {
                    $q2->where('name', $op, "%{$q}%")
                       ->orWhere('email', $op, "%{$q}%");
                });
            })
            ->when(in_array($role, ['Buyer','Seller'], true), function ($query) use ($role) {
                $query->where('role', $role);
            })
            ->where('id', '!=', $request->user()->id)
            ->take(10)
            ->get(['id','name','email','role','verification_status','verification_level']);

        $ids = $users->pluck('id')->all();
        $agg = collect([]);
        if (!empty($ids)) {
            $agg = ContractReview::selectRaw('contract_reviews.reviewee_id, AVG(contract_reviews.rating) as avg_rating, COUNT(*) as cnt')
                ->join('contracts', 'contracts.id', '=', 'contract_reviews.contract_id')
                ->whereIn('contract_reviews.reviewee_id', $ids)
                ->where('contracts.status', 'finalized')
                ->groupBy('contract_reviews.reviewee_id')
                ->get()
                ->keyBy('reviewee_id');
        }
        $payload = $users->map(function ($u) use ($agg) {
            $row = $agg->get($u->id);
            $avg = $row ? (float) $row->avg_rating : null;
            $count = $row ? (int) $row->cnt : 0;
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
        $avg = ContractReview::where('reviewee_id', $user->id)->whereHas('contract', function ($q) {
            $q->where('status', 'finalized');
        })->avg('rating');
        $count = ContractReview::where('reviewee_id', $user->id)->whereHas('contract', function ($q) {
            $q->where('status', 'finalized');
        })->count();
        $recentReviews = ContractReview::where('reviewee_id', $user->id)->with(['reviewer'])->latest()->limit(5)->get();
        $recentContracts = \App\Models\Contract::where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            })->whereIn('status', ['signed','finalized'])->latest()->limit(5)->get(['id','title','created_at','price_cents','currency','status']);

        $txns = Transaction::where(function ($q) use ($user) {
                $q->where('payer_id', $user->id)->orWhere('payee_id', $user->id);
            })->get(['id','status']);
        $totalTxn = $txns->count();
        $paidCount = $txns->where('status', 'paid')->count();
        $failedCount = $txns->where('status', 'failed')->count();
        $txnIds = $txns->pluck('id')->all();
        $disputeCount = 0;
        if (!empty($txnIds)) {
            $disputeCount = Dispute::whereIn('transaction_id', $txnIds)->count();
            if ($disputeCount === 0) {
                $disputeCount = WebhookEvent::whereIn('transaction_id', $txnIds)
                    ->where('event_id', 'ilike', '%dispute%')
                    ->count();
            }
        }
        $denom = max($paidCount + $failedCount, 1);
        $disputeRate = round(($disputeCount / $denom) * 100, 1);

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
                'total_txn' => $totalTxn,
                'paid_count' => $paidCount,
                'failed_count' => $failedCount,
                'dispute_count' => $disputeCount,
                'dispute_rate' => $disputeRate,
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
