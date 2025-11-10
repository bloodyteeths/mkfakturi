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
        if (Schema::hasTable('tax_report_periods')) {
            return;
        }

        Schema::create('tax_report_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            // Period definition
            $table->enum('period_type', ['monthly', 'quarterly', 'annual'])->comment('Type of tax reporting period');
            $table->integer('year')->comment('Calendar year of the period');
            $table->integer('month')->nullable()->comment('Month (1-12) for monthly periods, null otherwise');
            $table->integer('quarter')->nullable()->comment('Quarter (1-4) for quarterly periods, null otherwise');

            // Period dates
            $table->date('start_date')->comment('First day of the reporting period');
            $table->date('end_date')->comment('Last day of the reporting period');
            $table->date('due_date')->comment('Deadline for filing the tax return');

            // Status tracking
            $table->enum('status', ['open', 'closed', 'filed', 'amended'])->default('open')->comment('Current status of the period');

            // Lock/unlock audit trail
            $table->timestamp('locked_at')->nullable()->comment('When the period was locked/closed');
            $table->unsignedBigInteger('locked_by')->nullable()->comment('User who locked the period');
            $table->foreign('locked_by')->references('id')->on('users')->onDelete('restrict');

            $table->timestamp('reopened_at')->nullable()->comment('When the period was last reopened');
            $table->unsignedBigInteger('reopened_by')->nullable()->comment('User who reopened the period');
            $table->foreign('reopened_by')->references('id')->on('users')->onDelete('restrict');

            $table->text('reopen_reason')->nullable()->comment('Explanation for reopening the period');

            $table->timestamps();

            // Unique constraint: one period per company, type, year, month/quarter combination
            $table->unique(['company_id', 'period_type', 'year', 'month', 'quarter'], 'tax_periods_unique');

            // Indexes for performance
            $table->index('company_id');
            $table->index('status');
            $table->index('due_date');
            $table->index(['company_id', 'period_type', 'year']);
            $table->index(['company_id', 'status']);
        }) ;
    }

// CLAUDE-CHECKPOINT

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_report_periods');
    }
};
