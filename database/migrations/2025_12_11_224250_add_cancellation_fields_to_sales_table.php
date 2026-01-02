<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->nullable()->after('status');
            }
            if (!Schema::hasColumn('sales', 'status_before_cancel')) {
                $table->string('status_before_cancel')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sales', 'parent_sale_id')) {
                $table->unsignedBigInteger('parent_sale_id')->nullable()->after('id')->index();
            }
        });

        try {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'parent_sale_id')) {
                    $table->foreign('parent_sale_id')->references('id')->on('sales')->nullOnDelete();
                }
            });
        } catch (\Throwable $e) {

        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            try {
                $table->dropForeign(['parent_sale_id']);
            } catch (\Throwable $e) {

            }

            $columns = ['refund_amount', 'status_before_cancel', 'parent_sale_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
