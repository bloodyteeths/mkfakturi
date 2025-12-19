<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds allow_negative_stock column to items table.
     * When false, stock cannot go below zero for this item.
     * When true, overselling/backorders are allowed.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('items', 'allow_negative_stock')) {
            Schema::table('items', function (Blueprint $table) {
                $table->boolean('allow_negative_stock')->default(false)->after('track_quantity');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'allow_negative_stock')) {
                $table->dropColumn('allow_negative_stock');
            }
        });
    }
};
// CLAUDE-CHECKPOINT
