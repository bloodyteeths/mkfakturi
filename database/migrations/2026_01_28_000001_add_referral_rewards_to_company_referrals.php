<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add reward tracking columns to company_referrals table.
     * Supports company-to-company referral rewards:
     * - Invitee gets 10% off first payment
     * - Inviter gets 10% off next billing cycle
     */
    public function up(): void
    {
        if (! Schema::hasTable('company_referrals')) {
            return;
        }

        Schema::table('company_referrals', function (Blueprint $table) {
            // Stripe coupon IDs for tracking
            if (! Schema::hasColumn('company_referrals', 'invitee_stripe_coupon_id')) {
                $table->string('invitee_stripe_coupon_id', 255)->nullable()->after('status');
            }

            if (! Schema::hasColumn('company_referrals', 'inviter_stripe_coupon_id')) {
                $table->string('inviter_stripe_coupon_id', 255)->nullable()->after('invitee_stripe_coupon_id');
            }

            // Timestamps for when discounts were applied
            if (! Schema::hasColumn('company_referrals', 'invitee_discount_applied_at')) {
                $table->timestamp('invitee_discount_applied_at')->nullable()->after('inviter_stripe_coupon_id');
            }

            if (! Schema::hasColumn('company_referrals', 'inviter_discount_applied_at')) {
                $table->timestamp('inviter_discount_applied_at')->nullable()->after('invitee_discount_applied_at');
            }

            // Reward status tracking
            if (! Schema::hasColumn('company_referrals', 'referral_reward_status')) {
                $table->enum('referral_reward_status', ['pending', 'invitee_rewarded', 'both_rewarded', 'expired'])
                    ->default('pending')
                    ->after('inviter_discount_applied_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('company_referrals')) {
            return;
        }

        Schema::table('company_referrals', function (Blueprint $table) {
            $columns = [
                'invitee_stripe_coupon_id',
                'inviter_stripe_coupon_id',
                'invitee_discount_applied_at',
                'inviter_discount_applied_at',
                'referral_reward_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('company_referrals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

// CLAUDE-CHECKPOINT
