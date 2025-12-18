<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds missing currency and payout_method columns to payouts table
     */
    public function up(): void
    {
        if (! Schema::hasColumn('payouts', 'currency')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->string('currency', 3)->default('EUR')->after('amount');
            });
        }

        if (! Schema::hasColumn('payouts', 'payout_method')) {
            Schema::table('payouts', function (Blueprint $table) {
                $table->string('payout_method')->default('bank_transfer')->after('payout_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            if (Schema::hasColumn('payouts', 'currency')) {
                $table->dropColumn('currency');
            }
            if (Schema::hasColumn('payouts', 'payout_method')) {
                $table->dropColumn('payout_method');
            }
        });
    }
};
// CLAUDE-CHECKPOINT
