<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fixed_assets')) {
            return;
        }

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('asset_code')->nullable();
            $table->enum('category', [
                'real_estate',
                'buildings',
                'equipment',
                'vehicles',
                'computers_software',
                'other',
            ])->default('equipment');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('depreciation_account_id')->nullable();
            $table->date('acquisition_date');
            $table->decimal('acquisition_cost', 15, 2);
            $table->decimal('residual_value', 15, 2)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->enum('depreciation_method', ['straight_line', 'declining_balance'])->default('straight_line');
            $table->enum('status', ['active', 'disposed', 'fully_depreciated'])->default('active');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('depreciation_account_id')->references('id')->on('accounts')->onDelete('restrict');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};

// CLAUDE-CHECKPOINT
