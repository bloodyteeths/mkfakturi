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
        if (Schema::hasTable('e_invoices')) {
            return;
        }

        Schema::create('e_invoices', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->unsignedInteger('invoice_id');
            $table->foreign('invoice_id')
                  ->references('id')
                  ->on('invoices')
                  ->onDelete('restrict');

            $table->unsignedInteger('company_id');
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('restrict');

            // UBL XML storage
            $table->text('ubl_xml')->nullable(); // UBL 2.1 XML content (unsigned)
            $table->text('ubl_xml_signed')->nullable(); // Digitally signed UBL XML

            // Status tracking
            $table->enum('status', [
                'DRAFT',      // Initial state, UBL generated but not signed
                'SIGNED',     // Digitally signed with QES certificate
                'SUBMITTED',  // Submitted to government portal
                'ACCEPTED',   // Accepted by government portal
                'REJECTED',   // Rejected by government portal
                'FAILED'      // Submission or processing failed
            ])->default('DRAFT');

            // Hash and file paths
            $table->string('hash', 64)->nullable(); // SHA256 hash of UBL XML
            $table->string('xml_path')->nullable(); // Path to stored UBL XML file
            $table->string('signed_xml_path')->nullable(); // Path to stored signed XML file

            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('invoice_id');
            $table->index('status');
            $table->index(['company_id', 'status']);
            $table->index(['invoice_id', 'status']);

        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('e_invoices');
    }
};
// CLAUDE-CHECKPOINT
