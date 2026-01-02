<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales')) {
            return;
        }

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained('lots')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('buyers')->cascadeOnDelete();
            $table->foreignId('marketer_id')->nullable()->constrained('marketers')->nullOnDelete();
            $table->date('booking_date')->nullable();
            $table->enum('payment_method', ['cash', 'installment', 'kpr'])->default('installment');
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('down_payment')->default(0);
            $table->unsignedInteger('tenor_months')->default(0);
            $table->unsignedTinyInteger('due_day')->nullable();
            $table->unsignedBigInteger('paid_amount')->default(0);
            $table->unsignedBigInteger('outstanding_amount')->default(0);
            $table->enum('status', ['active', 'paid_off', 'canceled'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
