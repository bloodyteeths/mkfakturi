<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P10-02: Add connection_type and serial_port to fiscal_devices.
 *
 * Macedonian fiscal devices connect via TCP/IP (printers like Daisy FX),
 * RS232 serial (most cash registers), or Bluetooth (Пелистерец).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fiscal_devices') && ! Schema::hasColumn('fiscal_devices', 'connection_type')) {
            Schema::table('fiscal_devices', function (Blueprint $table) {
                $table->string('connection_type', 20)->default('tcp')->after('port');
                $table->string('serial_port', 100)->nullable()->after('connection_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fiscal_devices')) {
            Schema::table('fiscal_devices', function (Blueprint $table) {
                if (Schema::hasColumn('fiscal_devices', 'connection_type')) {
                    $table->dropColumn('connection_type');
                }
                if (Schema::hasColumn('fiscal_devices', 'serial_port')) {
                    $table->dropColumn('serial_port');
                }
            });
        }
    }
};
