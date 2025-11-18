<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration specifically fixes the 'name' field_variations to remove
     * conflicting terms that were causing duplicate mapping issues.
     *
     * Problem: The 'name' rule had 'description' and 'sku' in its field_variations,
     * causing those CSV fields to incorrectly try to map to 'name' target field.
     */
    public function up(): void
    {
        try {
            // Clean field variations without description/sku related terms
            $cleanedVariations = [
                // English
                'name', 'item_name', 'itemname', 'product_name', 'productname',
                'product', 'item', 'title', 'service', 'service_name',
                'servicename', 'goods', 'goods_name',

                // Macedonian (Cyrillic)
                'име', 'артикал', 'производ', 'назив', 'артикал_име',
                'производ_іме', 'услуга', 'стока', 'назив_артикал',
                'назив_производ', 'іме_артикал',

                // Albanian
                'emri', 'produkti', 'artikulli', 'emri_produktit',
                'sherbimi', 'malli', 'emri_artikullit', 'titulli',

                // Serbian (Cyrillic)
                'роба', 'назив_артикла', 'іме_производа',
            ];

            $cleanedVariationsJson = json_encode($cleanedVariations, JSON_UNESCAPED_UNICODE);

            // Update using raw SQL to ensure it works
            $updated = DB::table('mapping_rules')
                ->where('entity_type', 'item')
                ->where('target_field', 'name')
                ->update([
                    'field_variations' => $cleanedVariationsJson,
                    'updated_at' => now(),
                ]);

            if ($updated > 0) {
                \Log::info("Migration: Successfully updated 'name' field variations", [
                    'rows_updated' => $updated,
                    'variations_count' => count($cleanedVariations),
                    'removed_terms' => ['description', 'sku', 'item_description', 'product_description', 'pershkrim', 'опис'],
                ]);
            } else {
                \Log::warning("Migration: No 'name' mapping rule found for item entity type");
            }

        } catch (\Exception $e) {
            \Log::error('Migration: Failed to update name field variations', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversing this would reintroduce the bug, so we just log it
        \Log::info('Migration rollback: name field variations not restored (would reintroduce bugs)');
    }
};
