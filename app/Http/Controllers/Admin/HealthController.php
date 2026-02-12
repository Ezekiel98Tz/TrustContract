<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HealthController extends Controller
{
    public function db(Request $request)
    {
        $driver = DB::connection()->getDriverName();
        $conn = config("database.connections.$driver", []);
        $database = $conn['database'] ?? env('DB_DATABASE');
        $host = $conn['host'] ?? env('DB_HOST');
        $port = $conn['port'] ?? env('DB_PORT');

        $tables = [
            'trust_settings',
            'disputes',
            'contracts',
            'transactions',
            'webhook_events',
            'contract_reviews',
            'contract_signatures',
            'contract_logs',
            'migrations',
        ];
        $missingTables = [];
        foreach ($tables as $t) {
            if (!Schema::hasTable($t)) {
                $missingTables[] = $t;
            }
        }

        $missingColumns = [];
        if (Schema::hasTable('trust_settings')) {
            $required = [
                'min_for_contract',
                'min_for_high_value',
                'dispute_rate_warn_percent',
                'require_business_verification',
                'currency_thresholds',
            ];
            foreach ($required as $col) {
                if (!Schema::hasColumn('trust_settings', $col)) {
                    $missingColumns['trust_settings'][] = $col;
                }
            }
        }

        $appliedMigrations = [];
        $missingMigrations = [];
        if (Schema::hasTable('migrations')) {
            $appliedMigrations = DB::table('migrations')->pluck('migration')->all();
            $requiredMigrations = [
                '2025_10_14_000002_create_contracts_table',
                '2025_10_15_000013_update_contracts_status_check',
                '2026_01_28_000008_create_trust_settings_table',
                '2026_01_28_000009_create_trust_settings_logs_table',
                '2026_02_12_000009_add_dispute_rate_warn_percent_to_trust_settings',
                '2026_02_12_000010_create_disputes_table',
                '2026_02_12_000011_add_require_business_verification_to_trust_settings',
            ];
            foreach ($requiredMigrations as $mig) {
                if (!in_array($mig, $appliedMigrations, true)) {
                    $missingMigrations[] = $mig;
                }
            }
        }

        $constraints = [];
        if ($driver === 'pgsql' && Schema::hasTable('contracts')) {
            $rows = DB::select("
                SELECT con.conname
                FROM pg_constraint con
                JOIN pg_class rel ON rel.oid = con.conrelid
                WHERE rel.relname = 'contracts' AND con.contype = 'c'
            ");
            $constraints = array_map(fn($r) => $r->conname, $rows);
        }

        return response()->json([
            'connection' => [
                'driver' => $driver,
                'database' => $database,
                'host' => $host,
                'port' => $port,
            ],
            'missing_tables' => $missingTables,
            'missing_columns' => $missingColumns,
            'missing_migrations' => $missingMigrations,
            'contracts_constraints' => $constraints,
            'ok' => empty($missingTables) && empty($missingColumns) && empty($missingMigrations),
        ]);
    }
}
