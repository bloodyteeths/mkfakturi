<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add seniority bonus (минат труд) and night work premium fields
 * to payroll_run_lines per Macedonian labor law.
 *
 * Минат труд: 0.5% per year of service (Колективен договор / Закон за работни односи)
 * Night work: 35% premium for hours between 22:00-06:00 (Art. 105)
 *
 * Also seeds additional leave types per Art. 146-148.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_run_lines')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                if (!Schema::hasColumn('payroll_run_lines', 'seniority_years')) {
                    $table->unsignedSmallInteger('seniority_years')->default(0)->after('overtime_amount');
                }
                if (!Schema::hasColumn('payroll_run_lines', 'seniority_bonus')) {
                    $table->bigInteger('seniority_bonus')->default(0)->after('seniority_years');
                }
                if (!Schema::hasColumn('payroll_run_lines', 'night_hours')) {
                    $table->decimal('night_hours', 5, 2)->default(0)->after('seniority_bonus');
                }
                if (!Schema::hasColumn('payroll_run_lines', 'night_amount')) {
                    $table->bigInteger('night_amount')->default(0)->after('night_hours');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_run_lines')) {
            Schema::table('payroll_run_lines', function (Blueprint $table) {
                $columns = ['seniority_years', 'seniority_bonus', 'night_hours', 'night_amount'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('payroll_run_lines', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
