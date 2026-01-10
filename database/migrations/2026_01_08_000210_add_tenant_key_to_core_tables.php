<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'projects',
            'lots',
            'buyers',
            'marketers',
            'sales',
            'payments',
            'company_profiles',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                if (!Schema::hasColumn($table, 'tenant_key')) {
                    $tableBlueprint->string('tenant_key')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = [
            'projects',
            'lots',
            'buyers',
            'marketers',
            'sales',
            'payments',
            'company_profiles',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                if (Schema::hasColumn($table, 'tenant_key')) {
                    $tableBlueprint->dropColumn('tenant_key');
                }
            });
        }
    }
};
