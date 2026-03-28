<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add personal_deduction column to payroll_run_lines.
     *
     * Stores the лично ослободување (personal tax deduction) per
     * Закон за данокот на личен доход, currently MKD 10,270/month.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_run_lines') && ! Schema::hasColumn('payroll_run_lines', 'personal_deduction')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                $table->unsignedBigInteger('personal_deduction')->default(0)->after('additional_contribution');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_run_lines', 'personal_deduction')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                $table->dropColumn('personal_deduction');
            });
        }
    }
};

// CLAUDE-CHECKPOINT
