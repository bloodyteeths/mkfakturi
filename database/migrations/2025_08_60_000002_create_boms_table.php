<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('boms')) {
            return;
        }

        Schema::create('boms', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('currency_id')->nullable();
            $table->string('name', 255);
            $table->string('code', 50)->nullable();
            $table->unsignedInteger('output_item_id');
            $table->decimal('output_quantity', 15, 4)->default(1);
            $table->unsignedInteger('output_unit_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('expected_wastage_percent', 5, 2)->default(0);
            $table->unsignedBigInteger('labor_cost_per_unit')->default(0)->comment('In cents');
            $table->unsignedBigInteger('overhead_cost_per_unit')->default(0)->comment('In cents');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('output_item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('output_unit_id')->references('id')->on('units')->onDelete('set null');

            $table->index('company_id', 'idx_boms_company');
            $table->index('output_item_id', 'idx_boms_output_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boms');
    }
};

// CLAUDE-CHECKPOINT
