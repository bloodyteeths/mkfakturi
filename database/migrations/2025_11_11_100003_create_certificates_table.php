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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            // Company relationship
            $table->unsignedInteger('company_id')->index();
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('restrict');

            // Certificate identification
            $table->string('name'); // User-friendly name for the certificate
            $table->json('subject')->nullable(); // Certificate subject details (CN, O, OU, C, etc.)
            $table->json('issuer')->nullable(); // Issuer details

            // Unique identifiers
            $table->string('serial_number'); // Certificate serial number
            $table->string('fingerprint')->unique(); // SHA256 fingerprint - unique across all certificates

            // Validity period
            $table->datetime('valid_from'); // Certificate validity start date
            $table->datetime('valid_to'); // Certificate expiry date

            // Certificate storage
            $table->text('encrypted_key_blob'); // Encrypted private key
            $table->string('certificate_path', 255); // Path to certificate file (.pfx/.p12)

            // Cryptographic details
            $table->string('algorithm')->default('RSA-SHA256'); // RSA-SHA256, RSA-SHA384, RSA-SHA512, ECDSA, etc.
            $table->integer('key_size')->default(2048); // Key size in bits

            // Status flags
            $table->boolean('is_active')->default(true)->index(); // Is this certificate currently active for signing
            $table->boolean('is_expired')->default(false); // Cached expiry status for quick queries

            $table->timestamps();

            // Indexes for common queries
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'valid_to']);
            $table->index(['valid_to', 'is_expired']);
            $table->index('fingerprint');
        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
// CLAUDE-CHECKPOINT
