<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_company')) {
            Schema::table('user_company', function (Blueprint $table) {
                if (!Schema::hasColumn('user_company', 'is_legal_representative')) {
                    $table->boolean('is_legal_representative')->default(false)->after('company_id');
                }
                if (!Schema::hasColumn('user_company', 'is_signing_authority')) {
                    $table->boolean('is_signing_authority')->default(false)->after('is_legal_representative');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_company')) {
            Schema::table('user_company', function (Blueprint $table) {
                if (Schema::hasColumn('user_company', 'is_legal_representative')) {
                    $table->dropColumn('is_legal_representative');
                }
                if (Schema::hasColumn('user_company', 'is_signing_authority')) {
                    $table->dropColumn('is_signing_authority');
                }
            });
        }
    }
};
