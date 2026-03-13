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
            if (! Schema::hasColumn('client_documents', 'linked_invoice_id')) {
                $table->unsignedInteger('linked_invoice_id')->nullable()->after('linked_expense_id');
            }
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
            if (Schema::hasColumn('client_documents', 'linked_invoice_id')) {
                $table->dropColumn('linked_invoice_id');
            }
        });
    }
}; // CLAUDE-CHECKPOINT
