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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('paddle_id')->nullable()->unique()->after('ifrs_entity_id');
            $table->string('subscription_tier')->default('free')->after('paddle_id');
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_tier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['paddle_id', 'subscription_tier', 'trial_ends_at']);
        });
    }
}; // CLAUDE-CHECKPOINT
