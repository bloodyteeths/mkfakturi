<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create inventory_documents table for formal inventory documents
 * (приемница/издатница/преносница).
 *
 * Idempotent: uses Schema::hasTable() check for safe re-runs on Railway deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_documents')) {
            return;
        }

        Schema::create('inventory_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->enum('document_type', ['receipt', 'issue', 'transfer']);
            $table->string('document_number');
            $table->unsignedBigInteger('warehouse_id');
            $table->unsignedBigInteger('destination_warehouse_id')->nullable();
            $table->date('document_date');
            $table->enum('status', ['draft', 'approved', 'voided'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('total_value')->default(0);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')->on('companies')
                ->onDelete('cascade');

            $table->foreign('warehouse_id')
                ->references('id')->on('warehouses')
                ->onDelete('restrict');

            $table->foreign('destination_warehouse_id')
                ->references('id')->on('warehouses')
                ->onDelete('restrict');

            $table->foreign('approved_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            $table->foreign('created_by')
                ->references('id')->on('users')
                ->onDelete('set null');

            // Indexes
            $table->unique(['company_id', 'document_number']);
            $table->index('document_type');
            $table->index('document_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_documents');
    }
};
