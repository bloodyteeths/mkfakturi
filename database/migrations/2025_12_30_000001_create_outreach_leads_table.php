<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Core lead storage for outreach automation
     */
    public function up(): void
    {
        // CLAUDE-CHECKPOINT
        if (Schema::hasTable('outreach_leads')) {
            // Add missing columns if table exists
            Schema::table('outreach_leads', function (Blueprint $table) {
                if (!Schema::hasColumn('outreach_leads', 'website')) {
                    $table->string('website')->nullable()->after('city');
                }
                if (!Schema::hasColumn('outreach_leads', 'last_contacted_at')) {
                    $table->timestamp('last_contacted_at')->nullable()->after('next_followup_at');
                }
            });
            return;
        }

        Schema::create('outreach_leads', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('company_name')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('website')->nullable();
            $table->string('source', 50)->default('manual'); // isos, smetkovoditeli, manual
            $table->string('source_url')->nullable();
            $table->string('status', 30)->default('new'); // new, emailed, followup, interested, invite_sent, partner_active, lost
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->timestamp('next_followup_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'next_followup_at']);
            $table->index('source');

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
        Schema::dropIfExists('outreach_leads');
    }
};
