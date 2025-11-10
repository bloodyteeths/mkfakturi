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
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');

            // Company and customer relationships
            $table->integer('company_id')->unsigned();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            $table->integer('customer_id')->unsigned();
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('restrict');

            // Invoice relationship (the original invoice being credited)
            $table->integer('invoice_id')->unsigned();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('restrict');

            // Credit note identification
            $table->string('credit_note_number', 255);
            $table->string('unique_hash', 60)->unique()->nullable();

            // Dates
            $table->date('credit_note_date');
            $table->date('due_date')->nullable();

            // Status
            $table->enum('status', ['draft', 'sent', 'viewed', 'completed'])->default('draft');

            // Reason for credit note
            $table->text('reason')->nullable();

            // Financial amounts (in cents/smallest currency unit)
            $table->unsignedBigInteger('sub_total');
            $table->unsignedBigInteger('discount_val')->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->unsignedBigInteger('tax');
            $table->unsignedBigInteger('total');

            // Currency and exchange rate
            $table->unsignedInteger('currency_id')->nullable();
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');
            $table->decimal('exchange_rate', 19, 6)->nullable();

            // Template
            $table->string('template_name', 255)->nullable();

            // Additional information
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            // IFRS integration (for general ledger)
            $table->unsignedBigInteger('ifrs_transaction_id')->nullable();

            // Posted timestamp (when posted to IFRS)
            $table->timestamp('posted_at')->nullable();

            // Standard timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('company_id');
            $table->index('customer_id');
            $table->index('invoice_id');
            $table->index('status');
            $table->index('credit_note_number');
            $table->index('ifrs_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};

// CLAUDE-CHECKPOINT
