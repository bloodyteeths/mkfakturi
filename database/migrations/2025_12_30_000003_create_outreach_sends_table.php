<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks every email sent
     */
    public function up(): void
    {
        // CLAUDE-CHECKPOINT
        if (Schema::hasTable('outreach_sends')) {
            // Add missing columns if table exists
            Schema::table('outreach_sends', function (Blueprint $table) {
                if (!Schema::hasColumn('outreach_sends', 'open_count')) {
                    $table->unsignedInteger('open_count')->default(0)->after('clicked_at');
                }
                if (!Schema::hasColumn('outreach_sends', 'click_count')) {
                    $table->unsignedInteger('click_count')->default(0)->after('open_count');
                }
            });
            return;
        }

        Schema::create('outreach_sends', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->unsignedBigInteger('outreach_lead_id')->nullable();
            $table->string('template_key', 50); // first_touch, followup_1, followup_2
            $table->string('postmark_message_id', 100)->nullable()->index();
            $table->string('status', 30)->default('queued'); // queued, sent, delivered, opened, clicked, bounced, complained
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();

            $table->index(['email', 'template_key']);
            $table->index('sent_at');

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
        Schema::dropIfExists('outreach_sends');
    }
};
