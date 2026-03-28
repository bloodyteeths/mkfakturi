<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('items', 'retail_price')) {
            return;
        }

        Schema::table('items', function (Blueprint $table) {
            $table->bigInteger('retail_price')->nullable()->after('cost');
            $table->bigInteger('wholesale_price')->nullable()->after('retail_price');
            $table->decimal('markup_percent', 8, 2)->nullable()->after('wholesale_price');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['retail_price', 'wholesale_price', 'markup_percent']);
        });
    }
};

// CLAUDE-CHECKPOINT
