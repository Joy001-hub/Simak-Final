<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            if (!Schema::hasColumn('lots', 'block_number')) {
                $table->string('block_number')->default('LOT')->after('project_id');
            }
            if (!Schema::hasColumn('lots', 'area')) {
                $table->unsignedInteger('area')->nullable()->after('block_number');
            }
            if (!Schema::hasColumn('lots', 'base_price')) {
                $table->unsignedBigInteger('base_price')->default(0)->after('area');
            }
            if (!Schema::hasColumn('lots', 'status')) {
                $table->string('status')->default('available')->after('base_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lots', function (Blueprint $table) {
            if (Schema::hasColumn('lots', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('lots', 'base_price')) {
                $table->dropColumn('base_price');
            }
            if (Schema::hasColumn('lots', 'area')) {
                $table->dropColumn('area');
            }
            if (Schema::hasColumn('lots', 'block_number')) {
                $table->dropColumn('block_number');
            }
        });
    }
};
