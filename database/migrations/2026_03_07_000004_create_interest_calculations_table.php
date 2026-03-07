<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('interest_calculations')) {
            Schema::create('interest_calculations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedInteger('invoice_id')->nullable();
                $table->date('calculation_date');
                $table->unsignedBigInteger('principal_amount');
                $table->integer('days_overdue');
                $table->decimal('annual_rate', 5, 2);
                $table->unsignedBigInteger('interest_amount');
                $table->enum('status', ['calculated', 'invoiced', 'paid', 'waived'])->default('calculated');
                $table->unsignedInteger('interest_invoice_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->index('company_id', 'idx_ic_company');
                $table->index('customer_id', 'idx_ic_customer');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('interest_calculations');
    }
};

// CLAUDE-CHECKPOINT
