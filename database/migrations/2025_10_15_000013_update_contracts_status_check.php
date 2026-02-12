<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            // Rebuild table to update enum/check constraint
            DB::statement('CREATE TABLE contracts_tmp (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                buyer_id INTEGER NOT NULL,
                seller_id INTEGER NOT NULL,
                title VARCHAR NOT NULL,
                description TEXT NULL,
                price_cents BIGINT NOT NULL,
                currency CHAR(3) NOT NULL DEFAULT \'USD\',
                deadline_at DATETIME NULL,
                status VARCHAR NOT NULL CHECK (status IN (\'draft\',\'pending_approval\',\'signed\',\'finalized\',\'cancelled\')) DEFAULT \'draft\',
                buyer_accepted_at DATETIME NULL,
                seller_accepted_at DATETIME NULL,
                pdf_path VARCHAR NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL
            )');
            DB::statement('INSERT INTO contracts_tmp (id,buyer_id,seller_id,title,description,price_cents,currency,deadline_at,status,buyer_accepted_at,seller_accepted_at,pdf_path,created_at,updated_at)
                SELECT id,buyer_id,seller_id,title,description,price_cents,currency,deadline_at,
                    CASE status WHEN \'active\' THEN \'signed\' WHEN \'completed\' THEN \'finalized\' ELSE status END AS status,
                    buyer_accepted_at,seller_accepted_at,pdf_path,created_at,updated_at
                FROM contracts');
            DB::statement('DROP TABLE contracts');
            DB::statement('ALTER TABLE contracts_tmp RENAME TO contracts');
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
