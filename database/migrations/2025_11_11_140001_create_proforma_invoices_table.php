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
        if (Schema::hasTable('proforma_invoices')) {
            return;
        }

        Schema::create('proforma_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->date('proforma_invoice_date');
            $table->date('expiry_date');
            $table->string('proforma_invoice_number');
            $table->string('proforma_invoice_prefix')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('customer_po_number')->nullable();
            $table->enum('status', ['DRAFT', 'SENT', 'VIEWED', 'EXPIRED', 'CONVERTED', 'REJECTED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('private_notes')->nullable();
            $table->string('discount_type')->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->unsignedBigInteger('discount_val')->nullable();
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('tax');
            $table->string('unique_hash')->nullable();
            $table->string('template_name')->nullable();

            // Currency fields
            $table->unsignedInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate', 19, 6)->nullable();
            $table->unsignedBigInteger('base_discount_val')->nullable();
            $table->unsignedBigInteger('base_sub_total')->nullable();
            $table->unsignedBigInteger('base_total')->nullable();
            $table->unsignedBigInteger('base_tax')->nullable();

            // Relationships
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');

            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            $table->integer('created_by')->unsigned()->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Conversion tracking
            $table->integer('converted_invoice_id')->unsigned()->nullable();
            $table->foreign('converted_invoice_id')->references('id')->on('invoices')->onDelete('restrict');

            // Sequence numbers
            $table->bigInteger('sequence_number')->nullable();
            $table->bigInteger('customer_sequence_number')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('proforma_invoice_date');
            $table->index('status');
            $table->index('proforma_invoice_number');
            $table->index('expiry_date');
        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proforma_invoices');
    }
};

// CLAUDE-CHECKPOINT
