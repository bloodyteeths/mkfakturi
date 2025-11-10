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
        Schema::create('bank_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_connection_id')->constrained('bank_connections')->onDelete('restrict');
            $table->string('consent_id', 255)->comment('Consent ID from bank OAuth flow');
            $table->string('scope', 255)->comment('OAuth scope: accounts, balances, transactions');
            $table->timestamp('granted_at')->comment('When consent was granted');
            $table->timestamp('expires_at')->comment('Consent expiration timestamp');
            $table->enum('status', ['pending', 'active', 'expired', 'revoked'])->default('pending');
            $table->text('access_token')->comment('Encrypted OAuth2 access token');
            $table->text('refresh_token')->nullable()->comment('Encrypted OAuth2 refresh token');
            $table->json('metadata')->nullable()->comment('Additional OAuth metadata');
            $table->timestamps();

            // Indexes for performance
            $table->index('bank_connection_id');
            $table->index('consent_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index(['bank_connection_id', 'status']);
        });
    }

    // CLAUDE-CHECKPOINT

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_consents');
    }
};
