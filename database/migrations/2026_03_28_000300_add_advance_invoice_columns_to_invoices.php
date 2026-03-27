<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add advance invoice support to invoices table.
 *
 * - `type`: 'standard' (default), 'advance', or 'final'
 * - `parent_invoice_id`: links advance invoices to their final invoice
 *
 * Legal basis: Член 14 + Член 53 ЗДДВ (Macedonian VAT law)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('invoices', 'type')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->string('type', 20)->default('standard')->after('status');
            $table->unsignedInteger('parent_invoice_id')->nullable()->after('type');
            $table->index('type');
            $table->index('parent_invoice_id');
            $table->foreign('parent_invoice_id')
                ->references('id')
                ->on('invoices')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('invoices', 'type')) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['parent_invoice_id']);
            $table->dropIndex(['parent_invoice_id']);
            $table->dropIndex(['type']);
            $table->dropColumn(['parent_invoice_id', 'type']);
        });
    }
};

// CLAUDE-CHECKPOINT
