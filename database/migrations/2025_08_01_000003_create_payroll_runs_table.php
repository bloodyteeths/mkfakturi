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
        if (Schema::hasTable('payroll_runs')) {
            return;
        }

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->increments('id');

            // Company relationship
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            // Period information
            $table->smallInteger('period_year')->unsigned();
            $table->tinyInteger('period_month')->unsigned();
            $table->date('period_start');
            $table->date('period_end');

            // Status
            $table->enum('status', ['draft', 'calculated', 'approved', 'posted', 'paid'])
                ->default('draft')
                ->index();

            // Totals (all in cents)
            $table->unsignedBigInteger('total_gross')->default(0);
            $table->unsignedBigInteger('total_net')->default(0);
            $table->unsignedBigInteger('total_employer_tax')->default(0);
            $table->unsignedBigInteger('total_employee_tax')->default(0);

            // IFRS integration
            $table->string('ifrs_transaction_id')->nullable()->index();

            // Workflow timestamps
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Approval tracking
            $table->unsignedInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('restrict');

            // Bank file generation
            $table->timestamp('bank_file_generated_at')->nullable();
            $table->string('bank_file_path')->nullable();

            // Audit fields
            $table->unsignedInteger('creator_id')->nullable();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');

            $table->timestamps();

            // Unique constraint: one payroll run per company per period
            $table->unique(['company_id', 'period_year', 'period_month']);

            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'status']);
            $table->index(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};

// LLM-CHECKPOINT
