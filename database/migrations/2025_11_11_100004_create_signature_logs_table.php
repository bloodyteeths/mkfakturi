<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('signature_logs')) {
            return;
        }

        Schema::create('signature_logs', function (Blueprint $table) {
            $table->id();

            // Company relationship (multi-tenant)
            $table->unsignedInteger('company_id')->index();
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');

            // Certificate relationship
            $table->unsignedBigInteger('certificate_id')->index();
            $table->foreign('certificate_id')
                ->references('id')
                ->on('certificates')
                ->onDelete('restrict');

            // Action performed
            $table->enum('action', ['sign', 'verify', 'upload', 'delete', 'rotate'])
                ->index(); // Type of operation performed

            // Polymorphic relationship to the signed/verified entity
            $table->string('signable_type')->nullable(); // Invoice, Estimate, etc.
            $table->unsignedBigInteger('signable_id')->nullable();

            // User and context information
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null'); // Preserve logs even if user is deleted

            $table->ipAddress('ip_address')->nullable(); // IP address of the request
            $table->text('user_agent')->nullable(); // Browser/client user agent

            // Operation result
            $table->boolean('success')->default(true)->index(); // Did the operation succeed
            $table->text('error_message')->nullable(); // Error details if operation failed

            // Metadata about the operation
            $table->json('metadata')->nullable(); // Algorithm used, file size, signature format, etc.

            $table->timestamp('created_at')->index(); // When the action occurred

            // Composite indexes for common queries
            $table->index(['certificate_id', 'created_at']); // Certificate activity timeline
            $table->index(['signable_type', 'signable_id']); // Find all logs for a specific entity
            $table->index(['user_id', 'created_at']); // User activity timeline
            $table->index(['action', 'success', 'created_at']); // Failed operations by type
            $table->index(['certificate_id', 'action']); // Certificate usage by action type
        }).' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_logs');
    }
};
// CLAUDE-CHECKPOINT
