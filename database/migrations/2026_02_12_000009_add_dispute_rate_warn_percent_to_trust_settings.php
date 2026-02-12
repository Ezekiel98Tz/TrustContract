<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trust_settings')) {
            Schema::table('trust_settings', function (Blueprint $table) {
                $table->unsignedTinyInteger('dispute_rate_warn_percent')->default(5)->after('min_for_high_value');
            });
            DB::table('trust_settings')->update([
                'dispute_rate_warn_percent' => 5,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trust_settings')) {
            Schema::table('trust_settings', function (Blueprint $table) {
                $table->dropColumn('dispute_rate_warn_percent');
            });
        }
    }
};
