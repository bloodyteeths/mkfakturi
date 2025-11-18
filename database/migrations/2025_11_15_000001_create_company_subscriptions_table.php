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
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->increments('id');

            // Core relationships
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');

            // Optional accountant who brought this company (for commission tracking)
            $table->integer('accountant_id')->unsigned()->nullable();
            $table->foreign('accountant_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            // Plan details
            $table->enum('plan', ['free', 'starter', 'standard', 'business', 'max'])
                ->default('free')
                ->index('idx_company_subscriptions_plan');

            // Payment provider details
            $table->enum('provider', ['paddle', 'cpay'])
                ->nullable()
                ->index('idx_company_subscriptions_provider');

            $table->string('provider_subscription_id', 255)->nullable()->unique();

            // Pricing
            $table->decimal('price_monthly', 10, 2)->default(0.00);

            // Subscription status
            $table->enum('status', ['trial', 'active', 'past_due', 'paused', 'canceled'])
                ->default('trial')
                ->index('idx_company_subscriptions_status');

            // Important dates
            $table->timestamp('started_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['company_id', 'status'], 'idx_company_status');
            $table->index(['accountant_id', 'status'], 'idx_accountant_status');
        }).' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
// CLAUDE-CHECKPOINT
