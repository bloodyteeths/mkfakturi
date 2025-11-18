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
        if (! Schema::hasTable('e_invoices')) {
            return;
        }

        Schema::table('e_invoices', function (Blueprint $table) {
            // Certificate reference
            if (! Schema::hasColumn('e_invoices', 'certificate_id')) {
                $table->unsignedBigInteger('certificate_id')->nullable()->after('status');
                $table->foreign('certificate_id')
                    ->references('id')
                    ->on('certificates')
                    ->onDelete('set null');
            }

            // Certificate subject and issuer details
            if (! Schema::hasColumn('e_invoices', 'subject')) {
                $table->json('subject')->nullable()->after('certificate_id')
                    ->comment('Certificate subject DN details');
            }

            if (! Schema::hasColumn('e_invoices', 'issuer')) {
                $table->json('issuer')->nullable()->after('subject')
                    ->comment('Certificate issuer DN details');
            }

            // Timestamp columns
            if (! Schema::hasColumn('e_invoices', 'signed_at')) {
                $table->timestamp('signed_at')->nullable()->after('issuer')
                    ->comment('When the e-invoice was digitally signed');
            }

            if (! Schema::hasColumn('e_invoices', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('signed_at')
                    ->comment('When the e-invoice was submitted to tax authority');
            }

            if (! Schema::hasColumn('e_invoices', 'accepted_at')) {
                $table->timestamp('accepted_at')->nullable()->after('submitted_at')
                    ->comment('When the e-invoice was accepted by tax authority');
            }

            if (! Schema::hasColumn('e_invoices', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('accepted_at')
                    ->comment('When the e-invoice was rejected by tax authority');
            }

            // Rejection reason
            if (! Schema::hasColumn('e_invoices', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at')
                    ->comment('Reason for rejection or failure');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('e_invoices')) {
            return;
        }

        Schema::table('e_invoices', function (Blueprint $table) {
            // Drop foreign key first, then columns
            if (Schema::hasColumn('e_invoices', 'certificate_id')) {
                $table->dropForeign(['certificate_id']);
                $table->dropColumn('certificate_id');
            }

            $columns = ['subject', 'issuer', 'signed_at', 'submitted_at', 'accepted_at', 'rejected_at', 'rejection_reason'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('e_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
// CLAUDE-CHECKPOINT
