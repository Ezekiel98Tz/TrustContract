<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('provider', 50)->nullable()->after('currency');
            $table->string('provider_tx_ref')->nullable()->after('provider');
            $table->string('provider_tx_id')->nullable()->after('provider_tx_ref');
            $table->bigInteger('commission_cents')->default(0)->after('amount_cents');
            $table->bigInteger('charged_amount_cents')->nullable()->after('commission_cents');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_tx_ref', 'provider_tx_id', 'commission_cents', 'charged_amount_cents']);
        });
    }
};