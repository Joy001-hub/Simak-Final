<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'total_units')) {
                $table->unsignedInteger('total_units')->default(0)->after('notes');
            }
            if (!Schema::hasColumn('projects', 'sold_units')) {
                $table->unsignedInteger('sold_units')->default(0)->after('total_units');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'sold_units')) {
                $table->dropColumn('sold_units');
            }
            if (Schema::hasColumn('projects', 'total_units')) {
                $table->dropColumn('total_units');
            }
        });
    }
};
