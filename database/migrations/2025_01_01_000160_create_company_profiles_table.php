<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('company_profiles')) {
            return;
        }

        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Nama Perusahaan');
            $table->string('npwp')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('footer_note')->nullable();
            $table->string('invoice_format')->default('INV/{YYYY}/{MM}/{####}');
            $table->string('receipt_format')->default('KW/{YYYY}/{MM}/{####}');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_profiles');
    }
};
