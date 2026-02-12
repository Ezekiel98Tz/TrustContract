<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrustSetting;
use App\Models\TrustSettingLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TrustSettingsController extends Controller
{
    public function index()
    {
        $settings = null;
        if (Schema::hasTable('trust_settings')) {
            $settings = TrustSetting::first();
        }
        if (!$settings) {
            $settings = new TrustSetting([
                'min_for_contract' => config('trust.profile.min_for_contract', 50),
                'min_for_high_value' => config('trust.profile.min_for_high_value', 80),
                'dispute_rate_warn_percent' => 5,
                'currency_thresholds' => config('currency.thresholds_cents', [
                    'USD' => 50000,
                    'EUR' => 50000,
                    'TZS' => 130000000,
                ]),
            ]);
        }
        $currencies = array_keys(config('currency.thresholds_cents', [
            'USD' => 50000,
            'EUR' => 50000,
            'TZS' => 130000000,
        ]));
        return Inertia::render('Admin/TrustSettings', [
            'settings' => [
                'min_for_contract' => (int) $settings->min_for_contract,
                'min_for_high_value' => (int) $settings->min_for_high_value,
                'dispute_rate_warn_percent' => (int) ($settings->dispute_rate_warn_percent ?? 5),
                'currency_thresholds' => (array) $settings->currency_thresholds,
            ],
            'currencies' => $currencies,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'min_for_contract' => ['required', 'integer', 'min:0', 'max:100'],
            'min_for_high_value' => ['required', 'integer', 'min:0', 'max:100'],
            'dispute_rate_warn_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'currency_thresholds' => ['required', 'array'],
            'currency_thresholds.*' => ['required', 'integer', 'min:0'],
            'require_business_verification' => ['nullable', 'boolean'],
        ]);
        if (!Schema::hasTable('trust_settings')) {
            return redirect()->back()->with('error', 'Trust settings table not found. Please run migrations.');
        }
        $settings = TrustSetting::first();
        if (!$settings) {
            $settings = new TrustSetting();
        }
        $before = [
            'min_for_contract' => $settings->min_for_contract,
            'min_for_high_value' => $settings->min_for_high_value,
            'dispute_rate_warn_percent' => $settings->dispute_rate_warn_percent ?? 5,
            'currency_thresholds' => $settings->currency_thresholds,
            'require_business_verification' => $settings->require_business_verification ?? false,
        ];
        $settings->min_for_contract = $data['min_for_contract'];
        $settings->min_for_high_value = $data['min_for_high_value'];
        $settings->dispute_rate_warn_percent = $data['dispute_rate_warn_percent'];
        $settings->currency_thresholds = $data['currency_thresholds'];
        $settings->require_business_verification = (bool) ($data['require_business_verification'] ?? false);
        $settings->save();
        Cache::forget('trust_settings_first');
        TrustSettingLog::create([
            'admin_id' => $request->user()->id,
            'before' => $before,
            'after' => [
                'min_for_contract' => $settings->min_for_contract,
                'min_for_high_value' => $settings->min_for_high_value,
                'dispute_rate_warn_percent' => $settings->dispute_rate_warn_percent,
                'currency_thresholds' => $settings->currency_thresholds,
                'require_business_verification' => $settings->require_business_verification,
            ],
        ]);

        return redirect()->back()->with('success', 'Trust thresholds updated.');
    }
}
