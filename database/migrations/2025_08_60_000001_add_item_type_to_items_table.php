<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('items', 'item_type')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->enum('item_type', [
                'merchandise',
                'raw_material',
                'semi_finished',
                'finished_good',
                'by_product',
                'consumable',
                'biological',
            ])->default('merchandise')->after('unit_id');

            $table->decimal('default_wastage_percent', 5, 2)->default(0)->after('item_type');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'default_wastage_percent']);
        });
    }
};

// CLAUDE-CHECKPOINT
