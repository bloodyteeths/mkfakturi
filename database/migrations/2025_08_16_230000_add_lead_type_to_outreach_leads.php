<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('outreach_leads', 'lead_type')) {
            Schema::table('outreach_leads', function (Blueprint $table) {
                $table->string('lead_type', 20)->default('company')->after('source_url');
                $table->string('sector', 50)->nullable()->after('lead_type');
            });

            // Classify existing leads: ISOS/smetkovoditeli sources → accountant
            DB::table('outreach_leads')
                ->whereIn('source', ['isos', 'smetkovoditeli'])
                ->update(['lead_type' => 'accountant']);

            // Also classify by partner invite campaign history
            DB::table('outreach_leads')
                ->whereIn('email', function ($q) {
                    $q->select('email')
                        ->from('outreach_sends')
                        ->where('template_key', 'first_touch');
                })
                ->where('source', 'postmark_import')
                ->update(['lead_type' => 'accountant']);

            Schema::table('outreach_leads', function (Blueprint $table) {
                $table->index('lead_type');
                $table->index('sector');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('outreach_leads', 'lead_type')) {
            Schema::table('outreach_leads', function (Blueprint $table) {
                $table->dropIndex(['lead_type']);
                $table->dropIndex(['sector']);
                $table->dropColumn(['lead_type', 'sector']);
            });
        }
    }
};
