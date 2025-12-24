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
        if (Schema::hasTable('payroll_run_lines')) {
            return;
        }

        Schema::create('payroll_run_lines', function (Blueprint $table) {
            $table->increments('id');

            // Payroll run relationship
            $table->unsignedInteger('payroll_run_id');
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->onDelete('cascade');

            // Employee relationship
            $table->unsignedInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('payroll_employees')->onDelete('restrict');

            // Working days calculation
            $table->tinyInteger('working_days')->unsigned()->default(22);
            $table->tinyInteger('worked_days')->unsigned()->default(22);

            // Salary amounts (all in cents)
            $table->unsignedBigInteger('gross_salary');
            $table->unsignedBigInteger('net_salary');

            // Tax and contributions (all in cents)
            $table->unsignedBigInteger('income_tax_amount')->default(0);
            $table->unsignedBigInteger('pension_contribution_employee')->default(0);
            $table->unsignedBigInteger('pension_contribution_employer')->default(0);
            $table->unsignedBigInteger('health_contribution_employee')->default(0);
            $table->unsignedBigInteger('health_contribution_employer')->default(0);
            $table->unsignedBigInteger('unemployment_contribution')->default(0);
            $table->unsignedBigInteger('additional_contribution')->default(0);

            // Allowances (all in cents)
            $table->unsignedBigInteger('transport_allowance')->default(0);
            $table->unsignedBigInteger('meal_allowance')->default(0);

            // Other additions and deductions
            $table->json('other_additions')->nullable()->comment('Additional payments as JSON');
            $table->json('deductions')->nullable()->comment('Deductions as JSON');

            // Status
            $table->enum('status', ['included', 'excluded'])->default('included')->index();

            $table->timestamps();

            // Indexes
            $table->index('payroll_run_id');
            $table->index('employee_id');
            $table->index(['payroll_run_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_run_lines');
    }
};

// LLM-CHECKPOINT
