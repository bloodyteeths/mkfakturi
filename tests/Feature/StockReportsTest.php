<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * StockReportsTest
 *
 * Feature tests for S2-4A: Stock Reports Backend Endpoints
 *
 * Coverage:
 * - Item Stock Card endpoint
 * - Warehouse Inventory endpoint
 * - Inventory Valuation endpoint
 * - Authorization and company scoping
 * - Feature flag enforcement
 */
class StockReportsTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;

    protected Company $company;

    protected Company $otherCompany;

    protected User $user;

    protected User $otherUser;

    protected Currency $currency;

    protected Warehouse $warehouse;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable stock module for tests
        putenv('FACTURINO_STOCK_V1_ENABLED=true');

        $this->stockService = new StockService;

        // Create test currency
        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'name' => 'Macedonian Denar',
            'symbol' => 'ден',
            'precision' => 0,
        ]);

        // Create main company and user
        $this->company = Company::factory()->create([
            'name' => 'Test Stock Company',
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        // Create other company and user for authorization tests
        $this->otherCompany = Company::factory()->create([
            'name' => 'Other Company',
        ]);

        $this->otherUser = User::factory()->create();
        $this->otherUser->companies()->attach($this->otherCompany->id);

        // Create warehouse
        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Warehouse',
            'code' => 'MAIN',
            'is_default' => true,
        ]);

        // Create trackable item
        $this->item = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'sku' => 'SKU-001',
            'barcode' => '1234567890123',
            'price' => 100000,
            'track_quantity' => true,
            'quantity' => 0,
            'currency_id' => $this->currency->id,
        ]);

        // Add some stock movements
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            50000,
            StockMovement::SOURCE_INITIAL,
            null,
            '2025-11-01',
            'Initial stock'
        );

        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            5,
            60000,
            StockMovement::SOURCE_BILL_ITEM,
            null,
            '2025-11-15',
            'Purchase from supplier'
        );

        $this->stockService->recordStockOut(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            3,
            StockMovement::SOURCE_INVOICE_ITEM,
            null,
            '2025-11-20',
            'Sale to customer'
        );
    }

    protected function tearDown(): void
    {
        putenv('FACTURINO_STOCK_V1_ENABLED');
        parent::tearDown();
    }

    // ========================================
    // ITEM STOCK CARD TESTS
    // ========================================

    /** @test */
    public function it_can_get_item_stock_card()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/items/{$this->item->id}/card");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'item' => ['id', 'name', 'sku', 'barcode', 'unit'],
                    'warehouse',
                    'filters' => ['from_date', 'to_date'],
                    'opening_balance' => ['quantity', 'value'],
                    'movements' => [
                        '*' => [
                            'id',
                            'date',
                            'source_type',
                            'quantity',
                            'unit_cost',
                            'balance_quantity',
                            'balance_value',
                        ],
                    ],
                    'closing_balance' => ['quantity', 'value'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals($this->item->id, $data['item']['id']);
        $this->assertEquals('Test Product', $data['item']['name']);
        $this->assertCount(3, $data['movements']);

        // Final balance should be 12 units (10 + 5 - 3)
        $this->assertEquals(12, $data['closing_balance']['quantity']);
    }

    /** @test */
    public function it_can_filter_stock_card_by_warehouse()
    {
        // Create second warehouse with no movements
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Secondary Warehouse',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/items/{$this->item->id}/card?warehouse_id={$this->warehouse->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals($this->warehouse->id, $data['warehouse']['id']);
        $this->assertCount(3, $data['movements']);

        // Now query secondary warehouse - should have no movements
        $response2 = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/items/{$this->item->id}/card?warehouse_id={$warehouse2->id}");

        $response2->assertStatus(200);
        $data2 = $response2->json('data');
        $this->assertCount(0, $data2['movements']);
    }

    /** @test */
    public function it_can_filter_stock_card_by_date_range()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/items/{$this->item->id}/card?from_date=2025-11-10&to_date=2025-11-25");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should only show movements within date range (Nov 15 and Nov 20)
        $this->assertCount(2, $data['movements']);

        // Opening balance should reflect balance before Nov 10
        $this->assertEquals(10, $data['opening_balance']['quantity']);
    }

    /** @test */
    public function it_returns_404_for_other_company_item()
    {
        // Create item in other company
        $otherItem = Item::factory()->create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Other Company Item',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/items/{$otherItem->id}/card");

        $response->assertStatus(404);
    }

    // ========================================
    // WAREHOUSE INVENTORY TESTS
    // ========================================

    /** @test */
    public function it_can_get_warehouse_inventory()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/warehouses/{$this->warehouse->id}/inventory");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'warehouse' => ['id', 'name', 'code'],
                    'as_of_date',
                    'items' => [
                        '*' => [
                            'item_id',
                            'name',
                            'sku',
                            'barcode',
                            'unit',
                            'quantity',
                            'unit_cost',
                            'total_value',
                        ],
                    ],
                    'totals' => ['quantity', 'value'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals($this->warehouse->id, $data['warehouse']['id']);
        $this->assertCount(1, $data['items']); // Only one item with stock

        // Verify quantity
        $this->assertEquals(12, $data['items'][0]['quantity']);
    }

    /** @test */
    public function it_can_filter_warehouse_inventory_by_date()
    {
        // Get inventory as of Nov 10 (should only have 10 units)
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/warehouses/{$this->warehouse->id}/inventory?as_of_date=2025-11-10");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('2025-11-10', $data['as_of_date']);
        $this->assertEquals(10, $data['items'][0]['quantity']);
    }

    /** @test */
    public function it_can_search_warehouse_inventory()
    {
        // Create another item
        $item2 = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Different Product',
            'sku' => 'SKU-002',
            'track_quantity' => true,
            'currency_id' => $this->currency->id,
        ]);

        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $item2->id,
            5,
            30000
        );

        // Search by SKU
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/warehouses/{$this->warehouse->id}/inventory?search=SKU-001");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data['items']);
        $this->assertEquals('SKU-001', $data['items'][0]['sku']);
    }

    /** @test */
    public function it_returns_404_for_other_company_warehouse()
    {
        $otherWarehouse = Warehouse::factory()->create([
            'company_id' => $this->otherCompany->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/warehouses/{$otherWarehouse->id}/inventory");

        $response->assertStatus(404);
    }

    // ========================================
    // INVENTORY VALUATION TESTS
    // ========================================

    /** @test */
    public function it_can_get_inventory_valuation_by_warehouse()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/stock/inventory-valuation?group_by=warehouse');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'as_of_date',
                    'group_by',
                    'warehouses' => [
                        '*' => [
                            'warehouse' => ['id', 'name', 'code'],
                            'total_quantity',
                            'total_value',
                        ],
                    ],
                    'grand_total' => ['quantity', 'value'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('warehouse', $data['group_by']);
        $this->assertEquals(12, $data['grand_total']['quantity']);
    }

    /** @test */
    public function it_can_get_inventory_valuation_by_item()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/stock/inventory-valuation?group_by=item');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'as_of_date',
                    'group_by',
                    'items' => [
                        '*' => [
                            'item' => ['id', 'name', 'sku'],
                            'total_quantity',
                            'total_value',
                            'weighted_average_cost',
                        ],
                    ],
                    'grand_total' => ['quantity', 'value'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('item', $data['group_by']);
    }

    /** @test */
    public function it_can_filter_valuation_by_warehouse()
    {
        // Create second warehouse with some stock
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Secondary Warehouse',
        ]);

        $this->stockService->recordStockIn(
            $this->company->id,
            $warehouse2->id,
            $this->item->id,
            20,
            40000
        );

        // Get valuation for only warehouse 1
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/inventory-valuation?warehouse_id={$this->warehouse->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should only show warehouse 1 stock (12 units)
        $this->assertEquals(12, $data['grand_total']['quantity']);
    }

    // ========================================
    // INVENTORY LIST TESTS
    // ========================================

    /** @test */
    public function it_can_get_inventory_list()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/stock/inventory-list');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'as_of_date',
                    'warehouse_id',
                    'items' => [
                        '*' => [
                            'warehouse_id',
                            'warehouse_name',
                            'item_id',
                            'item_name',
                            'sku',
                            'barcode',
                            'unit',
                            'quantity',
                        ],
                    ],
                    'total_items',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['total_items']);
    }

    // ========================================
    // WAREHOUSES LIST TESTS
    // ========================================

    /** @test */
    public function it_can_get_warehouses_list()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/stock/warehouses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'code', 'is_default'],
                ],
            ]);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Main Warehouse', $data[0]['name']);
    }

    // ========================================
    // FEATURE FLAG TESTS
    // ========================================

    /** @test */
    public function it_returns_403_when_stock_module_disabled()
    {
        // Disable stock module
        putenv('FACTURINO_STOCK_V1_ENABLED=false');

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/items/{$this->item->id}/card");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Stock module is not enabled.',
            ]);
    }

    /** @test */
    public function all_endpoints_require_stock_module_enabled()
    {
        putenv('FACTURINO_STOCK_V1_ENABLED=false');

        $endpoints = [
            "/api/v1/stock/items/{$this->item->id}/card",
            "/api/v1/stock/warehouses/{$this->warehouse->id}/inventory",
            '/api/v1/stock/inventory-valuation',
            '/api/v1/stock/inventory-list',
            '/api/v1/stock/warehouses',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($this->user)
                ->withHeader('company', $this->company->id)
                ->getJson($endpoint);

            $response->assertStatus(403);
        }
    }

    // ========================================
    // CALCULATION ACCURACY TESTS
    // ========================================

    /** @test */
    public function it_calculates_weighted_average_cost_correctly()
    {
        // Item has:
        // - 10 units at 500 MKD = 5000 MKD
        // - 5 units at 600 MKD = 3000 MKD
        // - Sold 3 units
        // Total value after sale: depends on WAC at time of sale

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/stock/warehouses/{$this->warehouse->id}/inventory");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verify quantity
        $this->assertEquals(12, $data['items'][0]['quantity']);

        // WAC calculation:
        // After initial: 10 units, 500000 value, WAC = 50000
        // After purchase: 15 units, 800000 value, WAC = 53333
        // After sale: 12 units, value = 800000 - (3 * 53333) = 640001
        // Final WAC should be approximately 53333

        $wac = $data['items'][0]['unit_cost'];
        $this->assertGreaterThan(50000, $wac);
        $this->assertLessThan(60000, $wac);
    }
}
// CLAUDE-CHECKPOINT
