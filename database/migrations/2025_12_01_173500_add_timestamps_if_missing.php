<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        $tables = ['projects', 'lots', 'buyers', 'marketers', 'sales', 'payments'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableObj) use ($table) {
                if (!Schema::hasColumn($table, 'created_at')) {
                    $tableObj->timestamp('created_at')->nullable();
                }
                if (!Schema::hasColumn($table, 'updated_at')) {
                    $tableObj->timestamp('updated_at')->nullable();
                }
            });
        }

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'booking_date'))
                return;
            $table->index('booking_date', 'sales_booking_date_idx');
            $table->index('payment_method', 'sales_payment_method_idx');
            $table->index('status', 'sales_status_idx');
            $table->index('buyer_id', 'sales_buyer_idx');
            $table->index('marketer_id', 'sales_marketer_idx');
            $table->index('lot_id', 'sales_lot_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('sale_id', 'payments_sale_idx');
            $table->index('due_date', 'payments_due_date_idx');
            $table->index('status', 'payments_status_idx');
        });

        Schema::table('lots', function (Blueprint $table) {

        });
    }

    public function down(): void
    {
        $tables = ['projects', 'lots', 'buyers', 'marketers', 'sales', 'payments'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableObj) use ($table) {
                if (Schema::hasColumn($table, 'updated_at')) {
                    $tableObj->dropColumn('updated_at');
                }
                if (Schema::hasColumn($table, 'created_at')) {
                    $tableObj->dropColumn('created_at');
                }
            });
        }
    }
};
