<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('woocommerce_sync_log')) {
            Schema::create('woocommerce_sync_log', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('woo_order_id');
                $table->string('woo_order_number')->nullable();
                $table->string('woo_order_status', 50)->nullable();
                $table->string('idempotency_key')->nullable();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->json('invoice_data')->nullable();
                $table->string('status', 50)->default('synced');
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'woo_order_id']);
                $table->index(['company_id', 'status']);

                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });
        }

        if (! Schema::hasTable('woocommerce_sync_history')) {
            Schema::create('woocommerce_sync_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedInteger('synced_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->unsignedInteger('error_count')->default(0);
                $table->json('details')->nullable();
                $table->string('status', 50)->default('success');
                $table->timestamp('created_at')->nullable();

                $table->index(['company_id', 'created_at']);

                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('woocommerce_sync_history');
        Schema::dropIfExists('woocommerce_sync_log');
    }
};
