<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payroll_employees') && !Schema::hasColumn('payroll_employees', 'occupation_code')) {
            Schema::table('payroll_employees', function (Blueprint $table) {
                $table->string('occupation_code', 10)->nullable()->after('position')
                    ->comment('National Classification of Occupations code (шифра на занимање)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payroll_employees', 'occupation_code')) {
            Schema::table('payroll_employees', function (Blueprint $table) {
                $table->dropColumn('occupation_code');
            });
        }
    }
};

