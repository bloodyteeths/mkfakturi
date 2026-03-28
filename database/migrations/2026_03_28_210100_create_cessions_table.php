<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cessions')) {
            Schema::create('cessions', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('cession_number', 50);
                $table->date('cession_date');
                $table->enum('role', ['cedent', 'cessionary', 'debtor']);
                $table->string('cedent_name', 255);
                $table->string('cedent_vat_id', 50)->nullable();
                $table->string('cedent_tax_id', 50)->nullable();
                $table->string('cessionary_name', 255);
                $table->string('cessionary_vat_id', 50)->nullable();
                $table->string('cessionary_tax_id', 50)->nullable();
                $table->string('debtor_name', 255);
                $table->string('debtor_vat_id', 50)->nullable();
                $table->string('debtor_tax_id', 50)->nullable();
                $table->unsignedBigInteger('amount')->default(0);
                $table->string('original_document_type', 50)->nullable();
                $table->unsignedBigInteger('original_document_id')->nullable();
                $table->string('original_document_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
                $table->timestamp('confirmed_at')->nullable();
                $table->unsignedInteger('creator_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->unique(['company_id', 'cession_number'], 'uq_cession_number');
                $table->index(['company_id', 'cession_date'], 'idx_cession_company_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cessions');
    }
};

// CLAUDE-CHECKPOINT
