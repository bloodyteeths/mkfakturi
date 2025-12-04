<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes company_id nullable to support Stripe account-level webhooks
     * that don't have company_id in metadata.
     */
    public function up(): void
    {
        Schema::table('gateway_webhook_events', function (Blueprint $table) {
            // Drop the existing foreign key first
            $table->dropForeign(['company_id']);

            // Make company_id nullable
            $table->unsignedInteger('company_id')->nullable()->change();

            // Re-add foreign key with nullable support
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gateway_webhook_events', function (Blueprint $table) {
            // Drop the foreign key
            $table->dropForeign(['company_id']);

            // Make company_id required again
            $table->unsignedInteger('company_id')->nullable(false)->change();

            // Re-add foreign key
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }
};
// CLAUDE-CHECKPOINT
