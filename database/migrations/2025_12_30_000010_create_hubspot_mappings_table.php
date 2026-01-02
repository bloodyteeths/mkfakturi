<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Maps Facturino outreach leads to HubSpot CRM entity IDs
     */
    public function up(): void
    {
        // CLAUDE-CHECKPOINT
        if (Schema::hasTable('hubspot_mappings')) {
            return;
        }

        Schema::create('hubspot_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->unsignedBigInteger('outreach_lead_id')->nullable()->index();
            $table->string('hubspot_contact_id', 50)->nullable()->index();
            $table->string('hubspot_company_id', 50)->nullable()->index();
            $table->string('hubspot_deal_id', 50)->nullable()->index();
            $table->string('deal_stage', 50)->nullable();
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
        Schema::dropIfExists('hubspot_mappings');
    }
};
