<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('boms', 'estimated_hours')) {
            return;
        }

        Schema::table('boms', function (Blueprint $table) {
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('overhead_cost_per_unit');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('boms', 'estimated_hours')) {
            Schema::table('boms', function (Blueprint $table) {
                $table->dropColumn('estimated_hours');
            });
        }
    }
};
