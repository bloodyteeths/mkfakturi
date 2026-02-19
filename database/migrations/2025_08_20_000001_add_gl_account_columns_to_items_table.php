<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds GL account override columns to items table for per-item
     * inventory, COGS, and purchase account mapping (WS1: GL Auto-posting).
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'inventory_account_id')) {
                $table->unsignedBigInteger('inventory_account_id')->nullable()->after('currency_id');
                $table->foreign('inventory_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('items', 'cogs_account_id')) {
                $table->unsignedBigInteger('cogs_account_id')->nullable()->after('inventory_account_id');
                $table->foreign('cogs_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('items', 'purchase_account_id')) {
                $table->unsignedBigInteger('purchase_account_id')->nullable()->after('cogs_account_id');
                $table->foreign('purchase_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'inventory_account_id')) {
                $table->dropForeign(['inventory_account_id']);
                $table->dropColumn('inventory_account_id');
            }

            if (Schema::hasColumn('items', 'cogs_account_id')) {
                $table->dropForeign(['cogs_account_id']);
                $table->dropColumn('cogs_account_id');
            }

            if (Schema::hasColumn('items', 'purchase_account_id')) {
                $table->dropForeign(['purchase_account_id']);
                $table->dropColumn('purchase_account_id');
            }
        });
    }
};
