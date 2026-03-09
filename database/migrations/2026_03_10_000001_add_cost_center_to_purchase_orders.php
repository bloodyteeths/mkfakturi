<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders') && !Schema::hasColumn('purchase_orders', 'cost_center_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('cost_center_id')->nullable()->after('warehouse_id');

                $table->foreign('cost_center_id')
                    ->references('id')
                    ->on('cost_centers')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'cost_center_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn('cost_center_id');
            });
        }
    }
};
