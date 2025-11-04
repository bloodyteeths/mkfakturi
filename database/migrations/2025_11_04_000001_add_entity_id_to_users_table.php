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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('entity_id')->nullable()->after('id');
            $table->index('entity_id');
            $table->foreign('entity_id')
                ->references('id')
                ->on('ifrs_entities')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropIndex(['entity_id']);
            $table->dropColumn('entity_id');
        });
    }
}; // CLAUDE-CHECKPOINT
