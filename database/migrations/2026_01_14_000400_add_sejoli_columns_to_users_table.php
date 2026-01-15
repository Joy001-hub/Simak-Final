<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('sejoli_user_id')->nullable()->after('password');
            $table->unsignedBigInteger('sejoli_product_id')->nullable()->after('sejoli_user_id');
            $table->string('subscription_status')->nullable()->after('sejoli_product_id');
            $table->timestamp('subscription_end_date')->nullable()->after('subscription_status');
            $table->timestamp('last_subscription_check_at')->nullable()->after('subscription_end_date');

            $table->index(['sejoli_user_id', 'sejoli_product_id'], 'users_sejoli_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_sejoli_idx');
            $table->dropColumn([
                'sejoli_user_id',
                'sejoli_product_id',
                'subscription_status',
                'subscription_end_date',
                'last_subscription_check_at',
            ]);
        });
    }
};
