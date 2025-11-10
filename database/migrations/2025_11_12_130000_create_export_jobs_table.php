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
        if (Schema::hasTable('export_jobs')) {
            return;
        }

        Schema::create('export_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Export configuration
            $table->enum('type', ['invoices', 'bills', 'customers', 'suppliers', 'transactions', 'expenses', 'payments']);
            $table->enum('format', ['csv', 'xlsx', 'pdf']);
            $table->json('params')->nullable(); // Filter parameters (date range, status, etc.)

            // Processing status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            // Output
            $table->string('file_path')->nullable(); // Path in storage
            $table->unsignedInteger('row_count')->nullable(); // Number of rows exported
            $table->timestamp('expires_at')->nullable(); // Auto-delete after 7 days

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'user_id']);
            $table->index(['status', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_jobs');
    }
};
// CLAUDE-CHECKPOINT
