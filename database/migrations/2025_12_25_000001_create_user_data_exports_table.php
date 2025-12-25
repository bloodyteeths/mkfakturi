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
        if (Schema::hasTable('user_data_exports')) {
            return;
        }

        Schema::create('user_data_exports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Processing status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            // Output
            $table->string('file_path')->nullable(); // Path to ZIP file in storage
            $table->unsignedInteger('file_size')->nullable(); // File size in bytes
            $table->timestamp('expires_at')->nullable(); // Auto-delete after 7 days

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_data_exports');
    }
};
// CLAUDE-CHECKPOINT
