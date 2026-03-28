<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stock_movements', 'frozen_at')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->timestamp('frozen_at')->nullable()->after('created_by');
            $table->index('frozen_at', 'idx_stock_frozen');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stock_movements', 'frozen_at')) {
            return;
        }

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropIndex('idx_stock_frozen');
            $table->dropColumn('frozen_at');
        });
    }
};
