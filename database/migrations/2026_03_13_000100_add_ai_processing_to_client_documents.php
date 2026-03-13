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
        if (! Schema::hasTable('client_documents')) {
            return;
        }

        Schema::table('client_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('client_documents', 'processing_status')) {
                $table->string('processing_status', 20)->default('pending')->after('status');
            }
            if (! Schema::hasColumn('client_documents', 'ai_classification')) {
                $table->json('ai_classification')->nullable()->after('processing_status');
            }
            if (! Schema::hasColumn('client_documents', 'extracted_data')) {
                $table->json('extracted_data')->nullable()->after('ai_classification');
            }
            if (! Schema::hasColumn('client_documents', 'linked_bill_id')) {
                $table->unsignedInteger('linked_bill_id')->nullable()->after('extracted_data');
            }
            if (! Schema::hasColumn('client_documents', 'linked_expense_id')) {
                $table->unsignedInteger('linked_expense_id')->nullable()->after('linked_bill_id');
            }
            if (! Schema::hasColumn('client_documents', 'extraction_method')) {
                $table->string('extraction_method', 50)->nullable()->after('linked_expense_id');
            }
            if (! Schema::hasColumn('client_documents', 'error_message')) {
                $table->text('error_message')->nullable()->after('extraction_method');
            }
        });

        // Add FK constraints and index separately to avoid issues with column existence checks
        Schema::table('client_documents', function (Blueprint $table) {
            $table->index('processing_status', 'idx_processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('client_documents')) {
            return;
        }

        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropIndex('idx_processing_status');

            $columns = [
                'processing_status', 'ai_classification', 'extracted_data',
                'linked_bill_id', 'linked_expense_id', 'extraction_method', 'error_message',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('client_documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}; // CLAUDE-CHECKPOINT
