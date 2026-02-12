<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P7-04: Add overtime tracking fields to payroll run lines.
 *
 * Macedonian labor law requires overtime at:
 * - 135% for regular overtime
 * - 150% for holiday/night work
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payroll_run_lines')) {
            return;
        }

        if (! Schema::hasColumn('payroll_run_lines', 'overtime_hours')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                $table->decimal('overtime_hours', 5, 2)->default(0)
                    ->after('leave_deduction_amount')
                    ->comment('Overtime hours worked in the period');
                $table->decimal('overtime_multiplier', 3, 2)->default(1.35)
                    ->after('overtime_hours')
                    ->comment('Overtime rate multiplier (1.35 regular, 1.50 holiday/night)');
                $table->integer('overtime_amount')->default(0)
                    ->after('overtime_multiplier')
                    ->comment('Overtime premium amount in cents (added to gross)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_run_lines', 'overtime_hours')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                $table->dropColumn(['overtime_hours', 'overtime_multiplier', 'overtime_amount']);
            });
        }
    }
};
// CLAUDE-CHECKPOINT
