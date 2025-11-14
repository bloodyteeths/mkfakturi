<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Sales Rep Commission Tracking to Affiliate Events (AC-01-14)
 *
 * Extends affiliate_events to track 3-way commission splits:
 * - amount: Direct accountant commission (15%)
 * - upline_amount: Upline commission (5%)
 * - sales_rep_amount: Sales rep commission (5%)
 *
 * @ticket AC-01-14
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('affiliate_events', function (Blueprint $table) {
            // Sales rep commission amount (5% of subscription)
            $table->decimal('sales_rep_amount', 15, 2)->nullable()->after('upline_amount');

            // Sales rep user ID (for direct lookup)
            $table->unsignedInteger('sales_rep_id')->nullable()->after('upline_partner_id');
            $table->foreign('sales_rep_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // Index for sales rep commission queries
            $table->index(['sales_rep_id', 'paid_at'], 'idx_affiliate_events_sales_rep');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_events', function (Blueprint $table) {
            $table->dropForeign(['sales_rep_id']);
            $table->dropIndex('idx_affiliate_events_sales_rep');
            $table->dropColumn(['sales_rep_amount', 'sales_rep_id']);
        });
    }
};

// CLAUDE-CHECKPOINT
