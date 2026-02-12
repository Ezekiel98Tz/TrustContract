<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\User;
use App\Models\TrustSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'status' => ['nullable', 'in:draft,pending_approval,signed,finalized,cancelled'],
            'initiator_id' => ['nullable', 'integer'],
            'counterparty_id' => ['nullable', 'integer'],
            'verification_status' => ['nullable', 'in:verified,unverified,pending'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Contract::query()->with(['buyer', 'seller', 'signatures']);

        if ($user->role !== 'Admin') {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (!empty($validated['initiator_id'])) {
            $query->where(function ($q) use ($validated) {
                $q->where('buyer_id', $validated['initiator_id'])
                  ->orWhere('seller_id', $validated['initiator_id']);
            });
        }
        if (!empty($validated['counterparty_id'])) {
            $id = $validated['counterparty_id'];
            $query->where(function ($q) use ($id) {
                $q->where('buyer_id', $id)
                  ->orWhere('seller_id', $id);
            });
        }
        if (!empty($validated['verification_status'])) {
            $vs = $validated['verification_status'];
            $query->where(function ($q) use ($vs) {
                $q->whereHas('buyer', function ($qb) use ($vs) { $qb->where('verification_status', $vs); })
                  ->orWhereHas('seller', function ($qs) use ($vs) { $qs->where('verification_status', $vs); });
            });
        }

        $contracts = $query->latest()->paginate($validated['per_page'] ?? 15);
        return response()->json($contracts);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['Buyer', 'Seller'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$user->email_verified_at && !app()->environment('testing')) {
            return response()->json(['message' => 'Email must be verified to create contracts'], 403);
        }

        $required = ['phone', 'country'];
        foreach ($required as $field) {
            if (empty($user->{$field})) {
                return response()->json(['message' => 'Complete your profile before creating contracts', 'missing_field' => $field], 422);
            }
        }
        $completion = $user->profileCompletion();
        $minForContract = $this->minForContract();
        if (($completion['percent'] ?? 0) < $minForContract) {
            return response()->json(['message' => 'Increase profile completeness to create contracts', 'required_percent' => $minForContract], 422);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'deadline_at' => ['nullable', 'date'],
            'counterparty_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $currency = strtoupper($validated['currency'] ?? 'USD');
        $threshold = $this->currencyThreshold($currency);
        if (($validated['price_cents'] ?? 0) >= $threshold) {
            if (!in_array($user->verification_level ?? 'none', ['standard', 'advanced'], true)) {
                return response()->json(['message' => 'Standard verification required for high-value contracts'], 403);
            }
            $minHigh = $this->minForHighValue();
            if (($completion['percent'] ?? 0) < $minHigh) {
                return response()->json(['message' => 'Increase profile completeness to proceed with high-value contracts', 'required_percent' => $minHigh], 422);
            }
        }

        $counterparty = User::findOrFail($validated['counterparty_id']);
        if (!in_array($counterparty->role, ['Buyer', 'Seller'], true)) {
            return response()->json(['message' => 'Counterparty must be Buyer or Seller'], 422);
        }
        if ($counterparty->id === $user->id) {
            return response()->json(['message' => 'Counterparty cannot be yourself'], 422);
        }

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'price_cents' => $validated['price_cents'],
            'currency' => $validated['currency'] ?? 'USD',
            'deadline_at' => $validated['deadline_at'] ?? null,
            'status' => 'draft',
        ];

        if ($user->role === 'Buyer') {
            $data['buyer_id'] = $user->id;
            $data['seller_id'] = $counterparty->id;
        } else {
            $data['seller_id'] = $user->id;
            $data['buyer_id'] = $counterparty->id;
        }

        $contract = Contract::create($data);

        // Invite counterparty
        try {
            $counterparty->notify(new \App\Notifications\ContractInvitationNotification($contract));
        } catch (\Throwable $e) {}

        // Log creation
        try {
            \App\Models\ContractLog::create([
                'contract_id' => $contract->id,
                'actor_id' => $user->id,
                'action' => 'created',
                'from_status' => null,
                'to_status' => 'draft',
                'meta' => [
                    'title' => $contract->title,
                    'price_cents' => $contract->price_cents,
                    'currency' => $contract->currency,
                ],
            ]);
        } catch (\Throwable $e) {}

        return response()->json(['contract' => $contract], 201);
    }

    public function show($id)
    {
        $contract = Contract::with(['buyer', 'seller', 'signatures'])->find($id);
        if (!$contract) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = request()->user();
        if ($user->role !== 'Admin' && $user->id !== $contract->buyer_id && $user->id !== $contract->seller_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $buyerStatus = $contract->buyer?->verification_status;
        $sellerStatus = $contract->seller?->verification_status;
        $disclaimer = null;
        if ($buyerStatus !== 'verified' || $sellerStatus !== 'verified') {
            $disclaimer = 'This agreement includes an unverified user. Platform bears no responsibility in the event of dispute.';
        }

        return response()->json([
            'contract' => $contract,
            'buyer_verification_status' => $buyerStatus,
            'seller_verification_status' => $sellerStatus,
            'verification_disclaimer' => $disclaimer,
        ]);
    }

    public function update(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = $request->user();
        if ($user->role !== 'Admin' && $user->id !== $contract->buyer_id && $user->id !== $contract->seller_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['nullable', 'integer', 'min:1'],
            'currency' => ['nullable', 'string', 'size:3'],
            'deadline_at' => ['nullable', 'date'],
            'submit_for_approval' => ['nullable', 'boolean'],
            'status' => ['nullable', 'in:cancelled'],
        ]);

        $originalStatus = $contract->status;

        // Update editable fields only when draft
        if ($contract->status === 'draft') {
            foreach (['title','description','price_cents','currency','deadline_at'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $contract->{$field} = $validated[$field] ?? $contract->{$field};
                }
            }
        }

        // Submit for approval
        if (($validated['submit_for_approval'] ?? false) && $contract->status === 'draft') {
            $contract->status = 'pending_approval';
        }

        // Cancel
        if (($validated['status'] ?? null) === 'cancelled') {
            $contract->status = 'cancelled';
        }

        $contract->save();

        if ($contract->status !== $originalStatus) {
            \App\Models\ContractLog::create([
                'contract_id' => $contract->id,
                'actor_id' => $user->id,
                'action' => 'status_changed',
                'from_status' => $originalStatus,
                'to_status' => $contract->status,
                'meta' => [],
            ]);
        }

        return response()->json(['contract' => $contract]);
    }

    public function sign(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $user = $request->user();
        if ($user->id !== $contract->buyer_id && $user->id !== $contract->seller_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if (!$user->email_verified_at && !app()->environment('testing')) {
            return response()->json(['message' => 'Email must be verified to sign contracts'], 403);
        }

        $required = ['phone', 'country'];
        foreach ($required as $field) {
            if (empty($user->{$field})) {
                return response()->json(['message' => 'Complete your profile before signing', 'missing_field' => $field], 422);
            }
        }

        $validated = $request->validate([
            'reject' => ['nullable', 'boolean'],
            'fingerprint' => ['nullable', 'string', 'max:255'],
        ]);

        if (($validated['reject'] ?? false)) {
            $original = $contract->status;
            $contract->status = 'cancelled';
            $contract->save();

            \App\Models\ContractLog::create([
                'contract_id' => $contract->id,
                'actor_id' => $user->id,
                'action' => 'status_changed',
                'from_status' => $original,
                'to_status' => 'cancelled',
                'meta' => ['reason' => 'rejected'],
            ]);

            // Notify other party of rejection
            $otherId = $user->id === $contract->buyer_id ? $contract->seller_id : $contract->buyer_id;
            $other = \App\Models\User::find($otherId);
            if ($other) {
                try {
                    $other->notify(new \App\Notifications\ContractRejectedNotification($contract));
                } catch (\Throwable $e) {}
            }

            return response()->json(['contract' => $contract]);
        }

        // Transactional signing to avoid inconsistent states
        $originalStatus = $contract->status;
        DB::beginTransaction();
        try {
            $threshold = $this->currencyThreshold(strtoupper($contract->currency ?? 'USD'));
            if (($contract->price_cents ?? 0) >= $threshold) {
                if (!in_array($user->verification_level ?? 'none', ['standard', 'advanced'], true)) {
                    DB::rollBack();
                    return response()->json(['message' => 'Standard verification required to sign high-value contracts'], 403);
                }
                $completion = $user->profileCompletion();
                $minHigh = $this->minForHighValue();
                if (($completion['percent'] ?? 0) < $minHigh) {
                    DB::rollBack();
                    return response()->json(['message' => 'Increase profile completeness to sign high-value contracts', 'required_percent' => $minHigh], 422);
                }
            }
                $settings = $this->getSettings();
                if ($settings && ($settings->require_business_verification ?? false)) {
                    $business = \App\Models\Business::where('user_id', $user->id)->first();
                    if ($business && $business->verification_status !== 'verified') {
                        DB::rollBack();
                        return response()->json(['message' => 'Business verification required to sign high-value contracts'], 403);
                    }
                }
            $existingSignature = \App\Models\ContractSignature::where('contract_id', $contract->id)
                ->where('user_id', $user->id)
                ->first();


            $now = now();
            // Set acceptance BEFORE recording signature
            if ($user->id === $contract->buyer_id) {
                if (!$contract->buyer_accepted_at) {
                    $contract->buyer_accepted_at = $now;
                }
            } else {
                if (!$contract->seller_accepted_at) {
                    $contract->seller_accepted_at = $now;
                }
            }

            $signature = null;
            if (!$existingSignature) {
                $fingerprint = $validated['fingerprint'] ?? ($request->ip() . '|' . ($request->header('User-Agent') ?? 'unknown'));
                $signature = \App\Models\ContractSignature::create([
                    'contract_id' => $contract->id,
                    'user_id' => $user->id,
                    'signed_at' => $now,
                    'ip_address' => $request->ip(),
                    'device_info' => (string) $request->header('User-Agent'),
                    'fingerprint_hash' => hash('sha256', $fingerprint),
                ]);
            } else {
                // Graceful resume: if signature exists but acceptance was previously missing, proceed; otherwise conflict
                $hasAcceptance = ($user->id === $contract->buyer_id) ? (bool)$contract->buyer_accepted_at : (bool)$contract->seller_accepted_at;
                if ($hasAcceptance) {
                    DB::rollBack();
                    return response()->json(['message' => 'Already signed', 'contract' => $contract], 409);
                }
            }

            // Status transitions
            if ($contract->status === 'draft') {
                $contract->status = 'pending_approval';
            }

            // If both have signed, mark as signed
            $buyerSigned = \App\Models\ContractSignature::where('contract_id', $contract->id)->where('user_id', $contract->buyer_id)->exists();
            $sellerSigned = \App\Models\ContractSignature::where('contract_id', $contract->id)->where('user_id', $contract->seller_id)->exists();
            if ($buyerSigned && $sellerSigned && $contract->buyer_accepted_at && $contract->seller_accepted_at) {
                $contract->status = 'signed';
            }

            $contract->save();

            \App\Models\ContractLog::create([
                'contract_id' => $contract->id,
                'actor_id' => $user->id,
                'action' => $signature ? 'signed' : 'accepted_resumed',
                'from_status' => $originalStatus,
                'to_status' => $contract->status,
                'meta' => ['signature_id' => $signature?->id],
            ]);

            // Notify other party
            $otherId = $user->id === $contract->buyer_id ? $contract->seller_id : $contract->buyer_id;
            $other = \App\Models\User::find($otherId);
            if ($other) {
                try {
                    $role = $user->id === $contract->buyer_id ? 'Buyer' : 'Seller';
                    $other->notify(new \App\Notifications\ContractSignedNotification($contract, $role));
                } catch (\Throwable $e) {}
            }

            // If both signed, generate PDF
            if ($contract->status === 'signed' && !$contract->pdf_path) {
                try {
                    $service = new \App\Services\ContractPdfService();
                    $path = $service->generate($contract);
                    $contract->pdf_path = $path;
                    $contract->save();
                } catch (\Throwable $e) {}
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Signing failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['contract' => $contract]);
    }

    public function finalize(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user = $request->user();
        if ($user->role !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'action' => ['required', 'in:finalize,archive,cancel'],
        ]);

        $original = $contract->status;
        if (in_array($validated['action'], ['finalize', 'archive'], true)) {
            $contract->status = 'finalized';
        } else {
            $contract->status = 'cancelled';
        }
        $contract->save();

        \App\Models\ContractLog::create([
            'contract_id' => $contract->id,
            'actor_id' => $user->id,
            'action' => 'status_changed',
            'from_status' => $original,
            'to_status' => $contract->status,
            'meta' => ['admin_action' => $validated['action']],
        ]);

        // Notify parties
        foreach ([$contract->buyer_id, $contract->seller_id] as $uid) {
            $party = \App\Models\User::find($uid);
            if ($party) {
                try {
                    $party->notify(new \App\Notifications\ContractFinalizedNotification($contract));
                } catch (\Throwable $e) {}
            }
        }

        return response()->json(['contract' => $contract]);
    }

    public function repair(Request $request, $id)
    {
        $contract = Contract::find($id);
        if (!$contract) {
            return response()->json(['message' => 'Not found'], 404);
        }
        $user = $request->user();
        if ($user->role !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $original = $contract->status;
        DB::beginTransaction();
        try {
            $buyerSig = \App\Models\ContractSignature::where('contract_id', $contract->id)
                ->where('user_id', $contract->buyer_id)
                ->orderBy('signed_at')
                ->first();
            $sellerSig = \App\Models\ContractSignature::where('contract_id', $contract->id)
                ->where('user_id', $contract->seller_id)
                ->orderBy('signed_at')
                ->first();

            if (!$contract->buyer_accepted_at && $buyerSig) {
                $contract->buyer_accepted_at = $buyerSig->signed_at;
            }
            if (!$contract->seller_accepted_at && $sellerSig) {
                $contract->seller_accepted_at = $sellerSig->signed_at;
            }

            if ($contract->buyer_accepted_at && $contract->seller_accepted_at && !in_array($contract->status, ['finalized','cancelled'], true)) {
                $contract->status = 'signed';
            }

            $contract->save();

            \App\Models\ContractLog::create([
                'contract_id' => $contract->id,
                'actor_id' => $user->id,
                'action' => 'repaired',
                'from_status' => $original,
                'to_status' => $contract->status,
                'meta' => [],
            ]);

            if ($contract->status === 'signed' && !$contract->pdf_path) {
                try {
                    $service = new \App\Services\ContractPdfService();
                    $contract->pdf_path = $service->generate($contract);
                    $contract->save();
                } catch (\Throwable $e) {}
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Repair failed', 'error' => $e->getMessage()], 500);
        }

        return response()->json(['contract' => $contract]);
    }

    private function getSettings()
    {
        if (!Schema::hasTable('trust_settings')) {
            return null;
        }
        return Cache::remember('trust_settings_first', 60, function () {
            return TrustSetting::first();
        });
    }
    private function currencyThreshold(string $currency): int
    {
        $settings = $this->getSettings();
        if ($settings && is_array($settings->currency_thresholds) && isset($settings->currency_thresholds[$currency])) {
            return (int) $settings->currency_thresholds[$currency];
        }
        $thresholds = (array) config('currency.thresholds_cents', []);
        return (int) ($thresholds[$currency] ?? $thresholds['USD'] ?? 50000);
    }
    private function minForHighValue(): int
    {
        $settings = $this->getSettings();
        if ($settings && $settings->min_for_high_value !== null) {
            return (int) $settings->min_for_high_value;
        }
        return (int) config('trust.profile.min_for_high_value', 80);
    }
    private function minForContract(): int
    {
        $settings = $this->getSettings();
        if ($settings && $settings->min_for_contract !== null) {
            return (int) $settings->min_for_contract;
        }
        return (int) config('trust.profile.min_for_contract', 50);
    }
}
