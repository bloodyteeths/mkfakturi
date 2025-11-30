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
 * StockServiceTest
 *
 * Feature tests for Phase 2: Stock Module - StockService
 *
 * Coverage:
 * - Stock IN movements (purchases)
 * - Stock OUT movements (sales)
 * - Weighted Average Cost calculation
 * - Stock adjustments
 * - Warehouse transfers
 * - Movement history
 */
class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $stockService;

    protected Company $company;

    protected User $user;

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

        // Create company and user
        $this->company = Company::factory()->create([
            'name' => 'Test Stock Company',
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

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
            'price' => 100000, // 1000 MKD
            'track_quantity' => true,
            'quantity' => 0,
            'currency_id' => $this->currency->id,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('FACTURINO_STOCK_V1_ENABLED');
        parent::tearDown();
    }

    // ========================================
    // STOCK IN TESTS
    // ========================================

    /** @test */
    public function it_can_record_stock_in_movement()
    {
        $movement = $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10, // quantity
            50000, // unit cost 500 MKD
            StockMovement::SOURCE_INITIAL,
            null,
            '2025-11-01'
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals(10, $movement->quantity);
        $this->assertEquals(50000, $movement->unit_cost);
        $this->assertEquals(500000, $movement->total_cost); // 10 * 50000
        $this->assertEquals(10, $movement->balance_quantity);
        $this->assertEquals(500000, $movement->balance_value);
    }

    /** @test */
    public function it_updates_running_balance_on_stock_in()
    {
        // First stock in: 10 units at 500 MKD each
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        // Second stock in: 5 units at 600 MKD each
        $movement2 = $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            5,
            60000,
            StockMovement::SOURCE_BILL_ITEM
        );

        // Balance should be 15 units
        $this->assertEquals(15, $movement2->balance_quantity);

        // Value should be (10 * 500) + (5 * 600) = 8000 MKD = 800000 cents
        $this->assertEquals(800000, $movement2->balance_value);
    }

    /** @test */
    public function stock_in_quantity_must_be_positive()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stock IN quantity must be positive');

        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            -5, // Negative quantity
            50000
        );
    }

    // ========================================
    // STOCK OUT TESTS
    // ========================================

    /** @test */
    public function it_can_record_stock_out_movement()
    {
        // First, add some stock
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            20,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        // Now record stock out
        $movement = $this->stockService->recordStockOut(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            5,
            StockMovement::SOURCE_INVOICE_ITEM
        );

        $this->assertInstanceOf(StockMovement::class, $movement);
        $this->assertEquals(-5, $movement->quantity);
        $this->assertEquals(15, $movement->balance_quantity);
    }

    /** @test */
    public function stock_out_reduces_balance_at_weighted_average_cost()
    {
        // Add stock at two different prices
        // 10 units at 500 MKD = 5000
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        // 10 units at 600 MKD = 6000
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            60000,
            StockMovement::SOURCE_BILL_ITEM
        );

        // Total: 20 units, value = 11000 MKD
        // Weighted Average Cost = 11000 / 20 = 550 MKD per unit

        // Sell 5 units
        $outMovement = $this->stockService->recordStockOut(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            5,
            StockMovement::SOURCE_INVOICE_ITEM
        );

        // Balance should be 15 units
        $this->assertEquals(15, $outMovement->balance_quantity);

        // Value removed = 5 * 550 = 2750 MKD = 275000 cents
        // Remaining value = 1100000 - 275000 = 825000 cents
        $this->assertEquals(825000, $outMovement->balance_value);
    }

    // ========================================
    // WEIGHTED AVERAGE COST TESTS
    // ========================================

    /** @test */
    public function it_calculates_weighted_average_cost_correctly()
    {
        // Add 10 units at 1000 MKD
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            100000,
            StockMovement::SOURCE_INITIAL
        );

        // Add 10 units at 2000 MKD
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            200000,
            StockMovement::SOURCE_BILL_ITEM
        );

        // Get current stock
        $stock = $this->stockService->getItemStock(
            $this->company->id,
            $this->item->id,
            $this->warehouse->id
        );

        // Total: 20 units, value = (10 * 1000) + (10 * 2000) = 30000 MKD
        $this->assertEquals(20, $stock['quantity']);
        $this->assertEquals(3000000, $stock['total_value']);

        // WAC = 30000 / 20 = 1500 MKD = 150000 cents
        $this->assertEquals(150000, $stock['weighted_average_cost']);
    }

    /** @test */
    public function weighted_average_cost_updates_after_stock_movements()
    {
        // Add 10 units at 1000 MKD
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            100000,
            StockMovement::SOURCE_INITIAL
        );

        $stock1 = $this->stockService->getItemStock(
            $this->company->id,
            $this->item->id,
            $this->warehouse->id
        );
        $this->assertEquals(100000, $stock1['weighted_average_cost']);

        // Add 10 units at 2000 MKD (WAC should change)
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            200000,
            StockMovement::SOURCE_BILL_ITEM
        );

        $stock2 = $this->stockService->getItemStock(
            $this->company->id,
            $this->item->id,
            $this->warehouse->id
        );
        // WAC = (1000000 + 2000000) / 20 = 150000
        $this->assertEquals(150000, $stock2['weighted_average_cost']);
    }

    // ========================================
    // ADJUSTMENT TESTS
    // ========================================

    /** @test */
    public function it_can_record_positive_adjustment()
    {
        $movement = $this->stockService->recordAdjustment(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10, // positive adjustment
            50000, // unit cost required for positive
            'Physical count correction'
        );

        $this->assertEquals(10, $movement->quantity);
        $this->assertEquals(StockMovement::SOURCE_ADJUSTMENT, $movement->source_type);
        $this->assertEquals(10, $movement->balance_quantity);
    }

    /** @test */
    public function it_can_record_negative_adjustment()
    {
        // First add some stock
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            20,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        // Record negative adjustment
        $movement = $this->stockService->recordAdjustment(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            -5, // negative adjustment
            null, // unit cost not required for negative
            'Damaged goods write-off'
        );

        $this->assertEquals(-5, $movement->quantity);
        $this->assertEquals(15, $movement->balance_quantity);
    }

    /** @test */
    public function positive_adjustment_requires_unit_cost()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unit cost is required for positive stock adjustments');

        $this->stockService->recordAdjustment(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            null // Missing unit cost
        );
    }

    // ========================================
    // WAREHOUSE TRANSFER TESTS
    // ========================================

    /** @test */
    public function it_can_transfer_stock_between_warehouses()
    {
        // Create second warehouse
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Secondary Warehouse',
            'code' => 'SEC',
        ]);

        // Add stock to first warehouse
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            20,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        // Transfer 10 units to second warehouse
        $transfer = $this->stockService->transferStock(
            $this->company->id,
            $this->warehouse->id,
            $warehouse2->id,
            $this->item->id,
            10,
            'Inter-warehouse transfer'
        );

        // Check source warehouse balance
        $sourceStock = $this->stockService->getItemStock(
            $this->company->id,
            $this->item->id,
            $this->warehouse->id
        );
        $this->assertEquals(10, $sourceStock['quantity']);

        // Check destination warehouse balance
        $destStock = $this->stockService->getItemStock(
            $this->company->id,
            $this->item->id,
            $warehouse2->id
        );
        $this->assertEquals(10, $destStock['quantity']);

        // Transfer should preserve cost
        $this->assertEquals(50000, $destStock['weighted_average_cost']);
    }

    /** @test */
    public function transfer_fails_with_insufficient_stock()
    {
        $warehouse2 = Warehouse::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Add only 5 units
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            5,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        // Try to transfer 10 units
        $this->stockService->transferStock(
            $this->company->id,
            $this->warehouse->id,
            $warehouse2->id,
            $this->item->id,
            10
        );
    }

    /** @test */
    public function transfer_fails_when_source_equals_destination()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Source and destination warehouse cannot be the same');

        $this->stockService->transferStock(
            $this->company->id,
            $this->warehouse->id,
            $this->warehouse->id, // Same warehouse
            $this->item->id,
            10
        );
    }

    // ========================================
    // MOVEMENT HISTORY TESTS
    // ========================================

    /** @test */
    public function it_can_retrieve_movement_history()
    {
        // Create multiple movements
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            50000,
            StockMovement::SOURCE_INITIAL,
            null,
            '2025-11-01'
        );

        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            5,
            60000,
            StockMovement::SOURCE_BILL_ITEM,
            null,
            '2025-11-02'
        );

        $this->stockService->recordStockOut(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            3,
            StockMovement::SOURCE_INVOICE_ITEM,
            null,
            '2025-11-03'
        );

        $history = $this->stockService->getMovementHistory(
            $this->company->id,
            $this->item->id,
            $this->warehouse->id
        );

        $this->assertCount(3, $history);

        // Most recent first
        $this->assertEquals(-3, $history[0]->quantity);
        $this->assertEquals(5, $history[1]->quantity);
        $this->assertEquals(10, $history[2]->quantity);
    }

    /** @test */
    public function it_can_filter_history_by_date_range()
    {
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            50000,
            StockMovement::SOURCE_INITIAL,
            null,
            '2025-11-01'
        );

        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            5,
            60000,
            StockMovement::SOURCE_BILL_ITEM,
            null,
            '2025-11-15'
        );

        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            8,
            55000,
            StockMovement::SOURCE_BILL_ITEM,
            null,
            '2025-12-01'
        );

        // Get only November movements
        $history = $this->stockService->getMovementHistory(
            $this->company->id,
            $this->item->id,
            $this->warehouse->id,
            '2025-11-01',
            '2025-11-30'
        );

        $this->assertCount(2, $history);
    }

    // ========================================
    // STOCK VALUATION TESTS
    // ========================================

    /** @test */
    public function it_can_generate_stock_valuation_report()
    {
        // Create another trackable item
        $item2 = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Another Product',
            'track_quantity' => true,
            'currency_id' => $this->currency->id,
        ]);

        // Add stock to both items
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            100000,
            StockMovement::SOURCE_INITIAL
        );

        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $item2->id,
            20,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        $report = $this->stockService->getStockValuationReport($this->company->id);

        $this->assertCount(2, $report['items']);
        $this->assertEquals(30, $report['total_quantity']); // 10 + 20
        $this->assertEquals(2000000, $report['total_value']); // 1000000 + 1000000
    }

    // ========================================
    // ITEM QUANTITY UPDATE TESTS
    // ========================================

    /** @test */
    public function it_updates_item_quantity_after_movements()
    {
        $this->assertEquals(0, $this->item->fresh()->quantity);

        // Add stock
        $this->stockService->recordStockIn(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            25,
            50000,
            StockMovement::SOURCE_INITIAL
        );

        $this->assertEquals(25, $this->item->fresh()->quantity);

        // Remove stock
        $this->stockService->recordStockOut(
            $this->company->id,
            $this->warehouse->id,
            $this->item->id,
            10,
            StockMovement::SOURCE_INVOICE_ITEM
        );

        $this->assertEquals(15, $this->item->fresh()->quantity);
    }

    // ========================================
    // DEFAULT WAREHOUSE TESTS
    // ========================================

    /** @test */
    public function it_creates_default_warehouse_if_none_exists()
    {
        // Create new company without warehouse
        $newCompany = Company::factory()->create();

        $warehouse = Warehouse::getOrCreateDefault($newCompany->id);

        $this->assertEquals($newCompany->id, $warehouse->company_id);
        $this->assertTrue($warehouse->is_default);
        $this->assertEquals('Default Warehouse', $warehouse->name);
    }

    /** @test */
    public function it_returns_existing_default_warehouse()
    {
        $existingDefault = Warehouse::where('company_id', $this->company->id)
            ->where('is_default', true)
            ->first();

        $warehouse = Warehouse::getOrCreateDefault($this->company->id);

        $this->assertEquals($existingDefault->id, $warehouse->id);
    }
}
// CLAUDE-CHECKPOINT
