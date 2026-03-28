<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_counts')) {
            return;
        }

        Schema::create('stock_counts', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('status', 30)->default('draft');
            $table->date('count_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('counted_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->integer('total_items_counted')->default(0);
            $table->decimal('total_variance_quantity', 15, 4)->default(0);
            $table->bigInteger('total_variance_value')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('counted_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_counts');
    }
};
// CLAUDE-CHECKPOINT
