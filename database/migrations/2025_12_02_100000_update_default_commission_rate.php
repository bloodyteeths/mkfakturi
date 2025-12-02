<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Update default commission rate to 20% and set existing partners to 20%
     */
    public function up(): void
    {
        // Update existing partners with 0% commission to 20%
        DB::table('partners')
            ->where('commission_rate', 0)
            ->orWhereNull('commission_rate')
            ->update(['commission_rate' => 20.00]);

        // Change column default to 20%
        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(20.00)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->change();
        });
    }
};

// CLAUDE-CHECKPOINT
