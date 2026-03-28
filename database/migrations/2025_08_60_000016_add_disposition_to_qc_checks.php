<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('production_qc_checks', 'disposition')) {
            return;
        }

        Schema::table('production_qc_checks', function (Blueprint $table) {
            $table->enum('disposition', ['none', 'rework', 'scrap'])->default('none')->after('result');
            $table->unsignedBigInteger('rework_order_id')->nullable()->after('disposition');
            $table->unsignedBigInteger('scrap_quantity')->nullable()->after('rework_order_id');

            $table->foreign('rework_order_id')
                ->references('id')->on('production_orders')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('production_qc_checks', 'disposition')) {
            Schema::table('production_qc_checks', function (Blueprint $table) {
                $table->dropForeign(['rework_order_id']);
                $table->dropColumn(['disposition', 'rework_order_id', 'scrap_quantity']);
            });
        }
    }
};
