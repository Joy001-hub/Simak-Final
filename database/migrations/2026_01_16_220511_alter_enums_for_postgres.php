<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Alter ENUM types if they exist, or change column type to VARCHAR to be flexible

            // 1. Fix payments.status
            // The best way in Postgres to handle expanding enums safely in Laravel is sometimes to just use VARCHAR with check constraint
            // or ALTER TYPE .. ADD VALUE. Let's try changing to VARCHAR to be safe and flexible like other tables.
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments ALTER COLUMN status TYPE VARCHAR(50)");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments ALTER COLUMN status SET DEFAULT 'unpaid'");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");

            // 2. Fix sales.status 
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE sales ALTER COLUMN status TYPE VARCHAR(50)");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE sales ALTER COLUMN status SET DEFAULT 'active'");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_status_check");

        } elseif ($driver === 'mysql') {
            // MySQL: Modify ENUM definition
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('unpaid', 'paid', 'overdue', 'partial', 'kpr_bank') NOT NULL DEFAULT 'unpaid'");
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('active', 'paid_off', 'canceled', 'DIBATALKAN_HAPUS', 'DIBATALKAN_REFUND', 'DIALIHKAN_OPER_KREDIT') NOT NULL DEFAULT 'active'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed as we want to keep the flexibility
    }
};
