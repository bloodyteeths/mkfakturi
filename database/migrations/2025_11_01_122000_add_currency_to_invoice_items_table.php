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
        Schema::table('invoice_items', function (Blueprint $table) {
            if (! Schema::hasColumn('invoice_items', 'currency_id')) {
                $table->unsignedInteger('currency_id')->nullable()->after('company_id');

                if (Schema::hasTable('currencies') && config('database.default') !== 'sqlite') {
                    $table->foreign('currency_id')
                        ->references('id')
                        ->on('currencies');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'currency_id')) {
                if (Schema::hasTable('currencies') && config('database.default') !== 'sqlite') {
                    $table->dropForeign(['currency_id']);
                }

                $table->dropColumn('currency_id');
            }
        });
    }
};
