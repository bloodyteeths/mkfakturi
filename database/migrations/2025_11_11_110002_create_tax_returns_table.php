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
        if (Schema::hasTable('tax_returns')) {
            return;
        }

        Schema::create('tax_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            $table->unsignedBigInteger('period_id')->comment('Tax reporting period this return covers');
            $table->foreign('period_id')->references('id')->on('tax_report_periods')->onDelete('restrict');

            // Return metadata
            $table->string('return_type', 50)->default('ddv04')->comment('Tax return form type (e.g., ddv04, ddv05)');

            // Submission tracking
            $table->timestamp('submitted_at')->nullable()->comment('When the return was submitted to tax authority');
            $table->unsignedInteger('submitted_by')->nullable()->comment('User who submitted the return');
            $table->foreign('submitted_by')->references('id')->on('users')->onDelete('restrict');

            // Status tracking
            $table->enum('status', ['draft', 'filed', 'accepted', 'rejected', 'amended'])->default('draft')->comment('Current status of the return');

            // Submission data and response
            $table->string('xml_path', 255)->nullable()->comment('File path to generated XML file');
            $table->text('exact_xml_submitted')->nullable()->comment('Exact XML content that was submitted');
            $table->string('receipt_number', 100)->nullable()->comment('Receipt/confirmation number from tax authority');
            $table->json('response_data')->nullable()->comment('Full response from tax authority API');

            // Amendment tracking
            $table->unsignedBigInteger('amendment_of')->nullable()->comment('Original return ID if this is an amendment');
            $table->foreign('amendment_of')->references('id')->on('tax_returns')->onDelete('restrict');

            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('period_id');
            $table->index('status');
            $table->index('receipt_number');
            $table->index(['company_id', 'period_id']);
            $table->index(['company_id', 'status']);
            $table->index(['period_id', 'return_type', 'status']);
        }) ;
    }

// CLAUDE-CHECKPOINT

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_returns');
    }
};
