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
        Schema::create('bill_items', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2);
            $table->string('unit_name')->nullable();
            $table->unsignedBigInteger('price');
            $table->enum('discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('discount', 15, 2)->nullable();
            $table->unsignedBigInteger('discount_val')->default(0);
            $table->unsignedBigInteger('tax');
            $table->unsignedBigInteger('total');

            // Relationships
            $table->integer('bill_id')->unsigned();
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->integer('item_id')->unsigned()->nullable();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            // Currency conversion fields
            $table->decimal('exchange_rate', 19, 6)->nullable();
            $table->unsignedBigInteger('base_price')->nullable();
            $table->unsignedBigInteger('base_discount_val')->nullable();
            $table->unsignedBigInteger('base_tax')->nullable();
            $table->unsignedBigInteger('base_total')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('bill_id');
            $table->index('item_id');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bill_items');
    }
};

// CLAUDE-CHECKPOINT
