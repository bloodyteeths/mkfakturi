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
        if (Schema::hasTable('salary_structures')) {
            return;
        }

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->increments('id');

            // Company relationship
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            // Employee relationship
            $table->unsignedInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('payroll_employees')->onDelete('cascade');

            // Effective dates
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            // Salary components (all in cents)
            $table->unsignedBigInteger('gross_salary');
            $table->unsignedBigInteger('transport_allowance')->default(0);
            $table->unsignedBigInteger('meal_allowance')->default(0);
            $table->json('other_allowances')->nullable()->comment('Additional allowances as JSON');

            // Status
            $table->boolean('is_current')->default(false)->index();

            $table->timestamps();

            // Indexes
            $table->index('company_id');
            $table->index('employee_id');
            $table->index('effective_from');
            $table->index(['employee_id', 'is_current']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_structures');
    }
};

// LLM-CHECKPOINT
