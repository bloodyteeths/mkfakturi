<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL FIX: Macedonian Denar (MKD) must have precision=0
     *
     * MKD is a zero-decimal currency (no cents/denars like USD has cents)
     * Using precision=2 causes issues with v-money3 component where:
     * - User types "120000" expecting 120,000 ден
     * - System interprets as 1,200.00 ден (divides by 100)
     * - Or v-money3 emits null for formatted input
     *
     * This migration fixes any MKD currencies that were seeded with wrong precision
     */
    public function up(): void
    {
        DB::table('currencies')
            ->where('code', 'MKD')
            ->update([
                'precision' => 0,
                'thousand_separator' => '.',
                'decimal_separator' => ',',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Intentionally not reversing - precision=0 is the correct value
        // If needed to revert for testing: UPDATE currencies SET precision=2 WHERE code='MKD'
    }
};
