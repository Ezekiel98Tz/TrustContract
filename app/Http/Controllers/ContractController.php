<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\User;
use App\Models\TrustSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = Contract::query()
            ->with(['buyer', 'seller'])
            ->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)
                  ->orWhere('seller_id', $user->id);
            });

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $contracts = $query->latest()->paginate(10)->through(function (Contract $contract) {
            $status = $this->presentStatus($contract->status);

            return [
                'id' => $contract->id,
                'buyer_id' => $contract->buyer_id,
                'seller_id' => $contract->seller_id,
                'title' => $contract->title,
                'price_cents' => $contract->price_cents,
                'currency' => $contract->currency,
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
        });

        return Inertia::render('Contracts/Index', [
            'contracts' => $contracts,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        $settings = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('trust_settings')) {
            $settings = \App\Models\TrustSetting::first();
        }
        $thresholds = $settings && is_array($settings->currency_thresholds)
            ? $settings->currency_thresholds
            : (array) config('currency.thresholds_cents', []);
        $currencies = array_keys($thresholds);
        $minHigh = $settings && $settings->min_for_high_value !== null
            ? (int) $settings->min_for_high_value
            : (int) config('trust.profile.min_for_high_value', 80);
        return Inertia::render('Contracts/Create', [
            'users' => [],
            'currencies' => $currencies,
            'currency_thresholds' => $thresholds,
            'min_for_high_value' => $minHigh,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->email_verified_at) {
            return back()->withErrors(['email' => 'Verify your email before creating contracts.']);
        }
        if (empty($user->phone) || empty($user->country)) {
            return back()->withErrors(['profile' => 'Complete your profile (phone, country) before creating contracts.'])
                ->with('redirect_to_profile', true);
        }
        
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_cents' => ['required', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'size:3'],
            'deadline_at' => ['nullable', 'date'],
            'counterparty_id' => ['required', 'exists:users,id'],
        ]);

        $currency = strtoupper($validated['currency'] ?? 'USD');
        $threshold = $this->currencyThreshold($currency);
        if (($validated['price_cents'] ?? 0) >= $threshold) {
            if (!in_array($user->verification_level ?? 'none', ['standard', 'advanced'], true)) {
                return back()->withErrors(['verification' => 'Standard verification required for high-value contracts.']);
            }
            $completion = $user->profileCompletion();
            $minHigh = $this->minForHighValue();
            if (($completion['percent'] ?? 0) < $minHigh) {
                return back()->withErrors([
                    'profile' => 'Increase profile completeness to proceed with high-value contracts.',
                ])->with('redirect_to_profile', true);
            }
        } else {
            $completionBase = $user->profileCompletion();
            if (($completionBase['percent'] ?? 0) < $this->minForContract()) {
                return back()->withErrors(['profile' => 'Increase profile completeness to create contracts.'])->with('redirect_to_profile', true);
            }
        }

        if ($validated['counterparty_id'] == $user->id) {
            return back()->withErrors(['counterparty_id' => 'You cannot trade with yourself.']);
        }

        $contract = new Contract([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'price_cents' => $validated['price_cents'],
            'deadline_at' => $validated['deadline_at'] ?? null,
            'currency' => strtoupper($validated['currency'] ?? 'USD'),
            'status' => 'draft',
        ]);

        // Determine who is buyer/seller based on current user role
        // For simplicity, if current user is Buyer, they are Buyer.
        // If current user is Seller, they are Seller.
        // If role is generic or both, we default to initiator is Buyer?
        // Let's assume the user selects their role in the contract or we infer it.
        // Based on API controller logic:
        
        if ($user->role === 'Buyer') {
            $contract->buyer_id = $user->id;
            $contract->seller_id = $validated['counterparty_id'];
        } else {
            $contract->seller_id = $user->id;
            $contract->buyer_id = $validated['counterparty_id'];
        }

        $contract->save();

        return redirect()->route('contracts.show', $contract->id)->with('success', 'Contract created successfully.');
    }

    public function show(Contract $contract)
    {
        $user = Auth::user();

        // Authorization check
        if ($contract->buyer_id !== $user->id && $contract->seller_id !== $user->id && $user->role !== 'Admin') {
            abort(403);
        }

        $contract->load(['buyer', 'seller', 'signatures.user', 'logs.actor']);
        $buyerRecentReviews = \App\Models\ContractReview::where('reviewee_id', $contract->buyer_id)->with(['reviewer'])->latest()->limit(5)->get();
        $sellerRecentReviews = \App\Models\ContractReview::where('reviewee_id', $contract->seller_id)->with(['reviewer'])->latest()->limit(5)->get();
        $buyerHistory = \App\Models\Contract::where('buyer_id', $contract->buyer_id)->where('status', 'finalized')->latest()->limit(5)->get(['id','title','created_at','price_cents','currency']);
        $sellerHistory = \App\Models\Contract::where('seller_id', $contract->seller_id)->where('status', 'finalized')->latest()->limit(5)->get(['id','title','created_at','price_cents','currency']);
        $ratings = [
            'buyer_avg' => \App\Models\ContractReview::where('reviewee_id', $contract->buyer_id)->avg('rating'),
            'buyer_count' => \App\Models\ContractReview::where('reviewee_id', $contract->buyer_id)->count(),
            'seller_avg' => \App\Models\ContractReview::where('reviewee_id', $contract->seller_id)->avg('rating'),
            'seller_count' => \App\Models\ContractReview::where('reviewee_id', $contract->seller_id)->count(),
        ];
        $myReviewExists = \App\Models\ContractReview::where('contract_id', $contract->id)->where('reviewer_id', $user->id)->exists();
        $reviews = \App\Models\ContractReview::where('contract_id', $contract->id)
            ->with(['reviewer'])
            ->orderByDesc('created_at')
            ->get();

        $status = $this->presentStatus($contract->status);
        $hasSigned = $contract->signatures->contains('user_id', $user->id);
        $isParty = $user->id === $contract->buyer_id || $user->id === $contract->seller_id;
        $canSign = $isParty
            && !$hasSigned
            && in_array($contract->status, ['draft', 'pending_approval'], true)
            && !in_array($contract->status, ['finalized', 'cancelled'], true);

        $threshold = $this->currencyThreshold(strtoupper($contract->currency ?? 'USD'));
        $isHighValue = ($contract->price_cents ?? 0) >= $threshold;
        return Inertia::render('Contracts/Show', [
            'contract' => [
                'id' => $contract->id,
                'buyer_id' => $contract->buyer_id,
                'seller_id' => $contract->seller_id,
                'title' => $contract->title,
                'description' => $contract->description,
                'price_cents' => $contract->price_cents,
                'currency' => $contract->currency,
                'deadline_at' => $contract->deadline_at,
                'created_at' => $contract->created_at,
                'status_label' => $status['label'],
                'status_tone' => $status['tone'],
                'high_value' => $isHighValue,
                'high_value_threshold_cents' => $threshold,
            'buyer' => $contract->buyer ? [
                    'id' => $contract->buyer->id,
                    'name' => $contract->buyer->name,
                    'email' => $contract->buyer->email,
                    'verification_status' => $contract->buyer->verification_status,
                    'verification_level' => $contract->buyer->verification_level ?? 'none',
                    'rating_avg' => $ratings['buyer_avg'] ? round($ratings['buyer_avg'], 1) : null,
                    'rating_count' => $ratings['buyer_count'],
                ] : null,
                'seller' => $contract->seller ? [
                    'id' => $contract->seller->id,
                    'name' => $contract->seller->name,
                    'email' => $contract->seller->email,
                    'verification_status' => $contract->seller->verification_status,
                    'verification_level' => $contract->seller->verification_level ?? 'none',
                    'rating_avg' => $ratings['seller_avg'] ? round($ratings['seller_avg'], 1) : null,
                    'rating_count' => $ratings['seller_count'],
                ] : null,
                'signatures' => $contract->signatures
                    ->map(function ($signature) {
                        return [
                            'id' => $signature->id,
                            'user_id' => $signature->user_id,
                            'signed_at' => $signature->signed_at,
                            'user' => $signature->user ? [
                                'id' => $signature->user->id,
                                'name' => $signature->user->name,
                                'email' => $signature->user->email,
                            ] : null,
                        ];
                    })
                    ->values(),
                'logs' => $contract->logs
                    ->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'action_label' => $this->actionLabel($log->action),
                            'actor' => $log->actor ? [
                                'id' => $log->actor->id,
                                'name' => $log->actor->name,
                                'email' => $log->actor->email,
                            ] : null,
                            'from_status_label' => $log->from_status ? $this->statusLabel($log->from_status) : null,
                            'to_status_label' => $log->to_status ? $this->statusLabel($log->to_status) : null,
                            'created_at' => $log->created_at,
                        ];
                    })
                    ->values(),
                'reviews' => $reviews->map(function ($rv) {
                    return [
                        'id' => $rv->id,
                        'reviewer_id' => $rv->reviewer_id,
                        'reviewer' => $rv->reviewer ? [
                            'id' => $rv->reviewer->id,
                            'name' => $rv->reviewer->name,
                            'email' => $rv->reviewer->email,
                        ] : null,
                        'reviewee_id' => $rv->reviewee_id,
                        'rating' => $rv->rating,
                        'comment' => $rv->comment,
                        'created_at' => $rv->created_at,
                    ];
                })->values(),
            ],
            'auth' => [
                'user' => $user,
            ],
            'isBuyer' => $user->id === $contract->buyer_id,
            'isSeller' => $user->id === $contract->seller_id,
            'canSign' => $canSign,
            'canReview' => in_array($contract->status, ['signed','finalized'], true) && !$myReviewExists && ($user->id === $contract->buyer_id || $user->id === $contract->seller_id),
            'downloadable' => in_array($contract->status, ['signed','finalized'], true),
            'parties' => [
                'buyer' => [
                    'recent_reviews' => $buyerRecentReviews->map(function ($rv) {
                        return [
                            'id' => $rv->id,
                            'rating' => $rv->rating,
                            'comment' => $rv->comment,
                            'created_at' => $rv->created_at,
                            'reviewer' => $rv->reviewer ? ['id' => $rv->reviewer->id, 'name' => $rv->reviewer->name] : null,
                        ];
                    })->values(),
                    'recent_history' => $buyerHistory->map(function ($h) {
                        return [
                            'id' => $h->id,
                            'title' => $h->title,
                            'price_cents' => $h->price_cents,
                            'currency' => $h->currency,
                            'created_at' => $h->created_at,
                        ];
                    })->values(),
                ],
                'seller' => [
                    'recent_reviews' => $sellerRecentReviews->map(function ($rv) {
                        return [
                            'id' => $rv->id,
                            'rating' => $rv->rating,
                            'comment' => $rv->comment,
                            'created_at' => $rv->created_at,
                            'reviewer' => $rv->reviewer ? ['id' => $rv->reviewer->id, 'name' => $rv->reviewer->name] : null,
                        ];
                    })->values(),
                    'recent_history' => $sellerHistory->map(function ($h) {
                        return [
                            'id' => $h->id,
                            'title' => $h->title,
                            'price_cents' => $h->price_cents,
                            'currency' => $h->currency,
                            'created_at' => $h->created_at,
                        ];
                    })->values(),
                ],
            ],
        ]);
    }

    public function sign(Request $request, Contract $contract)
    {
        $user = Auth::user();

        if ($contract->buyer_id !== $user->id && $contract->seller_id !== $user->id) {
            abort(403);
        }

        if (!$user->email_verified_at) {
            return back()->with('error', 'Verify your email before signing.');
        }
        if (empty($user->phone) || empty($user->country)) {
            return back()->with('error', 'Complete your profile (phone, country) before signing.');
        }

        if ($contract->status === 'finalized' || $contract->status === 'cancelled') {
            return back()->with('error', 'Cannot sign a finalized or cancelled contract.');
        }

        // Check if already signed
        $hasSigned = \App\Models\ContractSignature::where('contract_id', $contract->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasSigned) {
            return back()->with('error', 'You have already signed this contract.');
        }

        // Transactional signing
        $originalStatus = $contract->status;
        DB::beginTransaction();
        try {
            $threshold = $this->currencyThreshold(strtoupper($contract->currency ?? 'USD'));
            if (($contract->price_cents ?? 0) >= $threshold) {
                if (!in_array($user->verification_level ?? 'none', ['standard', 'advanced'], true)) {
                    DB::rollBack();
                    return back()->with('error', 'Standard verification required to sign high-value contracts.');
                }
                $completion = $user->profileCompletion();
                $minHigh = $this->minForHighValue();
                if (($completion['percent'] ?? 0) < $minHigh) {
                    DB::rollBack();
                    return back()->with('error', 'Increase profile completeness to sign high-value contracts.');
                }
                $settings = null;
                if (\Illuminate\Support\Facades\Schema::hasTable('trust_settings')) {
                    $settings = \App\Models\TrustSetting::first();
                }
                if ($settings && ($settings->require_business_verification ?? false)) {
                    $business = \App\Models\Business::where('user_id', $user->id)->first();
                    if ($business && $business->verification_status !== 'verified') {
                        DB::rollBack();
                        return back()->with('error', 'Business verification required to sign high-value contracts.');
                    }
                }
            }
            $now = now();
            // Set acceptance
            if ($user->id === $contract->buyer_id) {
                if (!$contract->buyer_accepted_at) {
                    $contract->buyer_accepted_at = $now;
                }
            } else {
                if (!$contract->seller_accepted_at) {
                    $contract->seller_accepted_at = $now;
                }
            }

            // Create Signature
            $fingerprint = $request->ip() . '|' . ($request->header('User-Agent') ?? 'unknown');
            $signature = \App\Models\ContractSignature::create([
                'contract_id' => $contract->id,
                'user_id' => $user->id,
                'signed_at' => $now,
                'ip_address' => $request->ip(),
                'device_info' => (string) $request->header('User-Agent'),
                'fingerprint_hash' => hash('sha256', $fingerprint),
            ]);

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
                'action' => 'signed',
                'from_status' => $originalStatus,
                'to_status' => $contract->status,
                'meta' => ['signature_id' => $signature->id],
            ]);

            // Notify other party (Optional but recommended)
            // ... (Notification logic can go here)

            DB::commit();
            
            if ($contract->status === 'signed') {
                return redirect()->route('contracts.print', $contract->id)->with('success', 'Both parties have signed. You can print or save the agreement now.');
            } else {
                return back()->with('success', 'Signed successfully! Now waiting for the other party to sign.');
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Signing failed: ' . $e->getMessage());
        }
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

    private function actionLabel(?string $action): string
    {
        $map = [
            'created' => 'Created',
            'signed' => 'Signed',
            'accepted_resumed' => 'Accepted',
            'status_changed' => 'Status updated',
            'repaired' => 'Repaired',
        ];

        return $map[$action] ?? 'Updated';
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

    private function currencyThreshold(string $currency): int
    {
        $settings = null;
        if (Schema::hasTable('trust_settings')) {
            $settings = TrustSetting::first();
        }
        if ($settings && is_array($settings->currency_thresholds) && isset($settings->currency_thresholds[$currency])) {
            return (int) $settings->currency_thresholds[$currency];
        }
        $thresholds = (array) config('currency.thresholds_cents', []);
        return (int) ($thresholds[$currency] ?? $thresholds['USD'] ?? 50000);
    }

    private function minForHighValue(): int
    {
        $settings = null;
        if (Schema::hasTable('trust_settings')) {
            $settings = TrustSetting::first();
        }
        if ($settings && $settings->min_for_high_value !== null) {
            return (int) $settings->min_for_high_value;
        }
        return (int) config('trust.profile.min_for_high_value', 80);
    }
    private function minForContract(): int
    {
        $settings = null;
        if (Schema::hasTable('trust_settings')) {
            $settings = TrustSetting::first();
        }
        if ($settings && $settings->min_for_contract !== null) {
            return (int) $settings->min_for_contract;
        }
        return (int) config('trust.profile.min_for_contract', 50);
    }

    public function downloadPdf(Contract $contract)
    {
        $user = Auth::user();
        if ($user->role !== 'Admin' && $user->id !== $contract->buyer_id && $user->id !== $contract->seller_id) {
            abort(403);
        }
        if ($contract->status !== 'signed') {
            return back()->with('error', 'PDF is available after both parties sign.');
        }
        if (!$contract->pdf_path) {
            try {
                $service = new \App\Services\ContractPdfService();
                $contract->pdf_path = $service->generate($contract);
                $contract->save();
            } catch (\Throwable $e) {
                return back()->with('error', 'Unable to generate PDF. Use Printable Version instead.');
            }
        }
        $path = $contract->pdf_path;
        if (!\Illuminate\Support\Facades\Storage::exists($path)) {
            return back()->with('error', 'PDF file not found.');
        }
        if (request()->boolean('inline')) {
            return response()->file(\Illuminate\Support\Facades\Storage::path($path));
        }
        return \Illuminate\Support\Facades\Storage::download($path);
    }

    // Removed previewPdf: we now rely on Printable Version for white paper styling and direct download from there.

    public function print(Contract $contract)
    {
        $user = Auth::user();
        if ($user->role !== 'Admin' && $user->id !== $contract->buyer_id && $user->id !== $contract->seller_id) {
            abort(403);
        }
        $contract->load(['buyer', 'seller', 'signatures.user']);
        return Inertia::render('Contracts/Print', [
            'contract' => [
                'id' => $contract->id,
                'title' => $contract->title,
                'description' => $contract->description,
                'price_cents' => $contract->price_cents,
                'currency' => $contract->currency,
                'status' => $contract->status,
                'created_at' => $contract->created_at,
                'buyer' => $contract->buyer ? [
                    'name' => $contract->buyer->name,
                    'email' => $contract->buyer->email,
                    'verification_status' => $contract->buyer->verification_status,
                    'verification_level' => $contract->buyer->verification_level ?? 'none',
                ] : null,
                'seller' => $contract->seller ? [
                    'name' => $contract->seller->name,
                    'email' => $contract->seller->email,
                    'verification_status' => $contract->seller->verification_status,
                    'verification_level' => $contract->seller->verification_level ?? 'none',
                ] : null,
                'signatures' => $contract->signatures->map(function ($s) {
                    return [
                        'user' => $s->user ? $s->user->name : ('User #'.$s->user_id),
                        'signed_at' => $s->signed_at,
                        'ip_address' => $s->ip_address,
                        'device_info' => $s->device_info,
                    ];
                })->values(),
            ],
        ]);
    }
    public function destroy(Contract $contract)
    {
        $user = Auth::user();
        if ($user->role !== 'Admin' && $user->id !== $contract->buyer_id && $user->id !== $contract->seller_id) {
            abort(403);
        }
        try {
            if ($contract->pdf_path) {
                \Illuminate\Support\Facades\Storage::delete($contract->pdf_path);
            }
            \App\Models\ContractSignature::where('contract_id', $contract->id)->delete();
            \App\Models\ContractLog::where('contract_id', $contract->id)->delete();
            \App\Models\ContractReview::where('contract_id', $contract->id)->delete();
            $contract->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete contract.');
        }
        return redirect()->route('contracts.index')->with('success', 'Contract deleted. You are responsible for deletion of agreements.');
    }
}
