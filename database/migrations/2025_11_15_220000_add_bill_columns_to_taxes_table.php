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
        Schema::table('taxes', function (Blueprint $table) {
            // Add bill_id and bill_item_id columns
            $table->integer('bill_id')->unsigned()->nullable()->after('estimate_id');
            $table->integer('bill_item_id')->unsigned()->nullable()->after('estimate_item_id');

            // Add foreign key constraints
            $table->foreign('bill_id')->references('id')->on('bills')->onDelete('cascade');
            $table->foreign('bill_item_id')->references('id')->on('bill_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropForeign(['bill_id']);
            $table->dropForeign(['bill_item_id']);
            $table->dropColumn(['bill_id', 'bill_item_id']);
        });
    }
};
