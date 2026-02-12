<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P7-02: Add incoming e-invoice support fields to e_invoices table.
 *
 * Adds: direction, received_at, sender_vat_id, sender_name,
 * portal_inbox_id, reviewed_by, rejection_reason
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('e_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('e_invoices', 'direction')) {
                $table->string('direction', 20)->default('outbound')->after('status');
            }

            if (! Schema::hasColumn('e_invoices', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('submitted_at');
            }

            if (! Schema::hasColumn('e_invoices', 'sender_vat_id')) {
                $table->string('sender_vat_id', 50)->nullable()->after('direction');
            }

            if (! Schema::hasColumn('e_invoices', 'sender_name')) {
                $table->string('sender_name', 255)->nullable()->after('sender_vat_id');
            }

            if (! Schema::hasColumn('e_invoices', 'portal_inbox_id')) {
                $table->string('portal_inbox_id', 100)->nullable()->after('sender_name');
            }

            if (! Schema::hasColumn('e_invoices', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('portal_inbox_id');
            }

            if (! Schema::hasColumn('e_invoices', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('reviewed_by');
            }
        });

        // Add index for inbound queries
        Schema::table('e_invoices', function (Blueprint $table) {
            $indexes = collect(DB::select('SHOW INDEX FROM e_invoices'))->pluck('Key_name')->unique()->toArray();

            if (! in_array('e_invoices_direction_status_index', $indexes)) {
                $table->index(['direction', 'status'], 'e_invoices_direction_status_index');
            }

            if (! in_array('e_invoices_portal_inbox_id_index', $indexes)) {
                $table->index('portal_inbox_id', 'e_invoices_portal_inbox_id_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('e_invoices', function (Blueprint $table) {
            $table->dropIndex('e_invoices_direction_status_index');
            $table->dropIndex('e_invoices_portal_inbox_id_index');

            $table->dropColumn([
                'direction',
                'received_at',
                'sender_vat_id',
                'sender_name',
                'portal_inbox_id',
                'reviewed_by',
                'rejection_reason',
            ]);
        });
    }
};
// CLAUDE-CHECKPOINT
