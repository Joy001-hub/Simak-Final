<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {

    public function up(): void
    {

        DB::transaction(function () {

            DB::statement('PRAGMA foreign_keys=OFF;');

            DB::statement("
                CREATE TABLE IF NOT EXISTS payments_temp (
                    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    sale_id INTEGER,
                    due_date DATE,
                    amount INTEGER DEFAULT 0 NOT NULL,
                    status VARCHAR(255) DEFAULT 'unpaid' NOT NULL,
                    note VARCHAR(255),
                    created_at DATETIME,
                    updated_at DATETIME,
                    paid_at DATETIME
                );
            ");

            DB::statement("
                INSERT INTO payments_temp (id, sale_id, due_date, amount, status, note, created_at, updated_at, paid_at)
                SELECT id, sale_id, due_date, amount, status, note, created_at, updated_at, paid_at
                FROM payments;
            ");

            DB::statement("DROP TABLE payments;");
            DB::statement("ALTER TABLE payments_temp RENAME TO payments;");

            DB::statement('PRAGMA foreign_keys=ON;');
        });
    }

    public function down(): void
    {

    }
};
