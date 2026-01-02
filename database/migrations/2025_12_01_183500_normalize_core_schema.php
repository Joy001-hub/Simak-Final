<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {

        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'notes')) {
                $table->text('notes')->nullable()->after('location');
            }
            if (!Schema::hasColumn('projects', 'total_units')) {
                $table->unsignedInteger('total_units')->default(0)->after('notes');
            }
            if (!Schema::hasColumn('projects', 'sold_units')) {
                $table->unsignedInteger('sold_units')->default(0)->after('total_units');
            }
            if (!Schema::hasColumn('projects', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('projects', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        Schema::table('lots', function (Blueprint $table) {
            if (!Schema::hasColumn('lots', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable();
            }
            if (!Schema::hasColumn('lots', 'block_number')) {
                $table->string('block_number')->default('LOT');
            }
            if (!Schema::hasColumn('lots', 'area')) {
                $table->unsignedInteger('area')->nullable();
            }
            if (!Schema::hasColumn('lots', 'base_price')) {
                $table->unsignedBigInteger('base_price')->default(0);
            }
            if (!Schema::hasColumn('lots', 'status')) {
                $table->string('status')->default('available');
            }
            if (!Schema::hasColumn('lots', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('lots', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

        });

        Schema::table('buyers', function (Blueprint $table) {
            if (!Schema::hasColumn('buyers', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('buyers', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        Schema::table('marketers', function (Blueprint $table) {
            if (!Schema::hasColumn('marketers', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('marketers', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'lot_id')) {
                $table->unsignedBigInteger('lot_id')->nullable();
            }
            if (!Schema::hasColumn('sales', 'buyer_id')) {
                $table->unsignedBigInteger('buyer_id')->nullable();
            }
            if (!Schema::hasColumn('sales', 'marketer_id')) {
                $table->unsignedBigInteger('marketer_id')->nullable();
            }
            if (!Schema::hasColumn('sales', 'booking_date')) {
                $table->date('booking_date')->nullable();
            }
            if (!Schema::hasColumn('sales', 'payment_method')) {
                $table->string('payment_method', 30)->default('installment');
            }
            if (!Schema::hasColumn('sales', 'price')) {
                $table->unsignedBigInteger('price')->default(0);
            }
            if (!Schema::hasColumn('sales', 'down_payment')) {
                $table->unsignedBigInteger('down_payment')->default(0);
            }
            if (!Schema::hasColumn('sales', 'tenor_months')) {
                $table->unsignedInteger('tenor_months')->default(0);
            }
            if (!Schema::hasColumn('sales', 'due_day')) {
                $table->unsignedTinyInteger('due_day')->nullable();
            }
            if (!Schema::hasColumn('sales', 'paid_amount')) {
                $table->unsignedBigInteger('paid_amount')->default(0);
            }
            if (!Schema::hasColumn('sales', 'outstanding_amount')) {
                $table->unsignedBigInteger('outstanding_amount')->default(0);
            }
            if (!Schema::hasColumn('sales', 'status')) {
                $table->string('status', 20)->default('active');
            }
            if (!Schema::hasColumn('sales', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('sales', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

        });

        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'sale_id')) {
                $table->unsignedBigInteger('sale_id')->nullable();
            }
            if (!Schema::hasColumn('payments', 'due_date')) {
                $table->date('due_date')->nullable();
            }
            if (!Schema::hasColumn('payments', 'amount')) {
                $table->unsignedBigInteger('amount')->default(0);
            }
            if (!Schema::hasColumn('payments', 'status')) {
                $table->string('status', 20)->default('unpaid');
            }
            if (!Schema::hasColumn('payments', 'note')) {
                $table->string('note')->nullable();
            }
            if (!Schema::hasColumn('payments', 'paid_at')) {
                $table->dateTime('paid_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('payments', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

        });
    }

    public function down(): void
    {

    }
};
