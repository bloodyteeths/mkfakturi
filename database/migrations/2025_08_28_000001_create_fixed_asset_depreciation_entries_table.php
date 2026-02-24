<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fixed_asset_depreciation_entries')) {
            return;
        }

        Schema::create('fixed_asset_depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fixed_asset_id');
            $table->unsignedInteger('company_id');
            $table->date('month'); // First day of month (e.g. 2026-02-01)
            $table->decimal('amount', 15, 2);
            $table->unsignedBigInteger('ifrs_transaction_id')->nullable();
            $table->timestamps();

            $table->foreign('fixed_asset_id')
                ->references('id')->on('fixed_assets')
                ->onDelete('restrict');

            $table->foreign('company_id')
                ->references('id')->on('companies')
                ->onDelete('restrict');

            $table->unique(['fixed_asset_id', 'month'], 'fa_depr_asset_month_unique');
            $table->index(['company_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_depreciation_entries');
    }
};
