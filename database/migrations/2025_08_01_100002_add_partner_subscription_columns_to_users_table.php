<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Partner Plus subscription tracking to users table
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('paddle_id')->nullable()->unique()->after('partner_tier');
            $table->string('partner_subscription_tier')->default('free')->after('paddle_id')
                  ->comment('Partner subscription: free or plus');
            $table->timestamp('partner_trial_ends_at')->nullable()->after('partner_subscription_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['paddle_id', 'partner_subscription_tier', 'partner_trial_ends_at']);
        });
    }
}; // CLAUDE-CHECKPOINT
