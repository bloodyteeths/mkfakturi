<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Warehouses table for stock management (Phase 2: Stock Module)
     * Each company can have multiple warehouses with one default warehouse.
     */
    public function up(): void
    {
        if (Schema::hasTable('warehouses')) {
            return;
        }

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            // Indexes
            $table->index('company_id');
            $table->index('is_default');
            $table->unique(['company_id', 'code'], 'warehouses_company_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
// CLAUDE-CHECKPOINT
