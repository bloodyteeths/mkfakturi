<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the oneid_sub column to the users table for eID/OneID
     * OpenID Connect login integration (P13-03).
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'oneid_sub')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('oneid_sub', 100)->nullable()->after('github_id');
                $table->unique('oneid_sub', 'users_oneid_sub_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'oneid_sub')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique('users_oneid_sub_unique');
                $table->dropColumn('oneid_sub');
            });
        }
    }
};
