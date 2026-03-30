<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds branch (project_id) FK to warehouses, fiscal_devices, and pos_shifts.
 * Allows linking these assets to a specific branch for multi-location management.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Link warehouses to branches
        if (Schema::hasTable('warehouses') && ! Schema::hasColumn('warehouses', 'project_id')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('company_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('set null');
                $table->index('project_id');
            });
        }

        // Link fiscal devices to branches
        if (Schema::hasTable('fiscal_devices') && ! Schema::hasColumn('fiscal_devices', 'project_id')) {
            Schema::table('fiscal_devices', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('company_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('set null');
                $table->index('project_id');
            });
        }

        // Link POS shifts to branches
        if (Schema::hasTable('pos_shifts') && ! Schema::hasColumn('pos_shifts', 'project_id')) {
            Schema::table('pos_shifts', function (Blueprint $table) {
                $table->unsignedBigInteger('project_id')->nullable()->after('company_id');
                $table->foreign('project_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('set null');
                $table->index('project_id');
            });
        }
    }

    public function down(): void
    {
        $tables = ['warehouses', 'fiscal_devices', 'pos_shifts'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'project_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['project_id']);
                    $table->dropColumn('project_id');
                });
            }
        }
    }
};
