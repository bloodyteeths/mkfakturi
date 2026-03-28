<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add PP50 (budget payment) fields to payment_batch_items table.
 *
 * PP50 payments require a revenue code (шифра на приход) and
 * municipality code (шифра на општина) in addition to standard PP30 fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_batch_items')) {
            if (! Schema::hasColumn('payment_batch_items', 'revenue_code')) {
                Schema::table('payment_batch_items', function (Blueprint $table) {
                    $table->string('revenue_code', 20)->nullable()->after('payment_reference');
                });
            }

            if (! Schema::hasColumn('payment_batch_items', 'municipality_code')) {
                Schema::table('payment_batch_items', function (Blueprint $table) {
                    $table->string('municipality_code', 10)->nullable()->after('revenue_code');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_batch_items')) {
            Schema::table('payment_batch_items', function (Blueprint $table) {
                if (Schema::hasColumn('payment_batch_items', 'revenue_code')) {
                    $table->dropColumn('revenue_code');
                }
                if (Schema::hasColumn('payment_batch_items', 'municipality_code')) {
                    $table->dropColumn('municipality_code');
                }
            });
        }
    }
};

// CLAUDE-CHECKPOINT
