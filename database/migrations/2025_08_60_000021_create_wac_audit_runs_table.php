<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wac_audit_runs')) {
            return;
        }

        Schema::create('wac_audit_runs', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('total_movements_checked')->default(0);
            $table->unsignedInteger('discrepancies_found')->default(0);
            $table->json('summary')->nullable();
            $table->json('ai_analysis')->nullable();
            $table->unsignedBigInteger('triggered_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('set null');
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
            $table->foreign('triggered_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wac_audit_runs');
    }
};
