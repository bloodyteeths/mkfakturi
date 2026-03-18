<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Stripe subscription columns for partner billing.
     * Replaces Paddle with Stripe for accountant tier subscriptions.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'stripe_customer_id')) {
                $table->string('stripe_customer_id')->nullable()->after('paddle_id')
                    ->comment('Partner Stripe customer ID');
            }
            if (!Schema::hasColumn('users', 'stripe_subscription_id')) {
                $table->string('stripe_subscription_id')->nullable()->after('stripe_customer_id')
                    ->comment('Partner Stripe subscription ID');
            }
            if (!Schema::hasColumn('users', 'partner_seat_count')) {
                $table->unsignedSmallInteger('partner_seat_count')->default(0)->after('stripe_subscription_id')
                    ->comment('Additional seats purchased by partner');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id', 'partner_seat_count']);
        });
    }
}; // CLAUDE-CHECKPOINT
