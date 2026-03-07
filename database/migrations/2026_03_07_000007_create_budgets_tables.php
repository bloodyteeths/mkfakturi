<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. budgets table
        if (! Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('name', 150);
                $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('monthly');
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['draft', 'approved', 'locked', 'archived'])->default('draft');
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->enum('scenario', ['expected', 'optimistic', 'pessimistic'])->default('expected');
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->index(['company_id', 'status'], 'idx_budgets_company_status');
            });
        }

        // 2. budget_lines table
        if (! Schema::hasTable('budget_lines')) {
            Schema::create('budget_lines', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('budget_id');
                $table->string('account_type', 50);
                $table->unsignedBigInteger('ifrs_account_id')->nullable();
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('amount', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('budget_id')
                    ->references('id')
                    ->on('budgets')
                    ->onDelete('cascade');

                $table->index(['budget_id', 'period_start'], 'idx_budget_lines_budget_period');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
    }
};

// CLAUDE-CHECKPOINT
