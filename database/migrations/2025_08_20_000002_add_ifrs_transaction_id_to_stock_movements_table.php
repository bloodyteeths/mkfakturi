<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds ifrs_transaction_id to stock_movements table for GL linkage
     * (WS1: GL Auto-posting for Stock Movements).
     */
    public function up(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'ifrs_transaction_id')) {
                $table->unsignedBigInteger('ifrs_transaction_id')->nullable()->after('meta');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('stock_movements')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'ifrs_transaction_id')) {
                $table->dropColumn('ifrs_transaction_id');
            }
        });
    }
};
