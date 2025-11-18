<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('partners') && ! Schema::hasColumn('partners', 'kyc_status')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->enum('kyc_status', ['pending', 'verified', 'rejected'])
                    ->default('pending')
                    ->after('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('partners') && Schema::hasColumn('partners', 'kyc_status')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn('kyc_status');
            });
        }
    }
};
