<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Make certificate_id nullable to allow deletion logs without foreign key constraint violations.
     * When a certificate is deleted, we create a log entry with certificate_id = null,
     * and store the certificate details in the metadata JSON field for audit trail.
     */
    public function up(): void
    {
        Schema::table('signature_logs', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['certificate_id']);

            // Make the column nullable
            $table->unsignedBigInteger('certificate_id')->nullable()->change();

            // Re-add the foreign key with ON DELETE SET NULL
            $table->foreign('certificate_id')
                ->references('id')
                ->on('certificates')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_logs', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['certificate_id']);

            // Make column NOT NULL again
            $table->unsignedBigInteger('certificate_id')->nullable(false)->change();

            // Re-add the original foreign key with ON DELETE RESTRICT
            $table->foreign('certificate_id')
                ->references('id')
                ->on('certificates')
                ->onDelete('restrict');
        });
    }
};
