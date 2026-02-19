<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add cost (purchase/cost price) column to items table.
     * Stored in cents, same convention as the existing price (selling price) column.
     */
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'cost')) {
                $table->unsignedBigInteger('cost')->nullable()->after('price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'cost')) {
                $table->dropColumn('cost');
            }
        });
    }
};
