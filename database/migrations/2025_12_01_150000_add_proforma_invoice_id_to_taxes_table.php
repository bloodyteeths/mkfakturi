<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->integer('proforma_invoice_id')->unsigned()->nullable()->after('bill_id');
            $table->foreign('proforma_invoice_id')->references('id')->on('proforma_invoices')->onDelete('cascade');

            $table->integer('proforma_invoice_item_id')->unsigned()->nullable()->after('bill_item_id');
            $table->foreign('proforma_invoice_item_id')->references('id')->on('proforma_invoice_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropForeign(['proforma_invoice_id']);
            $table->dropColumn('proforma_invoice_id');

            $table->dropForeign(['proforma_invoice_item_id']);
            $table->dropColumn('proforma_invoice_item_id');
        });
    }
};
