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
        Schema::create('bills', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('ifrs_transaction_id')->nullable();
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->string('bill_number');
            $table->string('bill_prefix')->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('status', ['DRAFT', 'SENT', 'VIEWED', 'OVERDUE', 'PAID', 'COMPLETED'])->default('DRAFT');
            $table->enum('paid_status', ['UNPAID', 'PAID', 'PARTIALLY_PAID'])->default('UNPAID');
            $table->string('tax_per_item')->default('NO');
            $table->string('discount_per_item')->default('NO');
            $table->text('notes')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->unsignedBigInteger('discount_val')->nullable();
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('tax');
            $table->unsignedBigInteger('due_amount');
            $table->boolean('sent')->default(false);
            $table->boolean('viewed')->default(false);
            $table->string('unique_hash')->nullable();
            $table->string('template_name')->nullable();

            // Currency fields
            $table->integer('currency_id')->unsigned()->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate', 19, 6)->nullable();
            $table->unsignedBigInteger('base_discount_val')->nullable();
            $table->unsignedBigInteger('base_sub_total')->nullable();
            $table->unsignedBigInteger('base_total')->nullable();
            $table->unsignedBigInteger('base_tax')->nullable();
            $table->unsignedBigInteger('base_due_amount')->nullable();

            // Relationships
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('restrict');
            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->unsignedInteger('creator_id')->nullable();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');

            // IFRS integration
            $table->boolean('posted_to_ifrs')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('ifrs_transaction_id');
            $table->index('company_id');
            $table->index('supplier_id');
            $table->index('bill_date');
            $table->index('status');
            $table->index('paid_status');
            $table->index('bill_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};

// CLAUDE-CHECKPOINT
