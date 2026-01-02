<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('payments', 'due_date')) {
                $table->date('due_date')->nullable()->after('sale_id');
            }
            if (!Schema::hasColumn('payments', 'amount')) {
                $table->unsignedBigInteger('amount')->default(0)->after('due_date');
            }
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status', 20)->default('unpaid')->after('amount');
            }
            if (!Schema::hasColumn('payments', 'note')) {
                $table->string('note')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = ['paid_at', 'note', 'status', 'amount', 'due_date', 'sale_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
