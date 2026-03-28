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
        // Add urgency to payment_batches
        if (Schema::hasTable('payment_batches') && !Schema::hasColumn('payment_batches', 'urgency')) {
            Schema::table('payment_batches', function (Blueprint $table) {
                $table->enum('urgency', ['redovno', 'itno'])->default('redovno')->after('format');
            });
        }

        // Add PP50-specific fields to payment_batch_items
        if (Schema::hasTable('payment_batch_items') && !Schema::hasColumn('payment_batch_items', 'payment_code')) {
            Schema::table('payment_batch_items', function (Blueprint $table) {
                $table->string('payment_code', 3)->nullable()->after('currency_code');
                $table->string('tax_number', 13)->nullable()->after('description');
                $table->string('municipality_code', 10)->nullable()->after('tax_number');
                $table->string('revenue_code', 10)->nullable()->after('municipality_code');
                $table->string('program_code', 10)->nullable()->after('revenue_code');
                $table->string('approval_reference', 50)->nullable()->after('program_code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payment_batches') && Schema::hasColumn('payment_batches', 'urgency')) {
            Schema::table('payment_batches', function (Blueprint $table) {
                $table->dropColumn('urgency');
            });
        }

        if (Schema::hasTable('payment_batch_items')) {
            Schema::table('payment_batch_items', function (Blueprint $table) {
                $columns = ['payment_code', 'tax_number', 'municipality_code', 'revenue_code', 'program_code', 'approval_reference'];
                $toDrop = [];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('payment_batch_items', $col)) {
                        $toDrop[] = $col;
                    }
                }
                if (!empty($toDrop)) {
                    $table->dropColumn($toDrop);
                }
            });
        }
    }
};

// CLAUDE-CHECKPOINT
