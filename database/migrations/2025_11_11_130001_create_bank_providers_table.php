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
        Schema::create('bank_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique()->comment('Bank identifier: nlb, stopanska, komercijalna');
            $table->string('name', 255)->comment('Full bank name: NLB Banka, Stopanska Banka, etc.');
            $table->string('base_url', 255)->comment('API base URL for PSD2 endpoints');
            $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
            $table->boolean('supports_ais')->default(true)->comment('Account Information Service support');
            $table->boolean('supports_pis')->default(false)->comment('Payment Initiation Service support');
            $table->string('logo_url', 255)->nullable()->comment('Bank logo URL');
            $table->boolean('is_active')->default(true)->comment('Active status');
            $table->json('metadata')->nullable()->comment('Rate limits, special config, etc.');
            $table->timestamps();

            // Indexes for performance
            $table->index('key');
            $table->index('is_active');
        });
    }

    // CLAUDE-CHECKPOINT

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_providers');
    }
};
