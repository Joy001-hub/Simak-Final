<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('sales', 'notes')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales', 'notes')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
