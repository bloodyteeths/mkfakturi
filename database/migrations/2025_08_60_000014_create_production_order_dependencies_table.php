<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_order_dependencies')) {
            return;
        }

        Schema::create('production_order_dependencies', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->engine = 'InnoDB';

            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('depends_on_order_id');
            $table->timestamps();

            $table->foreign('order_id')
                ->references('id')->on('production_orders')
                ->onDelete('cascade');

            $table->foreign('depends_on_order_id')
                ->references('id')->on('production_orders')
                ->onDelete('cascade');

            $table->unique(['order_id', 'depends_on_order_id'], 'pod_order_depends_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_order_dependencies');
    }
};
