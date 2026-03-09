<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('batch_jobs') && Schema::hasColumn('batch_jobs', 'partner_id')) {
            Schema::table('batch_jobs', function (Blueprint $table) {
                $table->unsignedBigInteger('partner_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('batch_jobs') && Schema::hasColumn('batch_jobs', 'partner_id')) {
            Schema::table('batch_jobs', function (Blueprint $table) {
                $table->unsignedBigInteger('partner_id')->nullable(false)->change();
            });
        }
    }
};
