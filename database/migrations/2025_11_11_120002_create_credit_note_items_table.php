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
        if (Schema::hasTable('credit_note_items')) {
            return;
        }

        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->increments('id');

            // Credit note relationship
            $table->integer('credit_note_id')->unsigned();
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('restrict');

            // Item catalog relationship (optional - item may not exist in catalog)
            $table->integer('item_id')->unsigned()->nullable();
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');

            // Item details
            $table->string('name');
            $table->text('description')->nullable();

            // Quantity and unit
            $table->decimal('quantity', 15, 2);
            $table->integer('unit_id')->unsigned()->nullable();
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('restrict');
            $table->string('unit_name')->nullable();

            // Pricing (in cents/smallest currency unit)
            $table->unsignedBigInteger('price');

            // Discount
            $table->string('discount_type')->nullable();
            $table->decimal('discount', 15, 2)->nullable();
            $table->unsignedBigInteger('discount_val')->default(0);

            // Tax and total
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('total');

            // Company relationship
            $table->integer('company_id')->unsigned()->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');

            // Standard timestamps
            $table->timestamps();

            // Indexes
            $table->index('credit_note_id');
            $table->index('item_id');
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
    }
};

// CLAUDE-CHECKPOINT
