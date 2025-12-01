<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds payment_method column that was missing from previous migration
     */
    public function up(): void
    {
        if (! Schema::hasColumn('partners', 'payment_method')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->string('payment_method')->default('bank_transfer')->after('stripe_payouts_enabled_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
// CLAUDE-CHECKPOINT
