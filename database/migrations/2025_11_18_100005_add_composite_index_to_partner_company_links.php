<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add composite index for permission checks (Performance optimization)
     */
    public function up(): void
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->index(['partner_id', 'company_id', 'is_active'], 'partner_company_active_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->dropIndex('partner_company_active_lookup');
        });
    }
};

// CLAUDE-CHECKPOINT
