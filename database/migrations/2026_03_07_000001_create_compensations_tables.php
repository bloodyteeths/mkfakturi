<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compensations')) {
            Schema::create('compensations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('compensation_number', 50);
                $table->date('compensation_date');
                $table->enum('counterparty_type', ['customer', 'supplier', 'both']);
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->enum('type', ['bilateral', 'unilateral'])->default('bilateral');
                $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
                $table->unsignedBigInteger('total_amount')->default(0);
                $table->unsignedInteger('currency_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('receivables_total')->default(0);
                $table->unsignedBigInteger('payables_total')->default(0);
                $table->unsignedBigInteger('receivables_remaining')->default(0);
                $table->unsignedBigInteger('payables_remaining')->default(0);
                $table->unsignedBigInteger('ifrs_transaction_id')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('confirmed_by')->nullable();
                $table->timestamp('confirmed_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->index(['company_id', 'compensation_date'], 'idx_comp_company_date');
                $table->index('customer_id', 'idx_comp_customer');
                $table->index('supplier_id', 'idx_comp_supplier');
            });
        }

        if (!Schema::hasTable('compensation_items')) {
            Schema::create('compensation_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('compensation_id');
                $table->enum('side', ['receivable', 'payable']);
                $table->enum('document_type', ['invoice', 'bill', 'credit_note']);
                $table->unsignedBigInteger('document_id');
                $table->string('document_number', 100)->nullable();
                $table->date('document_date')->nullable();
                $table->unsignedBigInteger('document_total');
                $table->unsignedBigInteger('amount_offset');
                $table->unsignedBigInteger('remaining_after')->default(0);
                $table->timestamps();
                $table->foreign('compensation_id')->references('id')->on('compensations')->onDelete('cascade');
                $table->index('compensation_id', 'idx_ci_comp');
                $table->index(['document_type', 'document_id'], 'idx_ci_doc');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_items');
        Schema::dropIfExists('compensations');
    }
};

// CLAUDE-CHECKPOINT
