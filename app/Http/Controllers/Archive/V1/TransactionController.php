<?php
/**
 * Archived Controller — Legacy payment logic (disabled as of Oct 2025)
 */

namespace App\Http\Controllers\Archive\V1;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\CommissionRecordedNotification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\TransactionUpdatedNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user || $user->role !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'payer_id' => ['nullable', 'integer'],
            'payee_id' => ['nullable', 'integer'],
            'contract_id' => ['nullable', 'integer'],
            'min_amount' => ['nullable', 'integer', 'min:0'],
            'max_amount' => ['nullable', 'integer', 'min:0'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Transaction::query()->with(['contract', 'payer', 'payee'])->orderByDesc('created_at');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (!empty($validated['payer_id'])) {
            $query->where('payer_id', $validated['payer_id']);
        }
        if (!empty($validated['payee_id'])) {
            $query->where('payee_id', $validated['payee_id']);
        }
        if (!empty($validated['contract_id'])) {
            $query->where('contract_id', $validated['contract_id']);
        }
        if (!empty($validated['min_amount'])) {
            $query->where('amount_cents', '>=', $validated['min_amount']);
        }
        if (!empty($validated['max_amount'])) {
            $query->where('amount_cents', '<=', $validated['max_amount']);
        }
        if (!empty($validated['from'])) {
            $query->where('created_at', '>=', Carbon::parse($validated['from'])->startOfDay());
        }
        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', Carbon::parse($validated['to'])->endOfDay());
        }

        $perPage = $validated['per_page'] ?? 15;
        return response()->json($query->paginate($perPage));
    }
    public function show(Request $request, $id)
    {
        $txn = Transaction::with(['contract', 'payer', 'payee'])->find($id);
        if (!$txn) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = $request->user();
        if ($user->role !== 'Admin' && $user->id !== $txn->payer_id && $user->id !== $txn->payee_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['transaction' => $txn]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payer_id' => ['required', 'integer', 'exists:users,id'],
            'payee_id' => ['required', 'integer', 'exists:users,id'],
            'reference' => ['nullable', 'string', 'max:255'],
        ]);

        $contract = Contract::findOrFail($validated['contract_id']);

        // Ensure this transaction belongs to either buyer or seller
        if (!in_array($validated['payer_id'], [$contract->buyer_id, $contract->seller_id], true) ||
            !in_array($validated['payee_id'], [$contract->buyer_id, $contract->seller_id], true)
        ) {
            return response()->json(['message' => 'Payer/Payee must be contract participants'], 422);
        }

        $txn = Transaction::create([
            'contract_id' => $contract->id,
            'payer_id' => $validated['payer_id'],
            'payee_id' => $validated['payee_id'],
            'amount_cents' => $validated['amount_cents'],
            'currency' => $validated['currency'] ?? 'USD',
            'status' => 'pending',
            'reference' => $validated['reference'] ?? null,
        ]);

        return response()->json(['transaction' => $txn], 201);
    }

    public function update(Request $request, $id)
    {
        $txn = Transaction::find($id);
        if (!$txn) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = $request->user();
        if ($user->role !== 'Admin' && $user->id !== $txn->payer_id && $user->id !== $txn->payee_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:paid,failed,refunded'],
        ]);

        $target = $validated['status'];

        // Enforce lifecycle rules
        if ($target === 'paid') {
            if ($user->id !== $txn->payer_id) {
                return response()->json(['message' => 'Only payer can mark as paid'], 403);
            }
            if ($txn->status !== 'pending') {
                return response()->json(['message' => 'Only pending transactions can be paid'], 422);
            }
            $txn->status = 'paid';
        } elseif ($target === 'failed') {
            if ($user->role !== 'Admin') {
                return response()->json(['message' => 'Only admin can mark as failed'], 403);
            }
            if ($txn->status !== 'pending') {
                return response()->json(['message' => 'Only pending transactions can be failed'], 422);
            }
            $txn->status = 'failed';
        } elseif ($target === 'refunded') {
            if ($user->role !== 'Admin') {
                return response()->json(['message' => 'Only admin can refund'], 403);
            }
            if ($txn->status !== 'paid') {
                return response()->json(['message' => 'Only paid transactions can be refunded'], 422);
            }
            $txn->status = 'refunded';
        }

        // Capture original status BEFORE saving to detect actual changes
        $originalStatus = $txn->getOriginal('status');
        $txn->save();

        // Attempt to notify stakeholders about the status change
        try {
            // If the status actually changed, notify relevant parties
            if ($txn->status !== $originalStatus) {
                if ($txn->status === 'paid') {
                    if ($txn->payee) {
                        $txn->payee->notify(new TransactionUpdatedNotification($txn));
                    }
                } elseif (in_array($txn->status, ['failed', 'refunded'])) {
                    if ($txn->payer) {
                        $txn->payer->notify(new TransactionUpdatedNotification($txn));
                    }
                    if ($txn->payee) {
                        $txn->payee->notify(new TransactionUpdatedNotification($txn));
                    }
                }
            }
        } catch (\Throwable $e) {
            // Do not block primary flow due to notification failures
        }

        return response()->json(['transaction' => $txn]);
    }

    /**
     * Initiate a Flutterwave payment for a pending transaction.
     * Returns a hosted payment link for the buyer.
     */
    public function pay(Request $request, int $id)
    {
        $txn = Transaction::with(['contract', 'payer', 'payee'])->find($id);
        if (!$txn) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = $request->user();
        if ($user->id !== $txn->payer_id) {
            return response()->json(['message' => 'Only payer can initiate payment'], 403);
        }

        if ($txn->status !== 'pending') {
            return response()->json(['message' => 'Only pending transactions can be paid'], 422);
        }

        $rate = (float) config('flutterwave.commission_rate', 0.05);
        $commission = (int) ceil($txn->amount_cents * $rate);
        $charged = (int) ($txn->amount_cents + $commission);

        // archived: removed persistence of provider/fee fields
        // $txn->save();

        $txRef = 'TC-' . $txn->id . '-' . time();

        // Prepare Flutterwave payment initiation
        $baseUrl = rtrim((string) config('flutterwave.base_url'), '/');
        $secretKey = (string) config('flutterwave.secret_key');
        if (!$secretKey) {
            return response()->json(['message' => 'Flutterwave secret key not configured'], 500);
        }

        $amountFloat = round($charged / 100, 2);
        $payload = [
            'tx_ref' => $txRef,
            'amount' => $amountFloat,
            'currency' => $txn->currency,
            'redirect_url' => url('/payment/callback'),
            'customer' => [
                'email' => $txn->payer?->email,
                'name' => $txn->payer?->name,
            ],
            'meta' => [
                'transaction_id' => $txn->id,
                'contract_id' => $txn->contract_id,
                'test_mode' => (bool) config('flutterwave.test_mode'),
            ],
            'customizations' => [
                'title' => 'TrustContract Payment',
                'description' => 'Payment for contract #' . $txn->contract_id,
            ],
        ];

        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->post($baseUrl . '/v3/payments', $payload);

            if (!$response->ok()) {
                Log::warning('Flutterwave payment initiation failed', [
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);
                return response()->json([
                    'message' => 'Failed to initiate payment',
                    'details' => $response->json(),
                ], 502);
            }

            $data = $response->json('data');
            $link = $data['link'] ?? null;
            if (!$link) {
                return response()->json([
                    'message' => 'Payment link not provided',
                    'details' => $data,
                ], 502);
            }

            return response()->json([
                'payment_link' => $link,
                'transaction' => $txn,
            ]);
        } catch (\Throwable $e) {
            Log::error('Flutterwave API error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Flutterwave API error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}