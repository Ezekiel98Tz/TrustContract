<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trust_settings') && !Schema::hasColumn('trust_settings', 'require_business_verification')) {
            Schema::table('trust_settings', function (Blueprint $table) {
                $table->boolean('require_business_verification')->default(false)->after('dispute_rate_warn_percent');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('trust_settings') && Schema::hasColumn('trust_settings', 'require_business_verification')) {
            Schema::table('trust_settings', function (Blueprint $table) {
                $table->dropColumn('require_business_verification');
            });
        }
    }
};
