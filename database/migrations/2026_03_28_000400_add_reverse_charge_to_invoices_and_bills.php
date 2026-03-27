<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add reverse charge (Art. 32-а ЗДДВ) support to invoices and bills.
 *
 * When is_reverse_charge = true:
 *   - Supplier invoice: issued WITHOUT VAT, states "Пренесување на даночна обврска"
 *   - Recipient bill: self-assesses both output VAT (230/2302) and input VAT (130/1302)
 *
 * Legal basis: Член 32-а од ЗДДВ (reverse charge mechanism)
 * Applies to: construction, waste/scrap, forced collection
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invoices', 'is_reverse_charge')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->boolean('is_reverse_charge')->default(false)->after('type');
                $table->index('is_reverse_charge');
            });
        }

        if (! Schema::hasColumn('bills', 'is_reverse_charge')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->boolean('is_reverse_charge')->default(false)->after('status');
                $table->index('is_reverse_charge');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('invoices', 'is_reverse_charge')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropIndex(['is_reverse_charge']);
                $table->dropColumn('is_reverse_charge');
            });
        }

        if (Schema::hasColumn('bills', 'is_reverse_charge')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->dropIndex(['is_reverse_charge']);
                $table->dropColumn('is_reverse_charge');
            });
        }
    }
};

// CLAUDE-CHECKPOINT
