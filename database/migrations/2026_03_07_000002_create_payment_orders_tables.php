<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates payment_batches and payment_batch_items tables for Payment Orders
     * (Nalozi za Plakjanje) module. Also adds IBAN/BIC fields to suppliers.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payment_batches')) {
            Schema::create('payment_batches', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->bigIncrements('id');
                $table->integer('company_id')->unsigned();
                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->string('batch_number', 50);
                $table->date('batch_date');
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->enum('format', ['pp30', 'pp50', 'sepa_sct', 'csv'])->default('pp30');
                $table->enum('status', [
                    'draft',
                    'pending_approval',
                    'approved',
                    'exported',
                    'sent_to_bank',
                    'confirmed',
                    'cancelled',
                ])->default('draft');
                $table->unsignedBigInteger('total_amount')->default(0);
                $table->unsignedInteger('item_count')->default(0);
                $table->unsignedBigInteger('currency_id')->nullable();
                $table->timestamp('exported_at')->nullable();
                $table->string('exported_file_path', 500)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'status']);
                $table->index('batch_date');
            });
        }

        if (! Schema::hasTable('payment_batch_items')) {
            Schema::create('payment_batch_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->bigIncrements('id');
                $table->unsignedBigInteger('payment_batch_id');
                $table->foreign('payment_batch_id')
                    ->references('id')
                    ->on('payment_batches')
                    ->onDelete('cascade');
                $table->unsignedInteger('bill_id')->nullable();
                $table->foreign('bill_id')
                    ->references('id')
                    ->on('bills')
                    ->onDelete('set null');
                $table->string('creditor_name', 255);
                $table->string('creditor_iban', 34)->nullable();
                $table->string('creditor_bic', 11)->nullable();
                $table->string('creditor_bank_name', 255)->nullable();
                $table->unsignedBigInteger('amount');
                $table->string('currency_code', 3)->default('MKD');
                $table->string('purpose_code', 10)->nullable();
                $table->string('payment_reference', 50)->nullable();
                $table->string('description', 140)->nullable();
                $table->enum('status', ['pending', 'exported', 'confirmed', 'failed'])->default('pending');
                $table->timestamp('reconciled_at')->nullable();
                $table->unsignedBigInteger('bank_transaction_id')->nullable();
                $table->timestamps();

                $table->index('payment_batch_id');
                $table->index('bill_id');
            });
        }

        // Add IBAN/BIC fields to suppliers table
        if (Schema::hasTable('suppliers')) {
            if (! Schema::hasColumn('suppliers', 'iban')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->string('iban', 34)->nullable();
                });
            }
            if (! Schema::hasColumn('suppliers', 'bic')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->string('bic', 11)->nullable();
                });
            }
            if (! Schema::hasColumn('suppliers', 'bank_name')) {
                Schema::table('suppliers', function (Blueprint $table) {
                    $table->string('bank_name', 255)->nullable();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_batch_items');
        Schema::dropIfExists('payment_batches');

        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (Schema::hasColumn('suppliers', 'iban')) {
                    $table->dropColumn('iban');
                }
                if (Schema::hasColumn('suppliers', 'bic')) {
                    $table->dropColumn('bic');
                }
                if (Schema::hasColumn('suppliers', 'bank_name')) {
                    $table->dropColumn('bank_name');
                }
            });
        }
    }
};

// CLAUDE-CHECKPOINT
