<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sejoli_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->json('payload')->nullable();
            $table->string('signature')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'product_id']);
            $table->index(['event', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sejoli_webhook_events');
    }
};
