<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sales', 'paid_amount') || !Schema::hasColumn('sales', 'outstanding_amount')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'paid_amount')) {
                    $table->unsignedBigInteger('paid_amount')->default(0)->after('due_day');
                }
                if (!Schema::hasColumn('sales', 'outstanding_amount')) {
                    $table->unsignedBigInteger('outstanding_amount')->default(0)->after('paid_amount');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'outstanding_amount')) {
                $table->dropColumn('outstanding_amount');
            }
            if (Schema::hasColumn('sales', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
