<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('consolidation_groups')) {
            Schema::create('consolidation_groups', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->string('name', 150);
                $table->unsignedInteger('parent_company_id');
                $table->string('currency_code', 3)->default('MKD');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('parent_company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->index('partner_id', 'idx_cg_partner');
            });
        }

        if (!Schema::hasTable('consolidation_members')) {
            Schema::create('consolidation_members', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('group_id');
                $table->unsignedInteger('company_id');
                $table->decimal('ownership_pct', 5, 2)->default(100.00);
                $table->boolean('is_parent')->default(false);
                $table->timestamps();

                $table->foreign('group_id')
                    ->references('id')
                    ->on('consolidation_groups')
                    ->onDelete('cascade');

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->unique(['group_id', 'company_id'], 'idx_cm_group_company');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('consolidation_members');
        Schema::dropIfExists('consolidation_groups');
    }
};

// CLAUDE-CHECKPOINT
