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
        // Only add stripe_id if it doesn't exist
        if (! Schema::hasColumn('companies', 'stripe_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('stripe_id')->nullable()->unique()->after('paddle_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('companies', 'stripe_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('stripe_id');
            });
        }
    }
};
