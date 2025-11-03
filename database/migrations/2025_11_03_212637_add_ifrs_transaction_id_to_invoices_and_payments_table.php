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
        // Add ifrs_transaction_id to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('ifrs_transaction_id')->nullable()->after('id');
            $table->index('ifrs_transaction_id');
        });

        // Add ifrs_transaction_id to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('ifrs_transaction_id')->nullable()->after('id');
            $table->index('ifrs_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex(['ifrs_transaction_id']);
            $table->dropColumn('ifrs_transaction_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['ifrs_transaction_id']);
            $table->dropColumn('ifrs_transaction_id');
        });
    }
};

// CLAUDE-CHECKPOINT
