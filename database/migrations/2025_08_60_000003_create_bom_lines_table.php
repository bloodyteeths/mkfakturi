<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bom_lines')) {
            return;
        }

        Schema::create('bom_lines', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('bom_id');
            $table->unsignedInteger('item_id');
            $table->decimal('quantity', 15, 4);
            $table->unsignedInteger('unit_id')->nullable();
            $table->decimal('wastage_percent', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('bom_id')->references('id')->on('boms')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');

            $table->index('bom_id', 'idx_bom_lines_bom');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_lines');
    }
};

// CLAUDE-CHECKPOINT
