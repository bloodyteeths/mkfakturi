<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Webhook events from Postmark
     */
    public function up(): void
    {
        // CLAUDE-CHECKPOINT
        if (Schema::hasTable('outreach_events')) {
            // Add missing columns if table exists
            Schema::table('outreach_events', function (Blueprint $table) {
                if (!Schema::hasColumn('outreach_events', 'processed_at')) {
                    $table->timestamp('processed_at')->nullable()->after('recipient_email');
                }
            });
            return;
        }

        Schema::create('outreach_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->default('postmark'); // postmark
            $table->string('event_id', 100)->nullable();
            $table->string('event_type', 30); // Delivery, Open, Click, Bounce, SpamComplaint
            $table->string('postmark_message_id', 100)->nullable()->index();
            $table->string('recipient_email')->nullable()->index();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);

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
        Schema::dropIfExists('outreach_events');
    }
};
