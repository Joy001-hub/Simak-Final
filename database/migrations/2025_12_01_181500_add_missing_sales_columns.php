<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'lot_id')) {
                $table->unsignedBigInteger('lot_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('sales', 'buyer_id')) {
                $table->unsignedBigInteger('buyer_id')->nullable()->after('lot_id');
            }
            if (!Schema::hasColumn('sales', 'marketer_id')) {
                $table->unsignedBigInteger('marketer_id')->nullable()->after('buyer_id');
            }
            if (!Schema::hasColumn('sales', 'booking_date')) {
                $table->date('booking_date')->nullable()->after('marketer_id');
            }
            if (!Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method', 30)->default('installment')->after('booking_date');
            }
            if (!Schema::hasColumn('sales', 'price')) {
                $table->unsignedBigInteger('price')->default(0)->after('payment_method');
            }
            if (!Schema::hasColumn('sales', 'down_payment')) {
                $table->unsignedBigInteger('down_payment')->default(0)->after('price');
            }
            if (!Schema::hasColumn('sales', 'tenor_months')) {
                $table->unsignedInteger('tenor_months')->default(0)->after('down_payment');
            }
            if (!Schema::hasColumn('sales', 'due_day')) {
                $table->unsignedTinyInteger('due_day')->nullable()->after('tenor_months');
            }
            if (!Schema::hasColumn('sales', 'paid_amount')) {
                $table->unsignedBigInteger('paid_amount')->default(0)->after('due_day');
            }
            if (!Schema::hasColumn('sales', 'outstanding_amount')) {
                $table->unsignedBigInteger('outstanding_amount')->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('sales', 'status')) {
                $table->string('status', 20)->default('active')->after('outstanding_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $columns = [
                'status',
                'outstanding_amount',
                'paid_amount',
                'due_day',
                'tenor_months',
                'down_payment',
                'price',
                'payment_method',
                'booking_date',
                'marketer_id',
                'buyer_id',
                'lot_id',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
