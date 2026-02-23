<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add SMTP-level verification columns to outreach_leads.
     * Complements MX validation with mailbox-level RCPT TO checks.
     */
    public function up(): void
    {
        if (! Schema::hasTable('outreach_leads')) {
            return;
        }

        Schema::table('outreach_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('outreach_leads', 'smtp_valid')) {
                $table->boolean('smtp_valid')->nullable()->after('mx_checked_at')->index();
            }
            if (! Schema::hasColumn('outreach_leads', 'smtp_checked_at')) {
                $table->timestamp('smtp_checked_at')->nullable()->after('smtp_valid');
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
            if (Schema::hasColumn('outreach_leads', 'smtp_valid')) {
                $table->dropIndex(['smtp_valid']);
                $table->dropColumn('smtp_valid');
            }
            if (Schema::hasColumn('outreach_leads', 'smtp_checked_at')) {
                $table->dropColumn('smtp_checked_at');
            }
        });
    }
};
