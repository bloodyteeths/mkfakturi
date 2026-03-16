<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders') && !$this->hasIndex('purchase_orders', 'purchase_orders_po_number_company_id_unique')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->unique(['po_number', 'company_id'], 'purchase_orders_po_number_company_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && $this->hasIndex('purchase_orders', 'purchase_orders_po_number_company_id_unique')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropUnique('purchase_orders_po_number_company_id_unique');
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }

        return false;
    }
};

// CLAUDE-CHECKPOINT
