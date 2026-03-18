<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create partner_usage_tracking table for portfolio-level usage metering.
     * Separate from usage_tracking (which has non-nullable company_id FK).
     */
    public function up(): void
    {
        if (!Schema::hasTable('partner_usage_tracking')) {
            Schema::create('partner_usage_tracking', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_id');
                $table->string('meter', 100)->comment('companies, ai_credits, bank_accounts, etc.');
                $table->unsignedInteger('count')->default(0);
                $table->string('period', 20)->comment('YYYY-MM or total');
                $table->timestamps();

                $table->unique(['partner_id', 'meter', 'period'], 'partner_usage_unique');
                $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');

                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_usage_tracking');
    }
}; // CLAUDE-CHECKPOINT
