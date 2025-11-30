<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InvoiceProfitService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Invoice Profit Service (P2-5A)
 *
 * Verifies COGS and gross profit calculations using WAC data.
 */
class InvoiceProfitTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable stock module
        putenv('FACTURINO_STOCK_V1_ENABLED=true');

        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
        ]);

        $this->company = Company::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $this->user = User::factory()->create([
            'role' => 'super admin',
        ]);
        $this->user->companies()->attach($this->company->id);

        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Warehouse',
            'is_default' => true,
        ]);
    }

    protected function tearDown(): void
    {
        putenv('FACTURINO_STOCK_V1_ENABLED');
        parent::tearDown();
    }

    /** @test */
    public function it_calculates_profit_for_invoice_with_stock_movements()
    {
        // Create tracked item
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Product',
            'price' => 15000, // 150.00 selling price
            'track_quantity' => true,
        ]);

        // Record purchase: 10 units at 100.00 each = 1000.00 total
        StockMovement::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'source_type' => StockMovement::SOURCE_BILL_ITEM,
            'source_id' => 1,
            'quantity' => 10,
            'unit_cost' => 10000, // 100.00 in cents
            'total_cost' => 100000,
            'movement_date' => now()->subDays(5),
            'balance_quantity' => 10,
            'balance_value' => 100000,
        ]);

        // Create invoice with 3 units sold at 150.00 each
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'sub_total' => 45000, // 450.00
            'total' => 45000,
            'base_sub_total' => 45000,
            'base_total' => 45000,
        ]);

        $invoiceItem = InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Test Product',
            'quantity' => 3,
            'price' => 15000,
            'total' => 45000,
            'base_total' => 45000,
        ]);

        // Record stock OUT for invoice (using unit_cost from WAC)
        StockMovement::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'source_type' => StockMovement::SOURCE_INVOICE_ITEM,
            'source_id' => $invoiceItem->id,
            'quantity' => -3,
            'unit_cost' => 10000, // WAC at time of sale
            'total_cost' => 30000,
            'movement_date' => now(),
            'balance_quantity' => 7,
            'balance_value' => 70000,
        ]);

        // Calculate profit
        $profitService = new InvoiceProfitService(new StockService());
        $profit = $profitService->getInvoiceProfit($invoice, true);

        $this->assertTrue($profit['available']);
        $this->assertEquals(45000, $profit['revenue']); // 450.00
        $this->assertEquals(30000, $profit['cogs']); // 300.00 (3 × 100.00)
        $this->assertEquals(15000, $profit['gross_profit']); // 150.00
        $this->assertEquals(33.33, $profit['margin']); // 33.33%

        // Check item breakdown
        $this->assertCount(1, $profit['items']);
        $this->assertEquals(10000, $profit['items'][0]['unit_cost']); // 100.00
        $this->assertTrue($profit['items'][0]['has_cost']);
    }

    /** @test */
    public function it_returns_unavailable_when_stock_disabled()
    {
        putenv('FACTURINO_STOCK_V1_ENABLED=false');

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $profitService = new InvoiceProfitService(new StockService());
        $profit = $profitService->getInvoiceProfit($invoice);

        $this->assertFalse($profit['available']);
        $this->assertEquals('stock_disabled', $profit['reason']);
        $this->assertNull($profit['cogs']);
        $this->assertNull($profit['gross_profit']);
    }

    /** @test */
    public function it_returns_unavailable_when_no_stock_data()
    {
        // Create item WITHOUT stock tracking
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Service Item',
            'price' => 10000,
            'track_quantity' => false,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total' => 10000,
            'base_total' => 10000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'company_id' => $this->company->id,
            'name' => 'Service Item',
            'quantity' => 1,
            'price' => 10000,
            'total' => 10000,
            'base_total' => 10000,
        ]);

        $profitService = new InvoiceProfitService(new StockService());
        $profit = $profitService->getInvoiceProfit($invoice);

        $this->assertFalse($profit['available']);
        $this->assertEquals('no_stock_data', $profit['reason']);
    }

    /** @test */
    public function it_uses_current_wac_when_no_movement_linked()
    {
        // Create tracked item with stock
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Product',
            'price' => 20000,
            'track_quantity' => true,
        ]);

        // Add stock at 80.00 WAC
        StockMovement::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'source_type' => StockMovement::SOURCE_INITIAL,
            'quantity' => 5,
            'unit_cost' => 8000,
            'total_cost' => 40000,
            'movement_date' => now()->subDays(1),
            'balance_quantity' => 5,
            'balance_value' => 40000,
        ]);

        // Create invoice WITHOUT linked stock movement (simulating legacy data)
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total' => 20000,
            'base_total' => 20000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Product',
            'quantity' => 1,
            'price' => 20000,
            'total' => 20000,
            'base_total' => 20000,
        ]);

        $profitService = new InvoiceProfitService(new StockService());
        $profit = $profitService->getInvoiceProfit($invoice, true);

        $this->assertTrue($profit['available']);
        $this->assertEquals(20000, $profit['revenue']);
        $this->assertEquals(8000, $profit['cogs']); // Uses current WAC
        $this->assertEquals(12000, $profit['gross_profit']);
        $this->assertEquals('current_wac', $profit['items'][0]['cost_source']);
    }

    /** @test */
    public function it_handles_mixed_tracked_and_untracked_items()
    {
        // Create tracked item
        $trackedItem = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Physical Product',
            'price' => 10000,
            'track_quantity' => true,
        ]);

        // Create untracked item (service)
        $serviceItem = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Consulting Service',
            'price' => 5000,
            'track_quantity' => false,
        ]);

        // Add stock for tracked item
        StockMovement::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $trackedItem->id,
            'source_type' => StockMovement::SOURCE_INITIAL,
            'quantity' => 10,
            'unit_cost' => 6000,
            'total_cost' => 60000,
            'movement_date' => now()->subDay(),
            'balance_quantity' => 10,
            'balance_value' => 60000,
        ]);

        // Create invoice with both
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total' => 15000,
            'base_total' => 15000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $trackedItem->id,
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'name' => 'Physical Product',
            'quantity' => 1,
            'price' => 10000,
            'total' => 10000,
            'base_total' => 10000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $serviceItem->id,
            'company_id' => $this->company->id,
            'name' => 'Consulting Service',
            'quantity' => 1,
            'price' => 5000,
            'total' => 5000,
            'base_total' => 5000,
        ]);

        $profitService = new InvoiceProfitService(new StockService());
        $profit = $profitService->getInvoiceProfit($invoice, true);

        $this->assertTrue($profit['available']);
        $this->assertEquals(15000, $profit['revenue']); // 150.00 total
        $this->assertEquals(6000, $profit['cogs']); // Only tracked item cost
        $this->assertEquals(9000, $profit['gross_profit']);

        // Check item breakdown
        $this->assertCount(2, $profit['items']);

        // Find tracked item in breakdown
        $trackedBreakdown = collect($profit['items'])->firstWhere('item_id', $trackedItem->id);
        $this->assertTrue($trackedBreakdown['has_cost']);
        $this->assertEquals(6000, $trackedBreakdown['unit_cost']);

        // Service item has no cost
        $serviceBreakdown = collect($profit['items'])->firstWhere('item_id', $serviceItem->id);
        $this->assertFalse($serviceBreakdown['has_cost']);
        $this->assertEquals('not_tracked', $serviceBreakdown['cost_source']);
    }

    /** @test */
    public function it_handles_zero_quantity_invoice_items()
    {
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'track_quantity' => true,
        ]);

        StockMovement::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'source_type' => StockMovement::SOURCE_INITIAL,
            'quantity' => 10,
            'unit_cost' => 5000,
            'total_cost' => 50000,
            'movement_date' => now(),
            'balance_quantity' => 10,
            'balance_value' => 50000,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total' => 0,
            'base_total' => 0,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 0,
            'price' => 10000,
            'total' => 0,
            'base_total' => 0,
        ]);

        $profitService = new InvoiceProfitService(new StockService());
        $profit = $profitService->getInvoiceProfit($invoice);

        $this->assertTrue($profit['available']);
        $this->assertEquals(0, $profit['cogs']);
        $this->assertEquals(0, $profit['gross_profit']);
    }

    /** @test */
    public function it_handles_negative_margin_correctly()
    {
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'price' => 5000, // Selling below cost
            'track_quantity' => true,
        ]);

        StockMovement::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'source_type' => StockMovement::SOURCE_INITIAL,
            'quantity' => 10,
            'unit_cost' => 8000, // Cost is higher than selling price
            'total_cost' => 80000,
            'movement_date' => now(),
            'balance_quantity' => 10,
            'balance_value' => 80000,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'total' => 5000,
            'base_total' => 5000,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 1,
            'price' => 5000,
            'total' => 5000,
            'base_total' => 5000,
        ]);

        $profitService = new InvoiceProfitService(new StockService());
        $profit = $profitService->getInvoiceProfit($invoice);

        $this->assertTrue($profit['available']);
        $this->assertEquals(5000, $profit['revenue']);
        $this->assertEquals(8000, $profit['cogs']);
        $this->assertEquals(-3000, $profit['gross_profit']); // Negative profit
        $this->assertEquals(-60.0, $profit['margin']); // Negative margin
    }

    /** @test */
    public function profit_is_available_check_works()
    {
        // Test with no tracked items - should not be available
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'track_quantity' => false,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'item_id' => $item->id,
            'company_id' => $this->company->id,
        ]);

        $profitService = new InvoiceProfitService(new StockService());
        $this->assertFalse($profitService->isProfitAvailable($invoice));

        // Now with tracked item - should be available
        $trackedItem = Item::factory()->create([
            'company_id' => $this->company->id,
            'track_quantity' => true,
        ]);

        $invoice2 = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice2->id,
            'item_id' => $trackedItem->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertTrue($profitService->isProfitAvailable($invoice2));
    }

    /** @test */
    public function invoices_profit_summary_calculates_correctly()
    {
        // Create multiple invoices with stock
        $item = Item::factory()->create([
            'company_id' => $this->company->id,
            'track_quantity' => true,
        ]);

        StockMovement::create([
            'company_id' => $this->company->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'source_type' => StockMovement::SOURCE_INITIAL,
            'quantity' => 100,
            'unit_cost' => 5000,
            'total_cost' => 500000,
            'movement_date' => now(),
            'balance_quantity' => 100,
            'balance_value' => 500000,
        ]);

        $invoices = collect();

        for ($i = 0; $i < 3; $i++) {
            $invoice = Invoice::factory()->create([
                'company_id' => $this->company->id,
                'total' => 10000,
                'base_total' => 10000,
            ]);

            InvoiceItem::factory()->create([
                'invoice_id' => $invoice->id,
                'item_id' => $item->id,
                'company_id' => $this->company->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 1,
                'price' => 10000,
                'total' => 10000,
                'base_total' => 10000,
            ]);

            $invoices->push($invoice);
        }

        $profitService = new InvoiceProfitService(new StockService());
        $summary = $profitService->getInvoicesProfitSummary($invoices);

        $this->assertTrue($summary['available']);
        $this->assertEquals(30000, $summary['total_revenue']); // 3 × 100.00
        $this->assertEquals(15000, $summary['total_cogs']); // 3 × 50.00
        $this->assertEquals(15000, $summary['total_profit']);
        $this->assertEquals(50.0, $summary['avg_margin']);
        $this->assertEquals(3, $summary['invoices_analyzed']);
    }
}
// CLAUDE-CHECKPOINT
// CLAUDE-CHECKPOINT
