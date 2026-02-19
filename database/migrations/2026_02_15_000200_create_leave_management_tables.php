<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Leave Management Tables Migration
 *
 * Creates tables for the leave management system:
 * - leave_types: Configurable leave types per company (annual, sick, maternity, unpaid)
 * - leave_requests: Employee leave requests with approval workflow
 * - Adds leave tracking columns to payroll_run_lines
 *
 * Macedonian labor law mandates:
 * - 20-day minimum annual leave
 * - Sick leave at 70-100% gross for first 30 days (employer-funded)
 * - 9-month maternity leave at full pay
 *
 * IDEMPOTENT: Uses Schema::hasTable() / Schema::hasColumn() checks
 * for safe re-runs on Railway deployments.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Table 1: leave_types
        if (!Schema::hasTable('leave_types')) {
            Schema::create('leave_types', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('name', 100);
                $table->string('name_mk', 100);
                $table->string('code', 20); // ANNUAL, SICK, MATERNITY, UNPAID
                $table->unsignedSmallInteger('max_days_per_year')->default(20);
                $table->decimal('pay_percentage', 5, 2)->default(100.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->unique(['company_id', 'code']);
            });
        }

        // Table 2: leave_requests
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('leave_type_id');
                $table->date('start_date');
                $table->date('end_date');
                $table->unsignedSmallInteger('business_days');
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->foreign('employee_id')
                    ->references('id')
                    ->on('payroll_employees')
                    ->onDelete('restrict');

                $table->foreign('leave_type_id')
                    ->references('id')
                    ->on('leave_types')
                    ->onDelete('restrict');

                $table->index(['company_id', 'employee_id', 'status']);
                $table->index(['company_id', 'start_date', 'end_date']);
            });
        }

        // Add leave columns to payroll_run_lines
        if (!Schema::hasColumn('payroll_run_lines', 'leave_days_taken')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                $table->unsignedSmallInteger('leave_days_taken')->default(0)->after('worked_days');
                $table->integer('leave_deduction_amount')->default(0)->after('leave_days_taken');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove leave columns from payroll_run_lines
        if (Schema::hasColumn('payroll_run_lines', 'leave_days_taken')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                $table->dropColumn(['leave_days_taken', 'leave_deduction_amount']);
            });
        }

        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
    }
};

