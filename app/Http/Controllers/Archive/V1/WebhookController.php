<?php
/**
 * Archived Controller — Legacy payment logic (disabled as of Oct 2025)
 */

namespace App\Http\Controllers\Archive\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Notifications\TransactionUpdatedNotification;
use App\Notifications\CommissionRecordedNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    /**
     * Simulate provider webhook delivery to update transaction status.
     */
    public function simulate(Request $request, string $provider)
    {
        if (!config('webhooks.simulation_enabled')) {
            return response()->json([
                'ok' => false,
                'error' => 'Webhook simulation disabled',
            ], 403);
        }

        $sharedSecret = config('webhooks.shared_secret');
        $providedSecret = $request->header('X-TC-Webhook-Secret', $request->query('secret'));
        if (!$sharedSecret || $providedSecret !== $sharedSecret) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid webhook secret',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'event_type' => 'required|string',
            'event_id' => 'required|string',
            'transaction_id' => 'required|integer',
            'amount_cents' => 'nullable|integer',
            'currency' => 'nullable|string',
            'reason' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $payload = $validator->validated();
        $eventType = $payload['event_type'];
        $eventId = $payload['event_id'];
        $transactionId = (int) $payload['transaction_id'];

        // Idempotency: skip if we've already processed this event
        $existing = WebhookEvent::where('provider', $provider)
            ->where('event_id', $eventId)
            ->first();
        if ($existing) {
            return response()->json([
                'ok' => true,
                'duplicate' => true,
                'message' => 'Event already processed',
                'provider' => $provider,
                'event_id' => $eventId,
            ]);
        }

        $statusMap = (array) (config('webhooks.status_map')[$provider] ?? []);
        $mappedStatus = $statusMap[$eventType] ?? null;

        if (!$mappedStatus) {
            return response()->json([
                'ok' => false,
                'error' => 'Unsupported event_type for provider',
                'provider' => $provider,
                'event_type' => $eventType,
            ], 400);
        }

        try {
            $transaction = Transaction::with(['contract', 'payer', 'payee'])->findOrFail($transactionId);
            $originalStatus = $transaction->status;
            $statusChanged = $originalStatus !== $mappedStatus;

            $transaction->status = $mappedStatus;
            $transaction->save();

            // Notify parties only if status actually changed
            if ($statusChanged) {
                try {
                    if ($transaction->payer) {
                        $transaction->payer->notify(new TransactionUpdatedNotification($transaction));
                    }
                    if ($transaction->payee) {
                        $transaction->payee->notify(new TransactionUpdatedNotification($transaction));
                    }
                } catch (\Throwable $notifyErr) {
                    Log::warning('Webhook notification dispatch error', [
                        'error' => $notifyErr->getMessage(),
                        'transaction_id' => $transactionId,
                    ]);
                }
            }

            WebhookEvent::create([
                'provider' => $provider,
                'event_id' => $eventId,
                'transaction_id' => $transactionId,
                'status' => 'processed',
                'message' => $statusChanged ? 'Transaction status updated' : 'No change',
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            return response()->json([
                'ok' => true,
                'provider' => $provider,
                'event_id' => $eventId,
                'transaction_id' => $transactionId,
                'old_status' => $originalStatus,
                'new_status' => $mappedStatus,
                'status_changed' => $statusChanged,
            ]);
        } catch (\Throwable $e) {
            Log::error('Webhook simulation error', [
                'error' => $e->getMessage(),
                'provider' => $provider,
                'event_id' => $eventId,
                'transaction_id' => $transactionId,
            ]);

            WebhookEvent::create([
                'provider' => $provider,
                'event_id' => $eventId,
                'transaction_id' => $transactionId,
                'status' => 'error',
                'message' => $e->getMessage(),
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'Webhook processing failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Flutterwave webhook handler (real provider).
     * Verifies signature and updates transaction state accordingly.
     */
    public function flutterwave(Request $request)
    {
        $secretHash = (string) config('flutterwave.secret_hash');
        $headerHash = (string) $request->header('verif-hash');

        if (!$secretHash || $headerHash !== $secretHash) {
            return response()->json(['ok' => false, 'error' => 'Invalid signature'], 403);
        }

        $payload = $request->json()->all();
        $event = $payload['event'] ?? null; // e.g., charge.completed, refund.processed
        $data = $payload['data'] ?? [];
        $eventId = (string) ($data['id'] ?? ($event ? ($event . '_' . ($data['tx_ref'] ?? 'unknown')) : uniqid('fw_', true)));

        // Idempotency check
        $existing = WebhookEvent::where('provider', 'flutterwave')
            ->where('event_id', $eventId)
            ->first();
        if ($existing) {
            return response()->json(['ok' => true, 'duplicate' => true, 'message' => 'Event already processed']);
        }

        // archived: provider lookup removed
        $txn = null;

        if (!$txn) {
            // Log the event even if no transaction found
            WebhookEvent::create([
                'provider' => 'flutterwave',
                'event_id' => $eventId,
                'transaction_id' => null,
                'status' => 'error',
                'message' => 'Transaction not found for tx_ref',
                'payload' => $payload,
                'processed_at' => now(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Transaction not found'], 404);
        }

        // Determine mapped status
        $mappedStatus = null;
        $dataStatus = $data['status'] ?? null; // successful | failed | reversed | etc.
        if ($event === 'charge.completed') {
            $mappedStatus = ($dataStatus === 'successful') ? 'paid' : 'failed';
        } elseif ($event === 'refund.processed') {
            $mappedStatus = 'refunded';
        } elseif (is_string($event) && str_contains($event, 'dispute')) {
            // DB enum does not include 'disputed'; record as 'failed'.
            $mappedStatus = 'failed';
        } else {
            // Fallback to status value
            $mappedStatus = match ($dataStatus) {
                'successful' => 'paid',
                'failed', 'declined', 'cancelled' => 'failed',
                'reversed' => 'refunded',
                default => null,
            };
        }

        if (!$mappedStatus) {
            WebhookEvent::create([
                'provider' => 'flutterwave',
                'event_id' => $eventId,
                'transaction_id' => $txn->id,
                'status' => 'error',
                'message' => 'Unrecognized event/status',
                'payload' => $payload,
                'processed_at' => now(),
            ]);
            return response()->json(['ok' => false, 'error' => 'Unsupported event/status'], 400);
        }

        $originalStatus = $txn->status;
        $statusChanged = $originalStatus !== $mappedStatus;

        // Apply updates
        $txn->status = $mappedStatus;
        $txn->save();

        // Record dispute entity for dispute events
        if (is_string($event) && str_contains($event, 'dispute')) {
            try {
                \App\Models\Dispute::firstOrCreate(
                    [
                        'transaction_id' => $txn->id,
                        'external_event_id' => (string) $eventId,
                        'provider' => 'flutterwave',
                    ],
                    [
                        'contract_id' => $txn->contract_id,
                        'initiator_id' => null,
                        'status' => 'open',
                        'reason' => $data['reason'] ?? null,
                    ]
                );
            } catch (\Throwable $e) {
                Log::warning('Failed to create dispute record', ['error' => $e->getMessage(), 'transaction_id' => $txn->id]);
            }
        }

        // Notify buyer and seller if status changed
        if ($statusChanged) {
            try {
                if ($txn->payer) {
                    $txn->payer->notify(new TransactionUpdatedNotification($txn));
                }
                if ($txn->payee) {
                    $txn->payee->notify(new TransactionUpdatedNotification($txn));
                }
            } catch (\Throwable $notifyErr) {
                Log::warning('Webhook notification dispatch error', [
                    'error' => $notifyErr->getMessage(),
                    'transaction_id' => $txn->id,
                ]);
            }
        }

        // Notify admins about commission when paid
        try {
            $admins = User::where('role', 'Admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new CommissionRecordedNotification($txn));
            }
        } catch (\Throwable $e) {
            Log::warning('Admin commission notification error', ['error' => $e->getMessage()]);
        }
        }

        // Log webhook event
        WebhookEvent::create([
            'provider' => 'flutterwave',
            'event_id' => $eventId,
            'transaction_id' => $txn->id,
            'status' => 'processed',
            'message' => $statusChanged ? 'Transaction status updated' : 'No change',
            'payload' => $payload,
            'processed_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'provider' => 'flutterwave',
            'event' => $event,
            'event_id' => $eventId,
            'transaction_id' => $txn->id,
            'old_status' => $originalStatus,
            'new_status' => $mappedStatus,
            'status_changed' => $statusChanged,
        ]);
    }
}
