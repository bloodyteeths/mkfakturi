<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_ratio_cache')) {
            Schema::create('financial_ratio_cache', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->date('period_date')->comment('Month end date');
                $table->string('ratio_type', 50);
                $table->decimal('ratio_value', 15, 4)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('calculated_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'period_date', 'ratio_type'], 'frc_company_period_ratio_unique');

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_ratio_cache');
    }
};

// CLAUDE-CHECKPOINT
