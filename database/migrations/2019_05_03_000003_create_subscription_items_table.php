<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: Renamed from 'subscription_items' to 'paddle_subscription_items' for clarity.
     * This is Laravel Cashier Paddle's subscription items table (billing).
     */
    public function up(): void
    {
        Schema::create('paddle_subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id');
            $table->string('product_id');
            $table->string('price_id');
            $table->string('status');
            $table->integer('quantity');
            $table->timestamps();

            $table->unique(['subscription_id', 'price_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paddle_subscription_items');
    }
};
// CLAUDE-CHECKPOINT: Renamed Paddle Cashier subscription_items table to paddle_subscription_items
