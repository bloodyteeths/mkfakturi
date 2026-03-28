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
        if (Schema::hasTable('recurring_invoices') && ! Schema::hasColumn('recurring_invoices', 'contract_reference')) {
            Schema::table('recurring_invoices', function (Blueprint $table) {
                $table->string('contract_reference')->nullable()->after('template_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('recurring_invoices', 'contract_reference')) {
            Schema::table('recurring_invoices', function (Blueprint $table) {
                $table->dropColumn('contract_reference');
            });
        }
    }
};
// CLAUDE-CHECKPOINT
