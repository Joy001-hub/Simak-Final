<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_key')->unique();
            $table->string('plan')->default('basic');
            $table->string('subscription_status')->nullable();
            $table->unsignedInteger('max_devices')->default(2);
            $table->timestamp('subscription_checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_devices', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_key');
            $table->string('device_id');
            $table->string('device_name')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_key', 'device_id']);
            $table->index(['tenant_key', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_devices');
        Schema::dropIfExists('tenants');
    }
};
