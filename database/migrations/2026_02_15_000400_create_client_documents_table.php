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
        if (! Schema::hasTable('client_documents')) {
            Schema::create('client_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('uploaded_by');
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->enum('category', ['invoice', 'receipt', 'contract', 'bank_statement', 'other'])->default('other');
                $table->string('original_filename', 255);
                $table->string('file_path', 500);
                $table->unsignedInteger('file_size')->default(0);
                $table->string('mime_type', 100)->nullable();
                $table->enum('status', ['pending_review', 'reviewed', 'rejected'])->default('pending_review');
                $table->unsignedInteger('reviewer_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('notes')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('restrict');

                $table->index(['company_id', 'status']);
                $table->index(['partner_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
// CLAUDE-CHECKPOINT
