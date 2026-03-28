<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add source column to fiscal_receipts to track how receipts were printed.
 *
 * Values:
 *   'server'    — Printed via server-side driver (ErpNet.FP sidecar)
 *   'webserial' — Printed via browser WebSerial API (direct USB connection)
 *   'manual'    — Manually recorded (not printed by system)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fiscal_receipts')) {
            return;
        }

        if (Schema::hasColumn('fiscal_receipts', 'source')) {
            return;
        }

        Schema::table('fiscal_receipts', function (Blueprint $table) {
            $table->string('source', 20)->default('server')->after('metadata');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fiscal_receipts')) {
            return;
        }

        if (Schema::hasColumn('fiscal_receipts', 'source')) {
            Schema::table('fiscal_receipts', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
