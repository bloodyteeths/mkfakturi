<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add expiry and deactivation support for affiliate links (AC-12, AC-15)
     */
    public function up(): void
    {
        Schema::table('affiliate_links', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_active');
            $table->timestamp('last_clicked_at')->nullable()->after('expires_at');
            $table->string('deactivation_reason')->nullable()->after('last_clicked_at');
            $table->timestamp('deactivated_at')->nullable()->after('deactivation_reason');

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affiliate_links', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['expires_at', 'last_clicked_at', 'deactivation_reason', 'deactivated_at']);
        });
    }
};

// CLAUDE-CHECKPOINT
