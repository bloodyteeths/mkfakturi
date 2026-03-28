<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('budgets') && ! Schema::hasColumn('budgets', 'number')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->string('number', 20)->nullable()->after('company_id');
                $table->index(['company_id', 'number']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('budgets') && Schema::hasColumn('budgets', 'number')) {
            Schema::table('budgets', function (Blueprint $table) {
                $table->dropIndex(['company_id', 'number']);
                $table->dropColumn('number');
            });
        }
    }
};

// CLAUDE-CHECKPOINT
