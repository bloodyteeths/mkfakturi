<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * One-time tokens for unsubscribe links
     */
    public function up(): void
    {
        // CLAUDE-CHECKPOINT
        if (Schema::hasTable('unsubscribe_tokens')) {
            // Add missing columns if table exists
            Schema::table('unsubscribe_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('unsubscribe_tokens', 'outreach_lead_id')) {
                    $table->unsignedBigInteger('outreach_lead_id')->nullable()->after('email');
                }
            });
            return;
        }

        Schema::create('unsubscribe_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('email');
            $table->unsignedBigInteger('outreach_lead_id')->nullable();
            $table->boolean('used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['token', 'used']);

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
        Schema::dropIfExists('unsubscribe_tokens');
    }
};
