<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_templates')) {
            return;
        }

        Schema::create('production_templates', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('bom_id');
            $table->string('name', 255);
            $table->decimal('default_quantity', 15, 4);
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'custom'])->default('daily');
            $table->boolean('is_active')->default(true);
            $table->boolean('ai_suggested')->default(false);
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_generation_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('bom_id')->references('id')->on('boms')->onDelete('cascade');

            $table->index('company_id', 'idx_pt_company');
            $table->index('next_generation_at', 'idx_pt_next_gen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_templates');
    }
};

// CLAUDE-CHECKPOINT
