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
        if (Schema::hasTable('kyc_documents')) {
            return;
        }

        Schema::create('kyc_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->enum('document_type', [
                'id_card',
                'passport',
                'proof_of_address',
                'bank_statement',
                'tax_certificate',
                'other',
            ]);
            $table->string('original_filename');
            $table->string('file_path'); // Encrypted file path
            $table->text('encrypted_data')->nullable(); // Encrypted file contents (for sensitive docs)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable(); // Admin user ID
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable(); // Additional info (file size, mime type, etc.)
            $table->timestamps();
            $table->softDeletes(); // Soft delete for compliance

            // Foreign keys
            $table->foreign('partner_id')
                ->references('id')
                ->on('partners')
                ->onDelete('restrict');

            $table->foreign('verified_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes
            $table->index('partner_id');
            $table->index('status');
            $table->index('verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kyc_documents');
    }
};

// CLAUDE-CHECKPOINT
