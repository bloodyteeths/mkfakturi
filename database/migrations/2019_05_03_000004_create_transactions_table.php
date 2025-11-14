<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: Renamed from 'transactions' to 'paddle_transactions' to avoid
     * conflict with InvoiceShelf's transactions table (invoice payments).
     * This is Laravel Cashier Paddle's transactions table (billing).
     */
    public function up(): void
    {
        Schema::create('paddle_transactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->string('paddle_id')->unique();
            $table->string('paddle_subscription_id')->nullable()->index();
            $table->string('invoice_number')->nullable();
            $table->string('status');
            $table->string('total');
            $table->string('tax');
            $table->string('currency', 3);
            $table->timestamp('billed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paddle_transactions');
    }
};
// CLAUDE-CHECKPOINT: Renamed Paddle Cashier transactions table to paddle_transactions
