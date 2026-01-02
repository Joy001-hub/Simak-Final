<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lots')) {
            return;
        }

        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('block_number');
            $table->unsignedInteger('area')->nullable();
            $table->unsignedBigInteger('base_price')->default(0);
            $table->enum('status', ['available', 'sold', 'reserved', 'active'])->default('available');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots');
    }
};
