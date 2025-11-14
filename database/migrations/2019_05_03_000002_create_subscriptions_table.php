<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: Renamed from 'subscriptions' to 'paddle_subscriptions' for clarity.
     * This is Laravel Cashier Paddle's subscriptions table (billing).
     */
    public function up(): void
    {
        Schema::create('paddle_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->string('type');
            $table->string('paddle_id')->unique();
            $table->string('status');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paddle_subscriptions');
    }
};
// CLAUDE-CHECKPOINT: Renamed Paddle Cashier subscriptions table to paddle_subscriptions
