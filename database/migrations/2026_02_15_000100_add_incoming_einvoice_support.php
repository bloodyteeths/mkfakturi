<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * P7-02: Add incoming e-invoice support columns to e_invoices table.
 *
 * Adds direction, sender info, review tracking, and portal inbox reference
 * to support the inbound e-invoice acceptance workflow.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('e_invoices')) {
            return;
        }

        Schema::table('e_invoices', function (Blueprint $table) {
            // Direction: outbound (default, existing) or inbound (new)
            if (! Schema::hasColumn('e_invoices', 'direction')) {
                $table->enum('direction', ['outbound', 'inbound'])
                    ->default('outbound')
                    ->after('status')
                    ->comment('Whether this e-invoice was sent or received');
            }

            // Sender info for inbound e-invoices
            if (! Schema::hasColumn('e_invoices', 'sender_vat_id')) {
                $table->string('sender_vat_id', 20)
                    ->nullable()
                    ->after('direction')
                    ->comment('VAT ID of the sender (for inbound invoices)');
            }

            if (! Schema::hasColumn('e_invoices', 'sender_name')) {
                $table->string('sender_name', 255)
                    ->nullable()
                    ->after('sender_vat_id')
                    ->comment('Business name of the sender (for inbound invoices)');
            }

            // Portal inbox reference
            if (! Schema::hasColumn('e_invoices', 'portal_inbox_id')) {
                $table->string('portal_inbox_id', 100)
                    ->nullable()
                    ->after('sender_name')
                    ->comment('Reference ID from UJP portal inbox');
            }

            // Received timestamp (when pulled from portal)
            if (! Schema::hasColumn('e_invoices', 'received_at')) {
                $table->timestamp('received_at')
                    ->nullable()
                    ->after('submitted_at')
                    ->comment('When the inbound e-invoice was received from portal');
            }

            // Review tracking
            if (! Schema::hasColumn('e_invoices', 'reviewed_at')) {
                $table->timestamp('reviewed_at')
                    ->nullable()
                    ->after('received_at')
                    ->comment('When the inbound e-invoice was reviewed (accepted/rejected)');
            }

            if (! Schema::hasColumn('e_invoices', 'reviewed_by')) {
                $table->unsignedInteger('reviewed_by')
                    ->nullable()
                    ->after('reviewed_at')
                    ->comment('User ID who reviewed the inbound e-invoice');
            }
        });

        // Add new status values to the enum (MySQL only — SQLite uses strings natively)
        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('e_invoices', 'status')) {
            DB::statement("ALTER TABLE `e_invoices` MODIFY COLUMN `status` ENUM(
                'DRAFT',
                'SIGNED',
                'SUBMITTED',
                'ACCEPTED',
                'REJECTED',
                'FAILED',
                'RECEIVED',
                'UNDER_REVIEW',
                'ACCEPTED_INCOMING',
                'REJECTED_INCOMING'
            ) DEFAULT 'DRAFT'");
        }

        // Add composite index for efficient inbox queries (idempotent)
        try {
            Schema::table('e_invoices', function (Blueprint $table) {
                $table->index(
                    ['company_id', 'direction', 'status'],
                    'e_invoices_company_direction_status_index'
                );
            });
        } catch (\Exception $e) {
            // Index already exists — skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('e_invoices')) {
            return;
        }

        try {
            Schema::table('e_invoices', function (Blueprint $table) {
                $table->dropIndex('e_invoices_company_direction_status_index');
            });
        } catch (\Exception $e) {
            // Index doesn't exist — skip
        }

        // Revert enum to original values (MySQL only)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `e_invoices` MODIFY COLUMN `status` ENUM(
                'DRAFT',
                'SIGNED',
                'SUBMITTED',
                'ACCEPTED',
                'REJECTED',
                'FAILED'
            ) DEFAULT 'DRAFT'");
        }

        Schema::table('e_invoices', function (Blueprint $table) {
            $columns = [
                'direction',
                'sender_vat_id',
                'sender_name',
                'portal_inbox_id',
                'received_at',
                'reviewed_at',
                'reviewed_by',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('e_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
