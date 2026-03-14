<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partners') && !Schema::hasColumn('partners', 'onboarding_completed_at')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->timestamp('onboarding_completed_at')->nullable()->after('portfolio_grace_ends_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('partners', 'onboarding_completed_at')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn('onboarding_completed_at');
            });
        }
    }
};
