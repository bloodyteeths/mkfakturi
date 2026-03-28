<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Unit;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

/**
 * Seeds manufacturing test data for Company 2 (Teknomed DOO).
 * Idempotent — skips if data already exists or tables don't exist.
 */
class ManufacturingTestSeeder extends Seeder
{
    private const COMPANY_ID = 2;

    public function run(): void
    {
        if (! Schema::hasTable('boms') || ! Schema::hasTable('production_orders')) {
            $this->command->info('Manufacturing tables do not exist yet, skipping seeder.');

            return;
        }

        // Skip if BOMs already exist for this company
        if (Bom::where('company_id', self::COMPANY_ID)->exists()) {
            $this->command->info('Manufacturing data already exists for company 2, skipping.');

            return;
        }

        $this->command->info('Seeding manufacturing test data for Company 2...');

        // Get or create units
        $kg = Unit::firstOrCreate(['name' => 'кг'], ['name' => 'кг']);
        $litar = Unit::firstOrCreate(['name' => 'литар'], ['name' => 'литар']);
        $parche = Unit::firstOrCreate(['name' => 'парче'], ['name' => 'парче']);

        // Create raw material items with initial stock
        $rawMaterials = [
            ['name' => 'Брашно тип 400', 'unit_id' => $kg->id, 'stock' => 500, 'wac' => 5000],
            ['name' => 'Квасец суво', 'unit_id' => $kg->id, 'stock' => 20, 'wac' => 30000],
            ['name' => 'Сол кујнска', 'unit_id' => $kg->id, 'stock' => 50, 'wac' => 4000],
            ['name' => 'Шеќер бел', 'unit_id' => $kg->id, 'stock' => 100, 'wac' => 6000],
            ['name' => 'Масло сончогледово', 'unit_id' => $litar->id, 'stock' => 30, 'wac' => 12000],
            ['name' => 'Јајца', 'unit_id' => $parche->id, 'stock' => 200, 'wac' => 800],
        ];

        $items = [];
        $stockService = app(StockService::class);

        // Get default warehouse
        $warehouse = \App\Models\Warehouse::where('company_id', self::COMPANY_ID)->first();
        if (! $warehouse) {
            $this->command->warn('No warehouse found for company 2, skipping stock.');

            return;
        }

        foreach ($rawMaterials as $mat) {
            $item = Item::firstOrCreate(
                ['name' => $mat['name'], 'company_id' => self::COMPANY_ID],
                [
                    'company_id' => self::COMPANY_ID,
                    'name' => $mat['name'],
                    'unit_id' => $mat['unit_id'],
                    'price' => 0,
                    'track_quantity' => true,
                ]
            );

            // Update item_type if column exists
            if (Schema::hasColumn('items', 'item_type')) {
                $item->update(['item_type' => 'raw_material']);
            }

            $items[$mat['name']] = $item;

            // Create initial stock
            try {
                $stockService->recordInitialStock(
                    companyId: self::COMPANY_ID,
                    warehouseId: $warehouse->id,
                    itemId: $item->id,
                    quantity: $mat['stock'],
                    unitCost: $mat['wac'],
                    notes: 'Manufacturing test seeder',
                );
            } catch (\Throwable $e) {
                // Stock may already exist
            }
        }

        // Create finished goods
        $leb = Item::firstOrCreate(
            ['name' => 'Леб 500г', 'company_id' => self::COMPANY_ID],
            [
                'company_id' => self::COMPANY_ID,
                'name' => 'Леб 500г',
                'unit_id' => $parche->id,
                'price' => 5000, // 50 MKD selling price
                'track_quantity' => true,
            ]
        );

        if (Schema::hasColumn('items', 'item_type')) {
            $leb->update(['item_type' => 'finished_good']);
        }

        $kifla = Item::firstOrCreate(
            ['name' => 'Кифла', 'company_id' => self::COMPANY_ID],
            [
                'company_id' => self::COMPANY_ID,
                'name' => 'Кифла',
                'unit_id' => $parche->id,
                'price' => 3000, // 30 MKD
                'track_quantity' => true,
            ]
        );

        if (Schema::hasColumn('items', 'item_type')) {
            $kifla->update(['item_type' => 'finished_good']);
        }

        // Get default currency
        $currencyId = \App\Models\CompanySetting::getSetting('currency', self::COMPANY_ID);

        // BOM 1: Леб 500г — 100 pieces output
        $bomLeb = Bom::create([
            'company_id' => self::COMPANY_ID,
            'currency_id' => $currencyId,
            'name' => 'Леб 500г — стандарден рецепт',
            'output_item_id' => $leb->id,
            'output_quantity' => 100,
            'output_unit_id' => $parche->id,
            'expected_wastage_percent' => 3.00,
            'labor_cost_per_unit' => 500, // 5 MKD per piece
            'overhead_cost_per_unit' => 300, // 3 MKD per piece
            'is_active' => true,
            'version' => 1,
            'created_by' => 2,
        ]);

        // BOM lines for bread (per 100 pieces)
        $bomLeb->lines()->createMany([
            ['item_id' => $items['Брашно тип 400']->id, 'quantity' => 35, 'unit_id' => $kg->id, 'wastage_percent' => 2, 'sort_order' => 0],
            ['item_id' => $items['Квасец суво']->id, 'quantity' => 0.7, 'unit_id' => $kg->id, 'wastage_percent' => 0, 'sort_order' => 1],
            ['item_id' => $items['Сол кујнска']->id, 'quantity' => 0.7, 'unit_id' => $kg->id, 'wastage_percent' => 0, 'sort_order' => 2],
            ['item_id' => $items['Масло сончогледово']->id, 'quantity' => 2, 'unit_id' => $litar->id, 'wastage_percent' => 1, 'sort_order' => 3],
        ]);

        // BOM 2: Кифла — 200 pieces output
        $bomKifla = Bom::create([
            'company_id' => self::COMPANY_ID,
            'currency_id' => $currencyId,
            'name' => 'Кифла — стандарден рецепт',
            'output_item_id' => $kifla->id,
            'output_quantity' => 200,
            'output_unit_id' => $parche->id,
            'expected_wastage_percent' => 2.50,
            'labor_cost_per_unit' => 300,
            'overhead_cost_per_unit' => 200,
            'is_active' => true,
            'version' => 1,
            'created_by' => 2,
        ]);

        $bomKifla->lines()->createMany([
            ['item_id' => $items['Брашно тип 400']->id, 'quantity' => 25, 'unit_id' => $kg->id, 'wastage_percent' => 2, 'sort_order' => 0],
            ['item_id' => $items['Квасец суво']->id, 'quantity' => 0.5, 'unit_id' => $kg->id, 'wastage_percent' => 0, 'sort_order' => 1],
            ['item_id' => $items['Шеќер бел']->id, 'quantity' => 3, 'unit_id' => $kg->id, 'wastage_percent' => 0, 'sort_order' => 2],
            ['item_id' => $items['Јајца']->id, 'quantity' => 20, 'unit_id' => $parche->id, 'wastage_percent' => 5, 'sort_order' => 3],
            ['item_id' => $items['Масло сончогледово']->id, 'quantity' => 3, 'unit_id' => $litar->id, 'wastage_percent' => 1, 'sort_order' => 4],
        ]);

        // Create a sample draft production order
        ProductionOrder::create([
            'company_id' => self::COMPANY_ID,
            'currency_id' => $currencyId,
            'bom_id' => $bomLeb->id,
            'output_item_id' => $leb->id,
            'planned_quantity' => 100,
            'status' => 'draft',
            'order_date' => now()->format('Y-m-d'),
            'expected_completion_date' => now()->addDays(1)->format('Y-m-d'),
            'output_warehouse_id' => $warehouse->id,
            'notes' => 'Тест работен налог — дневна серија леб',
            'created_by' => 2,
        ]);

        $this->command->info('Manufacturing test data seeded: 2 BOMs, 1 production order.');
    }
}

// CLAUDE-CHECKPOINT
