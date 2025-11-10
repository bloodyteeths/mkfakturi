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
        if (Schema::hasTable('bank_connections')) {
            return;
        }

        Schema::create('bank_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->foreignId('bank_provider_id')->constrained('bank_providers')->onDelete('restrict');
            $table->enum('status', ['pending', 'active', 'expired', 'revoked', 'error'])->default('pending');
            $table->unsignedBigInteger('created_by')->comment('User who created the connection');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->timestamp('expires_at')->nullable()->comment('Connection expiration timestamp');
            $table->timestamp('last_synced_at')->nullable()->comment('Last successful sync timestamp');
            $table->text('error_message')->nullable()->comment('Error details if status is error');
            $table->json('metadata')->nullable()->comment('Connection details, account IDs, etc.');
            $table->timestamps();

            // Indexes for performance
            $table->index('company_id');
            $table->index('bank_provider_id');
            $table->index('status');
            $table->index(['company_id', 'bank_provider_id']);
            $table->index('expires_at');
        });
    }

    // CLAUDE-CHECKPOINT

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
