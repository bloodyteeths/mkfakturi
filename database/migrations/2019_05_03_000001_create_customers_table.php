<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * NOTE: Renamed from 'customers' to 'paddle_customers' to avoid
     * conflict with InvoiceShelf's customers table (invoice recipients).
     * This is Laravel Cashier Paddle's customers table (billing).
     */
    public function up(): void
    {
        Schema::create('paddle_customers', function (Blueprint $table) {
            $table->id();
            $table->morphs('billable');
            $table->string('paddle_id')->unique();
            $table->string('name');
            $table->string('email');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paddle_customers');
    }
};
// CLAUDE-CHECKPOINT: Renamed Paddle Cashier customers table to paddle_customers
