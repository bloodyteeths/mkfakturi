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
        Schema::create('bill_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ifrs_transaction_id')->nullable();
            $table->string('payment_number');
            $table->date('payment_date');
            $table->unsignedBigInteger('amount');
            $table->text('notes')->nullable();
            $table->string('unique_hash')->nullable();

            // Relationships
            $table->unsignedBigInteger('bill_id')->nullable();
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->unsignedInteger('creator_id')->nullable();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');
            $table->integer('payment_method_id')->unsigned()->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('restrict');

            // Currency conversion
            $table->decimal('exchange_rate', 19, 6)->nullable();
            $table->unsignedBigInteger('base_amount')->nullable();

            // IFRS integration
            $table->boolean('posted_to_ifrs')->default(false);

            $table->timestamps();

            // Indexes
            $table->index('ifrs_transaction_id');
            $table->index('bill_id');
            $table->index('company_id');
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_payments');
    }
};

// CLAUDE-CHECKPOINT
