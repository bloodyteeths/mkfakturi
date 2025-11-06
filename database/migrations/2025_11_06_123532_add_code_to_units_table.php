<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add UN/ECE Recommendation 20 unit code column for e-invoice compliance.
     * This allows storing standard UBL unit codes (e.g., "C62", "KGM", "LTR")
     * alongside human-readable names.
     */
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->after('name');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->dropColumn('code');
        });
    }
};
