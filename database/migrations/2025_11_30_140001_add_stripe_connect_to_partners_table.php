<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds Stripe Connect fields to partners table for cross-border payouts to Macedonia
     */
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            // Stripe Connect Custom account ID (acct_xxx)
            $table->string('stripe_account_id')->nullable()->after('commission_rate');

            // Account status: pending, active, restricted, disabled
            $table->string('stripe_account_status')->nullable()->after('stripe_account_id');

            // When payouts were enabled
            $table->timestamp('stripe_payouts_enabled_at')->nullable()->after('stripe_account_status');

            // Payment method preference: stripe_connect, bank_transfer, wise
            $table->string('payment_method')->default('bank_transfer')->after('stripe_payouts_enabled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_account_id',
                'stripe_account_status',
                'stripe_payouts_enabled_at',
                'payment_method',
            ]);
        });
    }
};
// CLAUDE-CHECKPOINT
