<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Affiliate Links - tracking unique referral codes for partners
        Schema::create('affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id'); // The accountant/partner who owns this link
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->string('code', 50)->unique(); // Unique referral code (e.g., "ACCT_JOHN_2025")
            $table->string('target')->default('company'); // What this link is for: company, accountant
            $table->text('description')->nullable(); // Optional description
            $table->boolean('is_active')->default(true);
            $table->integer('clicks')->default(0); // Track click count
            $table->integer('conversions')->default(0); // Track successful signups
            $table->timestamps();

            $table->index(['partner_id', 'is_active']);
            $table->index('code');
        });

        // Affiliate Events - track all commission-generating events
        Schema::create('affiliate_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('affiliate_partner_id'); // The partner earning commission
            $table->foreign('affiliate_partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->unsignedBigInteger('upline_partner_id')->nullable(); // The upline partner (if multi-level)
            $table->foreign('upline_partner_id')->references('id')->on('partners')->onDelete('set null');
            $table->unsignedInteger('company_id'); // The company this event relates to
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->string('event_type'); // recurring_commission, company_bounty, accountant_bounty, clawback
            $table->decimal('amount', 15, 2); // Commission amount in EUR
            $table->decimal('upline_amount', 15, 2)->nullable(); // Upline commission amount (if applicable)
            $table->string('month_ref', 7)->nullable(); // Reference month (YYYY-MM) for recurring commissions
            $table->string('subscription_id')->nullable(); // Reference to subscription/payment
            $table->boolean('is_clawed_back')->default(false); // If this was clawed back due to refund
            $table->timestamp('paid_at')->nullable(); // When this was included in a payout
            $table->unsignedBigInteger('payout_id')->nullable(); // Link to payout record
            $table->json('metadata')->nullable(); // Additional event data
            $table->timestamps();

            $table->index(['affiliate_partner_id', 'paid_at']);
            $table->index(['upline_partner_id', 'paid_at']);
            $table->index(['company_id', 'event_type']);
            $table->index('month_ref');
            $table->index('subscription_id');
        });

        // Payouts - monthly payout records for partners
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->decimal('amount', 15, 2); // Total payout amount
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->date('payout_date'); // Scheduled/actual payout date
            $table->string('payment_method')->nullable(); // bank_transfer, paypal, etc.
            $table->string('payment_reference')->nullable(); // Transaction ID from payment provider
            $table->json('details')->nullable(); // Breakdown of events included in this payout
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable(); // When payment was processed
            $table->unsignedInteger('processed_by')->nullable(); // Admin who processed
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['partner_id', 'status']);
            $table->index(['payout_date', 'status']);
        });

        // Add referral tracking fields to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('ref_code', 50)->nullable()->unique()->after('role'); // User's own referral code
            $table->unsignedInteger('referrer_user_id')->nullable()->after('ref_code'); // Who referred this user
            $table->foreign('referrer_user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('referred_at')->nullable()->after('referrer_user_id'); // When they signed up via referral

            $table->index('ref_code');
            $table->index('referrer_user_id');
        });

        // Add foreign key from affiliate_events to payouts (circular reference handled via nullable)
        Schema::table('affiliate_events', function (Blueprint $table) {
            $table->foreign('payout_id')->references('id')->on('payouts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign keys first
        Schema::table('affiliate_events', function (Blueprint $table) {
            $table->dropForeign(['payout_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referrer_user_id']);
            $table->dropColumn(['ref_code', 'referrer_user_id', 'referred_at']);
        });

        Schema::dropIfExists('payouts');
        Schema::dropIfExists('affiliate_events');
        Schema::dropIfExists('affiliate_links');
    }
};

// CLAUDE-CHECKPOINT
