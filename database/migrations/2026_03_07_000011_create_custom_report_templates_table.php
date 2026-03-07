<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('custom_report_templates')) {
            Schema::create('custom_report_templates', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('name', 150);
                $table->json('account_filter');
                $table->json('columns');
                $table->string('period_type', 20)->nullable();
                $table->string('group_by', 20)->nullable();
                $table->string('comparison', 30)->nullable();
                $table->string('schedule_cron', 50)->nullable();
                $table->json('schedule_emails')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->index(['company_id'], 'idx_custom_report_templates_company');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_report_templates');
    }
};

// CLAUDE-CHECKPOINT
