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
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('ifrs_entity_id')->nullable()->after('id');
            $table->index('ifrs_entity_id');
            $table->foreign('ifrs_entity_id')
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
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['ifrs_entity_id']);
            $table->dropIndex(['ifrs_entity_id']);
            $table->dropColumn('ifrs_entity_id');
        });
    }
}; // CLAUDE-CHECKPOINT
