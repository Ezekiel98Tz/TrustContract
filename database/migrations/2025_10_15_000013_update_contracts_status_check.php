<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            // SQLite does not support altering check constraints in the same way; skip.
            DB::statement("UPDATE contracts SET status = 'signed' WHERE status = 'active';");
            DB::statement("UPDATE contracts SET status = 'finalized' WHERE status = 'completed';");
            return;
        }
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts DROP CHECK contracts_status_check;");
        } else {
            DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS contracts_status_check;");
        }

        // Update existing rows to new status values
        DB::statement("UPDATE contracts SET status = 'signed' WHERE status = 'active';");
        DB::statement("UPDATE contracts SET status = 'finalized' WHERE status = 'completed';");

        // Add updated CHECK constraint
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_status_check CHECK (status IN ('draft','pending_approval','signed','finalized','cancelled'));");
        } else {
            DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_status_check CHECK (status IN ('draft','pending_approval','signed','finalized','cancelled'));");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement("UPDATE contracts SET status = 'active' WHERE status = 'signed';");
            DB::statement("UPDATE contracts SET status = 'completed' WHERE status = 'finalized';");
            return;
        }
        // Revert to previous allowed statuses
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE contracts DROP CHECK contracts_status_check;");
            DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_status_check CHECK (status IN ('draft','pending_approval','active','completed','cancelled'));");
        } else {
            DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS contracts_status_check;");
            DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_status_check CHECK (status IN ('draft','pending_approval','active','completed','cancelled'));");
        }

        // Map statuses back to previous naming
        DB::statement("UPDATE contracts SET status = 'active' WHERE status = 'signed';");
        DB::statement("UPDATE contracts SET status = 'completed' WHERE status = 'finalized';");
    }
};
