<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add reconciliation_status to support PERF-01-03 index
        if (Schema::hasTable('bank_transactions') && ! Schema::hasColumn('bank_transactions', 'reconciliation_status')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->string('reconciliation_status', 32)
                    ->default('unreconciled')
                    ->after('processing_status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('bank_transactions') && Schema::hasColumn('bank_transactions', 'reconciliation_status')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->dropColumn('reconciliation_status');
            });
        }
    }
};

