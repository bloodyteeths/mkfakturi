<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pos_shifts')) {
            return;
        }

        Schema::create('pos_shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('fiscal_device_id')->nullable();
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->bigInteger('opening_cash')->default(0);        // cents
            $table->bigInteger('closing_cash')->nullable();         // cents
            $table->bigInteger('total_sales')->default(0);          // cents
            $table->bigInteger('total_returns')->default(0);        // cents
            $table->integer('transactions_count')->default(0);
            $table->bigInteger('cash_difference')->nullable();      // cents (closing - expected)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('fiscal_device_id')->references('id')->on('fiscal_devices')->onDelete('set null');

            $table->index(['company_id', 'user_id', 'closed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_shifts');
    }
};

// CLAUDE-CHECKPOINT
