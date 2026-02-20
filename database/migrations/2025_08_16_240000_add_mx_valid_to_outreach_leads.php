<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add MX validation columns to outreach_leads for email deliverability.
     */
    public function up(): void
    {
        if (! Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('outreach_leads', 'mx_valid')) {
                $table->boolean('mx_valid')->nullable()->after('sector')->index();
            }
            if (! Schema::hasColumn('outreach_leads', 'mx_checked_at')) {
                $table->timestamp('mx_checked_at')->nullable()->after('mx_valid');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            if (Schema::hasColumn('outreach_leads', 'mx_valid')) {
                $table->dropIndex(['mx_valid']);
                $table->dropColumn('mx_valid');
            }
            if (Schema::hasColumn('outreach_leads', 'mx_checked_at')) {
                $table->dropColumn('mx_checked_at');
            }
        });
    }
};
