<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_order_overhead')) {
            return;
        }

        Schema::create('production_order_overhead', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->string('description', 255);
            $table->unsignedBigInteger('amount')->default(0)->comment('Cents');
            $table->enum('allocation_method', ['per_unit', 'percentage', 'fixed'])->default('fixed');
            $table->decimal('allocation_base', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');

            $table->index('production_order_id', 'idx_poo_production_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_overhead');
    }
};

// CLAUDE-CHECKPOINT
