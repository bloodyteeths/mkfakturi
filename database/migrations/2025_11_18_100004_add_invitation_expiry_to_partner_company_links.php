<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add invitation expiry support (AC-11)
     */
    public function up(): void
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('accepted_at');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn('expires_at');
        });
    }
};

// CLAUDE-CHECKPOINT
