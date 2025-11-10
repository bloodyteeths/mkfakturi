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
        if (Schema::hasTable('gateway_webhook_events')) {
            return;
        }

        Schema::create('gateway_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Provider identification
            $table->string('provider'); // paddle, cpay, nlb, stopanska
            $table->string('event_type'); // transaction.completed, payment.succeeded, etc.
            $table->string('event_id')->nullable(); // Provider's event ID for idempotency

            // Webhook data
            $table->json('payload'); // Full webhook payload
            $table->text('signature')->nullable(); // Signature for verification

            // Processing status
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'created_at']);
            $table->index(['provider', 'event_type']);
            $table->index('status');
            $table->unique(['provider', 'event_id']); // Prevent duplicate event processing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gateway_webhook_events');
    }
};
// CLAUDE-CHECKPOINT
