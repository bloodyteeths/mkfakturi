<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix expenses with NULL base_amount by calculating from amount * exchange_rate
 */
return new class extends Migration
{
    public function up(): void
    {
        // Update expenses where base_amount is NULL
        DB::statement('
            UPDATE expenses
            SET base_amount = amount * COALESCE(exchange_rate, 1)
            WHERE base_amount IS NULL AND amount IS NOT NULL
        ');
    }

    public function down(): void
    {
        // Cannot reliably reverse this - the original NULL values are lost
    }
};
