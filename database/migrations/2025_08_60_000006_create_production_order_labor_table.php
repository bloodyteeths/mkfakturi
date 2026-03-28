<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_order_labor')) {
            return;
        }

        Schema::create('production_order_labor', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->string('description', 255);
            $table->decimal('hours', 8, 2)->default(0);
            $table->unsignedBigInteger('rate_per_hour')->default(0)->comment('Cents');
            $table->unsignedBigInteger('total_cost')->default(0)->comment('Cents');
            $table->date('work_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');

            $table->index('production_order_id', 'idx_pol_production_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_labor');
    }
};

// CLAUDE-CHECKPOINT
