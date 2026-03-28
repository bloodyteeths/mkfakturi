<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add UJP compliance fields to fiscal_receipts table.
     */
    public function up(): void
    {
        if (! Schema::hasTable('fiscal_receipts')) {
            return;
        }

        Schema::table('fiscal_receipts', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            if (! Schema::hasColumn('fiscal_receipts', 'operator_id')) {
                $table->unsignedBigInteger('operator_id')->nullable()->after('source');
                $table->foreign('operator_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'operator_name')) {
                $table->string('operator_name', 100)->nullable()->after('operator_id');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'unique_sale_number')) {
                $table->string('unique_sale_number', 30)->nullable()->after('operator_name');
                $table->index('unique_sale_number');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'payment_type')) {
                $table->string('payment_type', 20)->nullable()->default('cash')->after('unique_sale_number');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'tax_breakdown')) {
                $table->json('tax_breakdown')->nullable()->after('payment_type');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'is_storno')) {
                $table->boolean('is_storno')->default(false)->after('tax_breakdown');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'storno_of_receipt_id')) {
                $table->unsignedBigInteger('storno_of_receipt_id')->nullable()->after('is_storno');
                $table->foreign('storno_of_receipt_id')
                    ->references('id')
                    ->on('fiscal_receipts')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'device_receipt_datetime')) {
                $table->timestamp('device_receipt_datetime')->nullable()->after('storno_of_receipt_id');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'items_snapshot')) {
                $table->json('items_snapshot')->nullable()->after('device_receipt_datetime');
            }

            if (! Schema::hasColumn('fiscal_receipts', 'device_registration_number')) {
                $table->string('device_registration_number', 50)->nullable()->after('items_snapshot');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('fiscal_receipts')) {
            return;
        }

        Schema::table('fiscal_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('fiscal_receipts', 'operator_id')) {
                $table->dropForeign(['operator_id']);
                $table->dropColumn('operator_id');
            }

            if (Schema::hasColumn('fiscal_receipts', 'storno_of_receipt_id')) {
                $table->dropForeign(['storno_of_receipt_id']);
                $table->dropColumn('storno_of_receipt_id');
            }

            $columns = [
                'operator_name',
                'unique_sale_number',
                'payment_type',
                'tax_breakdown',
                'is_storno',
                'device_receipt_datetime',
                'items_snapshot',
                'device_registration_number',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fiscal_receipts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

// CLAUDE-CHECKPOINT
