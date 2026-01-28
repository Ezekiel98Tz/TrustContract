<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trust_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('min_for_contract')->default(50);
            $table->unsignedTinyInteger('min_for_high_value')->default(80);
            $table->json('currency_thresholds')->nullable();
            $table->boolean('require_business_verification')->default(false);
            $table->timestamps();
        });

        $thresholds = config('currency.thresholds_cents', [
            'USD' => 50000,
            'EUR' => 50000,
            'TZS' => 130000000,
        ]);

        DB::table('trust_settings')->insert([
            'min_for_contract' => config('trust.profile.min_for_contract', 50),
            'min_for_high_value' => config('trust.profile.min_for_high_value', 80),
            'currency_thresholds' => json_encode($thresholds),
            'require_business_verification' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('trust_settings');
    }
};
