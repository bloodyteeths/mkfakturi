<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tracks welcome drip emails sent to companies and partners after signup.
     */
    public function up(): void
    {
        if (Schema::hasTable('welcome_sends')) {
            return;
        }

        Schema::create('welcome_sends', function (Blueprint $table) {
            $table->id();
            $table->string('sendable_type'); // 'App\Models\Company' or 'App\Models\Partner'
            $table->unsignedBigInteger('sendable_id');
            $table->string('email');
            $table->string('template_key', 50); // 'company_1', 'partner_3', etc.
            $table->string('status', 20)->default('queued'); // queued, sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['sendable_type', 'sendable_id', 'template_key'], 'idx_welcome_drip');
            $table->index('status', 'idx_welcome_status');

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
        Schema::dropIfExists('welcome_sends');
    }
};
