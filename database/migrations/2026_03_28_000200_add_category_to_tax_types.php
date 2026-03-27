<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add `category` column to tax_types table.
 *
 * Categories distinguish zero-rated from exempt at 0% rate,
 * replacing fragile name-based detection (isExemptTax).
 *
 * Values: standard, reduced, hospitality, zero_rated, exempt, reverse_charge
 * NULL = user-created custom types (categorized by percent fallback)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tax_types', 'category')) {
            return;
        }

        Schema::table('tax_types', function (Blueprint $table) {
            $table->string('category', 20)->nullable()->after('type');
            $table->index('category');
        });

        // Backfill global (system) tax types
        DB::table('tax_types')
            ->whereNull('company_id')
            ->where('name', 'ДДВ 18%')
            ->update(['category' => 'standard']);

        DB::table('tax_types')
            ->whereNull('company_id')
            ->where('name', 'ДДВ 10%')
            ->update(['category' => 'hospitality']);

        DB::table('tax_types')
            ->whereNull('company_id')
            ->where('name', 'ДДВ 5%')
            ->update(['category' => 'reduced']);

        // Backfill company-specific tax types by percent range
        DB::table('tax_types')
            ->whereNotNull('company_id')
            ->whereNull('category')
            ->where('percent', '>=', 15)
            ->update(['category' => 'standard']);

        DB::table('tax_types')
            ->whereNotNull('company_id')
            ->whereNull('category')
            ->where('percent', '>=', 8)
            ->where('percent', '<', 15)
            ->update(['category' => 'hospitality']);

        DB::table('tax_types')
            ->whereNotNull('company_id')
            ->whereNull('category')
            ->where('percent', '>', 0)
            ->where('percent', '<', 8)
            ->update(['category' => 'reduced']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tax_types', 'category')) {
            return;
        }

        Schema::table('tax_types', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }
};

// CLAUDE-CHECKPOINT
