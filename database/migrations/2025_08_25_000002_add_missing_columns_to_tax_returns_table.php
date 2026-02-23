<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing columns to tax_returns table.
 *
 * The original migration created the table with xml_path/exact_xml_submitted/receipt_number
 * but the TaxReturn model expects return_data JSON for storing structured form data,
 * plus accepted_at/rejected_at/rejection_reason for status tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_returns', function (Blueprint $table) {
            if (!Schema::hasColumn('tax_returns', 'return_data')) {
                $table->json('return_data')->nullable()->after('status')
                    ->comment('Structured tax return form data (JSON)');
            }
            if (!Schema::hasColumn('tax_returns', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('response_data')
                    ->comment('When accepted by tax authority');
            }
            if (!Schema::hasColumn('tax_returns', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('accepted_at')
                    ->comment('When rejected by tax authority');
            }
            if (!Schema::hasColumn('tax_returns', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at')
                    ->comment('Reason for rejection by tax authority');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tax_returns', function (Blueprint $table) {
            $table->dropColumn(['return_data', 'accepted_at', 'rejected_at', 'rejection_reason']);
        });
    }
};
