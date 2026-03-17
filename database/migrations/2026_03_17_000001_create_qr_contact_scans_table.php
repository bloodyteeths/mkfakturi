<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('qr_contact_scans')) {
            return;
        }

        Schema::create('qr_contact_scans', function (Blueprint $table) {
            $table->id();
            $table->timestamp('scanned_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->default('unknown'); // mobile, desktop, tablet, unknown
            $table->string('country', 2)->nullable(); // ISO 3166-1 alpha-2
            $table->index('scanned_at');
            $table->index('device_type');
        }) /* ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 */;
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_contact_scans');
    }
};

// CLAUDE-CHECKPOINT
