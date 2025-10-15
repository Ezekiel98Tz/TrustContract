<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $cols = [
                'commission_cents',
                'charged_amount_cents',
                'provider',
                'provider_tx_ref',
                'provider_tx_id',
                'external_status',
                'paid_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'commission_cents')) {
                $table->integer('commission_cents')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'charged_amount_cents')) {
                $table->integer('charged_amount_cents')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'provider')) {
                $table->string('provider')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'provider_tx_ref')) {
                $table->string('provider_tx_ref')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'provider_tx_id')) {
                $table->string('provider_tx_id')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'external_status')) {
                $table->string('external_status')->nullable();
            }
            if (!Schema::hasColumn('transactions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
        });
    }
};