<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('disputes')) {
            return;
        }
        Schema::table('disputes', function (Blueprint $table) {
            if (!Schema::hasColumn('disputes', 'resolution')) {
                $table->string('resolution')->nullable()->comment('won|lost|cancelled');
            }
            if (!Schema::hasColumn('disputes', 'mediator_id')) {
                $table->unsignedBigInteger('mediator_id')->nullable()->index();
            }
            if (!Schema::hasColumn('disputes', 'mediation_notes')) {
                $table->text('mediation_notes')->nullable();
            }
        });
        // Migrate legacy statuses into new lifecycle: won/lost/cancelled -> resolved + resolution
        try {
            DB::table('disputes')
                ->whereIn('status', ['won', 'lost', 'cancelled'])
                ->update([
                    'resolution' => DB::raw('status'),
                    'status' => 'resolved',
                    'resolved_at' => DB::raw('COALESCE(resolved_at, NOW())'),
                ]);
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        if (!Schema::hasTable('disputes')) {
            return;
        }
        // Revert resolution back into status where applicable
        try {
            DB::table('disputes')
                ->where('status', 'resolved')
                ->whereIn('resolution', ['won','lost','cancelled'])
                ->update([
                    'status' => DB::raw('resolution'),
                ]);
        } catch (\Throwable $e) {}
        Schema::table('disputes', function (Blueprint $table) {
            if (Schema::hasColumn('disputes', 'resolution')) {
                $table->dropColumn('resolution');
            }
            if (Schema::hasColumn('disputes', 'mediator_id')) {
                $table->dropColumn('mediator_id');
            }
            if (Schema::hasColumn('disputes', 'mediation_notes')) {
                $table->dropColumn('mediation_notes');
            }
        });
    }
};
