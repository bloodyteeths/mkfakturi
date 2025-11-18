<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds Facturino-specific columns to track subscription provider and tier
     * NOTE: Updated to use 'paddle_subscriptions' table name
     */
    public function up(): void
    {
        Schema::table('paddle_subscriptions', function (Blueprint $table) {
            $table->string('provider')->default('paddle')->after('type')
                ->comment('Payment provider: paddle or cpay');
            $table->string('tier')->nullable()->after('provider')
                ->comment('Subscription tier: free, starter, standard, business, max, partner_plus');
            $table->decimal('monthly_price', 10, 2)->nullable()->after('tier')
                ->comment('Monthly subscription price in EUR');
            $table->json('metadata')->nullable()->after('monthly_price')
                ->comment('Additional subscription metadata');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('paddle_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['provider', 'tier', 'monthly_price', 'metadata']);
        });
    }
}; // CLAUDE-CHECKPOINT
