<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('batch_jobs')) {
            Schema::create('batch_jobs', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('partner_id');
                $table->string('operation_type', 50);
                $table->json('company_ids');
                $table->json('parameters')->nullable();
                $table->enum('status', [
                    'queued',
                    'running',
                    'completed',
                    'failed',
                    'partially_failed',
                ])->default('queued');
                $table->unsignedInteger('total_items')->default(0);
                $table->unsignedInteger('completed_items')->default(0);
                $table->unsignedInteger('failed_items')->default(0);
                $table->json('results')->nullable();
                $table->text('error_log')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('partner_id')
                    ->references('id')
                    ->on('partners')
                    ->onDelete('restrict');

                $table->index(['partner_id', 'status'], 'idx_bj_partner');
                $table->index(['created_at'], 'idx_bj_created');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_jobs');
    }
};

// CLAUDE-CHECKPOINT
