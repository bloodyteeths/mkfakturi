<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds Stripe transfer tracking fields to payouts table
     */
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            // Stripe transfer ID (tr_xxx) when payout is via Stripe Connect
            $table->string('stripe_transfer_id')->nullable()->after('payment_reference');

            // Stripe payout ID (po_xxx) for tracking the actual bank payout
            $table->string('stripe_payout_id')->nullable()->after('stripe_transfer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_transfer_id',
                'stripe_payout_id',
            ]);
        });
    }
};
// CLAUDE-CHECKPOINT
