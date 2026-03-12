<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bills', 'is_duplicate')) {
            Schema::table('bills', function (Blueprint $table) {
                $table->boolean('is_duplicate')->default(false)->after('status');
                $table->unsignedBigInteger('duplicate_of_id')->nullable()->after('is_duplicate');

                $table->foreign('duplicate_of_id')
                    ->references('id')
                    ->on('bills')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bills', function (Blueprint $table) {
            $table->dropForeign(['duplicate_of_id']);
            $table->dropColumn(['is_duplicate', 'duplicate_of_id']);
        });
    }
};
