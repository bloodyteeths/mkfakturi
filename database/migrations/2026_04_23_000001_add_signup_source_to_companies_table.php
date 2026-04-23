<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'signup_source')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('signup_source', 50)->nullable()->after('subscription_tier');
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('signup_source');
        });
    }
};
