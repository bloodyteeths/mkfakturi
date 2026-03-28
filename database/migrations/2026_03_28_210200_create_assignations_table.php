<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assignations')) {
            Schema::create('assignations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('assignation_number', 50);
                $table->date('assignation_date');
                $table->enum('role', ['assignor', 'assignee', 'debtor']);
                $table->string('assignor_name', 255);
                $table->string('assignor_vat_id', 50)->nullable();
                $table->string('assignor_tax_id', 50)->nullable();
                $table->string('assignee_name', 255);
                $table->string('assignee_vat_id', 50)->nullable();
                $table->string('assignee_tax_id', 50)->nullable();
                $table->string('debtor_name', 255);
                $table->string('debtor_vat_id', 50)->nullable();
                $table->string('debtor_tax_id', 50)->nullable();
                $table->unsignedBigInteger('amount')->default(0);
                $table->string('assignor_to_assignee_doc', 100)->nullable();
                $table->string('assignor_to_debtor_doc', 100)->nullable();
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
                $table->timestamp('confirmed_at')->nullable();
                $table->unsignedInteger('creator_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->unique(['company_id', 'assignation_number'], 'uq_assignation_number');
                $table->index(['company_id', 'assignation_date'], 'idx_assignation_company_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assignations');
    }
};

// CLAUDE-CHECKPOINT
