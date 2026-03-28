<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('items', 'preferred_supplier_id')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->unsignedBigInteger('preferred_supplier_id')->nullable()->after('minimum_quantity');
            $table->unsignedInteger('reorder_quantity')->nullable()->after('preferred_supplier_id');
            $table->unsignedSmallInteger('lead_time_days')->nullable()->after('reorder_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['preferred_supplier_id', 'reorder_quantity', 'lead_time_days']);
        });
    }
};

// CLAUDE-CHECKPOINT
