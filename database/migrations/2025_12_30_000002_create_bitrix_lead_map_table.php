<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Maps Facturino leads to Bitrix24 lead IDs
     */
    public function up(): void
    {
        // CLAUDE-CHECKPOINT
        if (Schema::hasTable('bitrix_lead_map')) {
            return;
        }

        Schema::create('bitrix_lead_map', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('bitrix_lead_id', 50)->unique()->index();
            $table->unsignedBigInteger('outreach_lead_id')->nullable();
            $table->string('bitrix_stage', 50)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitrix_lead_map');
    }
};
