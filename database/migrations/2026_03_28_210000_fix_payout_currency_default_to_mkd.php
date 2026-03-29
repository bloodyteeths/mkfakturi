<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix currency default from EUR to MKD and update existing records.
     * The system operates in MKD but the original migration defaulted to EUR.
     */
    public function up(): void
    {
        // Update existing EUR records to MKD (all payouts are in MKD)
        DB::table('payouts')
            ->where('currency', 'EUR')
            ->update(['currency' => 'MKD']);

        // Change the column default
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('currency', 3)->default('MKD')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('currency', 3)->default('EUR')->change();
        });
    }
};
