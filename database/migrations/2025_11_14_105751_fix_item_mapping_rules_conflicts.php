<?php

use App\Models\MappingRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fixes duplicate mapping issues in item mapping rules:
     * 1. Removes 'description' and 'sku' from name field variations
     * 2. Adds 'category' mapping rule
     * 3. Adds 'tax_type' mapping rule
     *
     * This fixes the issue where:
     * - CSV fields 'description' and 'sku' were trying to map to 'name' target
     * - Duplicate prevention was blocking valid field mappings
     * - 'category' field had no mapping rule
     * - 'tax_type' and 'tax_rate' were conflicting
     */
    public function up(): void
    {
        DB::beginTransaction();

        try {
            // 1. Fix 'name' field variations - remove description and sku related terms
            $cleanedVariations = [
                // English
                'name', 'item_name', 'itemname', 'product_name', 'productname',
                'product', 'item', 'title', 'service', 'service_name',
                'servicename', 'goods', 'goods_name',

                // Macedonian (Cyrillic)
                'іме', 'артикал', 'производ', 'назив', 'артикал_име',
                'производ_іме', 'услуга', 'стока', 'назив_артикал',
                'назив_производ', 'іме_артикал',

                // Albanian
                'emri', 'produkti', 'artikulli', 'emri_produktit',
                'sherbimi', 'malli', 'emri_artikullit', 'titulli',

                // Serbian (Cyrillic)
                'роба', 'назив_артикла', 'іме_производа',
            ];

            $cleanedVariationsJson = json_encode($cleanedVariations, JSON_UNESCAPED_UNICODE);

            // Use direct SQL update to ensure it works across all databases
            $updated = DB::table('mapping_rules')
                ->where('entity_type', 'item')
                ->where('target_field', 'name')
                ->update(['field_variations' => $cleanedVariationsJson]);

            if ($updated > 0) {
                \Log::info("Migration: Updated 'name' field variations (removed description/sku conflicts)", [
                    'rows_updated' => $updated,
                    'variations_count' => count($cleanedVariations),
                ]);
            } else {
                \Log::warning("Migration: No 'name' rule found to update");
            }

            // 2. Add 'category' mapping rule if it doesn't exist
            $categoryRule = MappingRule::where('entity_type', MappingRule::ENTITY_ITEM)
                ->where('target_field', 'category')
                ->first();

            if (!$categoryRule) {
                $categoryRule = MappingRule::create([
                    'entity_type' => MappingRule::ENTITY_ITEM,
                    'source_field' => 'category',
                    'target_field' => 'category',
                    'field_variations' => [
                        // English
                        'category', 'categories', 'item_category', 'product_category',
                        'type', 'item_type', 'product_type', 'group', 'item_group',
                        'product_group', 'class', 'classification',

                        // Macedonian (Cyrillic)
                        'категорија', 'категории', 'тип', 'група', 'класа',
                        'класификација', 'категорија_артикал', 'тип_производ',

                        // Albanian
                        'kategoria', 'kategorite', 'lloji', 'grupi', 'klasa',
                        'klasifikimi', 'kategoria_produktit',

                        // Serbian (Cyrillic)
                        'категорија', 'врста', 'група', 'класа',
                    ],
                    'data_type' => 'string',
                    'validation_rules' => [
                        'required' => false,
                        'type' => 'string',
                        'max_length' => 255,
                    ],
                    'priority' => 100,
                    'is_active' => true,
                    'is_system_rule' => true,
                    'confidence_score' => 0.95,
                    'success_rate' => 0.0,
                    'usage_count' => 0,
                    'success_count' => 0,
                ]);

                \Log::info("Migration: Added 'category' mapping rule", [
                    'rule_id' => $categoryRule->id,
                ]);
            }

            // 3. Add 'tax_type' mapping rule if it doesn't exist
            $taxTypeRule = MappingRule::where('entity_type', MappingRule::ENTITY_ITEM)
                ->where('target_field', 'tax_type')
                ->first();

            if (!$taxTypeRule) {
                $taxTypeRule = MappingRule::create([
                    'entity_type' => MappingRule::ENTITY_ITEM,
                    'source_field' => 'tax_type',
                    'target_field' => 'tax_type',
                    'field_variations' => [
                        // English
                        'tax_type', 'taxtype', 'tax_name', 'vat_type', 'vattype',
                        'tax_category', 'tax_class', 'taxation_type',

                        // Macedonian (Cyrillic)
                        'тип_данок', 'вид_данок', 'данок_тип', 'ддв_тип',
                        'категорија_данок',

                        // Albanian
                        'lloji_tatimit', 'tipi_tatimit', 'kategoria_tatimit',

                        // Serbian (Cyrillic)
                        'тип_пореза', 'врста_пореза', 'категорија_пореза',
                    ],
                    'data_type' => 'string',
                    'validation_rules' => [
                        'required' => false,
                        'type' => 'string',
                        'max_length' => 255,
                    ],
                    'priority' => 90, // Higher priority than tax_rate
                    'is_active' => true,
                    'is_system_rule' => true,
                    'confidence_score' => 0.95,
                    'success_rate' => 0.0,
                    'usage_count' => 0,
                    'success_count' => 0,
                ]);

                \Log::info("Migration: Added 'tax_type' mapping rule", [
                    'rule_id' => $taxTypeRule->id,
                ]);
            }

            // 4. Update tax_rate rule priority to be lower than tax_type
            $taxRateRule = MappingRule::where('entity_type', MappingRule::ENTITY_ITEM)
                ->where('target_field', 'tax_rate')
                ->first();

            if ($taxRateRule && $taxRateRule->priority <= 90) {
                $taxRateRule->priority = 100;
                $taxRateRule->save();

                \Log::info("Migration: Updated 'tax_rate' rule priority", [
                    'rule_id' => $taxRateRule->id,
                    'new_priority' => 100,
                ]);
            }

            DB::commit();

            \Log::info("Migration: Item mapping rules fixed successfully");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Migration: Failed to fix item mapping rules", [
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
        // Note: This is a data migration, so down() is more complex
        // We'll just log that we can't easily reverse this
        \Log::warning("Migration: Cannot automatically reverse item mapping rules changes");

        // Optionally, you could restore the original 'name' variations
        // and remove the 'category' and 'tax_type' rules if needed
        // But since these are improvements, we typically don't reverse them
    }
};
