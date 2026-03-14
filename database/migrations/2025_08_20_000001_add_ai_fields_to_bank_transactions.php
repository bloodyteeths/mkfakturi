<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_transactions')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                if (! Schema::hasColumn('bank_transactions', 'ai_category')) {
                    $table->string('ai_category', 50)->nullable()->after('match_confidence');
                }
                if (! Schema::hasColumn('bank_transactions', 'ai_match_reason')) {
                    $table->text('ai_match_reason')->nullable()->after('ai_category');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bank_transactions')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('bank_transactions', 'ai_category')) {
                    $table->dropColumn('ai_category');
                }
                if (Schema::hasColumn('bank_transactions', 'ai_match_reason')) {
                    $table->dropColumn('ai_match_reason');
                }
            });
        }
    }
};
