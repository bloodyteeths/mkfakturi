<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Sales Rep Tracking to Users (AC-01-13)
 *
 * Enables 3-way commission split:
 * - Direct accountant: 15%
 * - Upline (referrer): 5%
 * - Sales rep: 5%
 * Total: 25% of company subscription goes to affiliates
 *
 * @ticket AC-01-13
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Sales rep who brought this accountant/user into the system
            // Typically a Facturino employee or agency partner
            $table->unsignedInteger('sales_rep_id')->nullable()->after('referrer_user_id');
            $table->foreign('sales_rep_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // Track when sales rep relationship was established
            $table->timestamp('sales_rep_assigned_at')->nullable()->after('sales_rep_id');

            // Index for sales rep commission queries
            $table->index('sales_rep_id', 'idx_users_sales_rep');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sales_rep_id']);
            $table->dropIndex('idx_users_sales_rep');
            $table->dropColumn(['sales_rep_id', 'sales_rep_assigned_at']);
        });
    }
};

// CLAUDE-CHECKPOINT
