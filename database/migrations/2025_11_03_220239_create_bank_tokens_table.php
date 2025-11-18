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
        Schema::create('bank_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('bank_code', 50)->comment('Bank identifier: stopanska, nlb, komercijalna');
            $table->text('access_token')->comment('Encrypted OAuth2 access token');
            $table->text('refresh_token')->nullable()->comment('Encrypted OAuth2 refresh token');
            $table->string('token_type', 50)->default('Bearer');
            $table->timestamp('expires_at')->comment('Token expiration timestamp');
            $table->string('scope', 255)->nullable()->comment('OAuth2 scope');
            $table->timestamps();

            // Unique constraint: One token per company per bank
            $table->unique(['company_id', 'bank_code']);

            // Index for performance
            $table->index(['company_id', 'bank_code', 'expires_at']);
        });
    }

    // CLAUDE-CHECKPOINT

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_tokens');
    }
};
