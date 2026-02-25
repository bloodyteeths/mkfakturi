<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'linked_supplier_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('linked_supplier_id')->nullable()->after('currency_id');
                $table->foreign('linked_supplier_id')
                      ->references('id')
                      ->on('suppliers')
                      ->onDelete('restrict');
                $table->unique(['linked_supplier_id', 'company_id'], 'customers_linked_supplier_company_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'linked_supplier_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['linked_supplier_id']);
                $table->dropUnique('customers_linked_supplier_company_unique');
                $table->dropColumn('linked_supplier_id');
            });
        }
    }
};
