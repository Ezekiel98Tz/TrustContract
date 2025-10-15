<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Drop existing constraint first
        DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS contracts_status_check;");

        // Update existing rows to new status values
        DB::statement("UPDATE contracts SET status = 'signed' WHERE status = 'active';");
        DB::statement("UPDATE contracts SET status = 'finalized' WHERE status = 'completed';");

        // Add updated CHECK constraint
        DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_status_check CHECK (status IN ('draft','pending_approval','signed','finalized','cancelled'));");
    }

    public function down(): void
    {
        // Revert to previous allowed statuses
        DB::statement("ALTER TABLE contracts DROP CONSTRAINT IF EXISTS contracts_status_check;");
        DB::statement("ALTER TABLE contracts ADD CONSTRAINT contracts_status_check CHECK (status IN ('draft','pending_approval','active','completed','cancelled'));");

        // Map statuses back to previous naming
        DB::statement("UPDATE contracts SET status = 'active' WHERE status = 'signed';");
        DB::statement("UPDATE contracts SET status = 'completed' WHERE status = 'finalized';");
    }
};