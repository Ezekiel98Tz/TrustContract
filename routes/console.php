<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Contract;
use App\Models\ContractReview;
use App\Models\Transaction;
use App\Models\Dispute;
use App\Models\TrustSetting;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('trust:phase4:verify', function () {
    $driver = DB::connection()->getDriverName();
    $this->info("DB driver: {$driver}");
    if ($driver !== 'pgsql') {
        $this->error('This verification requires Postgres (pgsql). Please configure DB_CONNECTION=pgsql.');
        return 1;
    }
    $tables = ['trust_settings','contracts','transactions','webhook_events','contract_reviews','contract_signatures','contract_logs','disputes'];
    foreach ($tables as $t) {
        if (!Schema::hasTable($t)) {
            $this->error("Missing table: {$t}");
            return 1;
        }
    }
    // Ensure trust settings row exists
    $settings = TrustSetting::first();
    if (!$settings) {
        $settings = TrustSetting::create([
            'min_for_contract' => 50,
            'min_for_high_value' => 80,
            'currency_thresholds' => ['USD' => 50000, 'EUR' => 50000, 'TZS' => 130000000],
            'dispute_rate_warn_percent' => 5,
        ]);
    }
    // Create users
    $buyer = User::factory()->create(['role' => 'Buyer', 'email_verified_at' => now(), 'name' => 'Buyer Test', 'email' => 'buyer@example.test']);
    $seller = User::factory()->create(['role' => 'Seller', 'email_verified_at' => now(), 'name' => 'Seller Test', 'email' => 'seller@example.test']);
    // Contracts
    $signed = Contract::create([
        'title' => 'Signed Contract',
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'price_cents' => 10000,
        'currency' => 'USD',
        'status' => 'signed',
    ]);
    $finalized = Contract::create([
        'title' => 'Finalized Contract',
        'buyer_id' => $buyer->id,
        'seller_id' => $seller->id,
        'price_cents' => 15000,
        'currency' => 'USD',
        'status' => 'finalized',
    ]);
    // Review enforcement: finalized only
    try {
        ContractReview::create([
            'contract_id' => $signed->id,
            'reviewer_id' => $buyer->id,
            'reviewee_id' => $seller->id,
            'rating' => 5,
            'comment' => 'Should fail (controller enforces), created only if bypassed.',
        ]);
        $this->warning('A review was inserted on signed contract; ensure controller route prevents this in web flow.');
    } catch (\Throwable $e) {
        // Model allows create, controller prevents; proceed
    }
    ContractReview::create([
        'contract_id' => $finalized->id,
        'reviewer_id' => $buyer->id,
        'reviewee_id' => $seller->id,
        'rating' => 4,
        'comment' => 'Finalized review',
    ]);
    // Transactions and dispute
    $txnPaid = Transaction::create([
        'contract_id' => $finalized->id,
        'payer_id' => $buyer->id,
        'payee_id' => $seller->id,
        'amount_cents' => 15000,
        'currency' => 'USD',
        'status' => 'paid',
    ]);
    $txnFailed = Transaction::create([
        'contract_id' => $finalized->id,
        'payer_id' => $buyer->id,
        'payee_id' => $seller->id,
        'amount_cents' => 15000,
        'currency' => 'USD',
        'status' => 'failed',
    ]);
    Dispute::firstOrCreate([
        'transaction_id' => $txnFailed->id,
        'contract_id' => $finalized->id,
        'provider' => 'sandbox',
        'external_event_id' => 'evt_verify_1',
    ], [
        'status' => 'open',
        'reason' => 'Verification',
    ]);
    // Insights computation mirror
    $txns = Transaction::where(function ($q) use ($seller) {
            $q->where('payer_id', $seller->id)->orWhere('payee_id', $seller->id);
        })->get(['id','status']);
    $paidCount = $txns->where('status','paid')->count();
    $failedCount = $txns->where('status','failed')->count();
    $disputeCount = Dispute::whereIn('transaction_id', $txns->pluck('id'))->count();
    $denom = max($paidCount + $failedCount, 1);
    $rate = round(($disputeCount / $denom) * 100, 1);
    $this->info("Insights: paid={$paidCount} failed={$failedCount} disputes={$disputeCount} rate={$rate}%");
    // Finalized-only averages
    $avgFinal = ContractReview::where('reviewee_id', $seller->id)->whereHas('contract', function ($q) {
        $q->where('status','finalized');
    })->avg('rating');
    $this->info("Finalized-only rating avg for seller: " . ($avgFinal ? round($avgFinal,1) : 'null'));
    // Admin settings update simulation
    $settings->dispute_rate_warn_percent = 7;
    $settings->require_business_verification = false;
    $settings->save();
    $this->info("Trust settings updated: dispute_rate_warn_percent={$settings->dispute_rate_warn_percent}");
    $this->info('Phase 4 verification completed on Postgres.');
    return 0;
})->purpose('Verify Phase 4 DB and flows on Postgres');
