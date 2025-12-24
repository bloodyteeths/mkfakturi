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
        if (Schema::hasTable('payroll_employees')) {
            return;
        }

        Schema::create('payroll_employees', function (Blueprint $table) {
            $table->increments('id');

            // Company relationship
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            // User relationship (nullable - can create payroll employee without user account)
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');

            // Employee identification
            $table->string('employee_number')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('embg', 13)->comment('Macedonian personal ID - EMBG');

            // Banking information
            $table->string('bank_account_iban')->nullable();
            $table->string('bank_name')->nullable();

            // Employment details
            $table->date('employment_date');
            $table->date('termination_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract'])->default('full_time');
            $table->string('department')->nullable();
            $table->string('position')->nullable();

            // Salary information
            $table->unsignedBigInteger('base_salary_amount')->comment('Base salary in cents');
            $table->integer('currency_id')->unsigned();
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');

            // Status
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->unsignedInteger('creator_id')->nullable();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');

            $table->timestamps();
            $table->softDeletes();

            // Composite unique constraint
            $table->unique(['company_id', 'employee_number']);

            // Indexes
            $table->index('company_id');
            $table->index('is_active');
            $table->index('employment_date');
            $table->index(['company_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_employees');
    }
};

// LLM-CHECKPOINT
