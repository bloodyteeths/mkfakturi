<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance_date (Ден на извршен промет) per ЗДДВ Член 53.
     */
    public function up(): void
    {
        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'performance_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->date('performance_date')->nullable()->after('invoice_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'performance_date')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('performance_date');
            });
        }
    }
};
// CLAUDE-CHECKPOINT
