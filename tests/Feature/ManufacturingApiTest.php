<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\ProductionOrder;
use Tests\TestCase;

/**
 * Feature tests for Manufacturing module API endpoints.
 *
 * Covers BOM CRUD, Production Order lifecycle,
 * material/labor/overhead entry, and report endpoints.
 */
class ManufacturingApiTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected Warehouse $warehouse;

    protected Unit $unit;

    protected Item $rawMaterial;

    protected Item $finishedGood;

    protected function setUp(): void
    {
        parent::setUp();

        putenv('FACTURINO_STOCK_V1_ENABLED=true');

        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'name' => 'Macedonian Denar',
            'symbol' => 'ден',
        ]);

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        $this->unit = Unit::firstOrCreate(['name' => 'кг']);

        $this->warehouse = Warehouse::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main',
            'code' => 'MAIN',
            'is_default' => true,
        ]);

        $this->rawMaterial = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Брашно',
            'unit_id' => $this->unit->id,
            'price' => 5000,
            'track_quantity' => true,
            'currency_id' => $this->currency->id,
        ]);

        $this->finishedGood = Item::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Леб 500г',
            'unit_id' => $this->unit->id,
            'price' => 50000,
            'track_quantity' => true,
            'currency_id' => $this->currency->id,
        ]);

        // Seed initial stock for raw material
        $stockService = new StockService;
        try {
            $stockService->recordInitialStock(
                $this->company->id,
                $this->warehouse->id,
                $this->rawMaterial->id,
                500,
                5000,
                'Test initial stock'
            );
        } catch (\Throwable $e) {
            // Stock might already exist
        }
    }

    protected function tearDown(): void
    {
        putenv('FACTURINO_STOCK_V1_ENABLED');
        parent::tearDown();
    }

    /**
     * Helper to make authenticated API request with company header.
     */
    private function apiAs(string $method, string $url, array $data = [])
    {
        return $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->{$method}("/api/v1{$url}", $data);
    }

    // ========================================
    // BOM CRUD
    // ========================================

    /** @test */
    public function it_can_create_a_bom()
    {
        $response = $this->apiAs('postJson', '/manufacturing/boms', [
            'name' => 'Леб — стандарден рецепт',
            'output_item_id' => $this->finishedGood->id,
            'output_quantity' => 100,
            'output_unit_id' => $this->unit->id,
            'currency_id' => $this->currency->id,
            'expected_wastage_percent' => 3,
            'labor_cost_per_unit' => 500,
            'overhead_cost_per_unit' => 300,
            'is_active' => true,
            'lines' => [
                [
                    'item_id' => $this->rawMaterial->id,
                    'quantity' => 35,
                    'unit_id' => $this->unit->id,
                    'wastage_percent' => 2,
                    'sort_order' => 0,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('boms', [
            'company_id' => $this->company->id,
            'name' => 'Леб — стандарден рецепт',
            'output_item_id' => $this->finishedGood->id,
        ]);
    }

    /** @test */
    public function it_can_list_boms()
    {
        Bom::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'output_item_id' => $this->finishedGood->id,
            'output_unit_id' => $this->unit->id,
            'name' => 'Test BOM',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $response = $this->apiAs('getJson', '/manufacturing/boms');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('meta.total', 1);
    }

    /** @test */
    public function it_can_show_a_bom_with_normative_cost()
    {
        $bom = Bom::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'output_item_id' => $this->finishedGood->id,
            'output_unit_id' => $this->unit->id,
            'name' => 'Show BOM',
            'created_by' => $this->user->id,
        ]);

        $response = $this->apiAs('getJson', "/manufacturing/boms/{$bom->id}");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure(['data' => ['id', 'name', 'code', 'normative_cost']]);
    }

    /** @test */
    public function it_cannot_delete_bom_used_by_orders()
    {
        $bom = Bom::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'output_item_id' => $this->finishedGood->id,
            'output_unit_id' => $this->unit->id,
            'created_by' => $this->user->id,
        ]);

        ProductionOrder::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'bom_id' => $bom->id,
            'output_item_id' => $this->finishedGood->id,
            'planned_quantity' => 100,
            'status' => 'draft',
            'created_by' => $this->user->id,
        ]);

        $response = $this->apiAs('deleteJson', "/manufacturing/boms/{$bom->id}");

        $response->assertStatus(422);
    }

    // ========================================
    // PRODUCTION ORDER LIFECYCLE
    // ========================================

    /** @test */
    public function it_can_create_a_production_order()
    {
        $bom = Bom::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'output_item_id' => $this->finishedGood->id,
            'output_unit_id' => $this->unit->id,
            'created_by' => $this->user->id,
        ]);

        $response = $this->apiAs('postJson', '/manufacturing/orders', [
            'bom_id' => $bom->id,
            'planned_quantity' => 100,
            'order_date' => '2026-03-27',
            'expected_completion_date' => '2026-03-28',
            'output_warehouse_id' => $this->warehouse->id,
            'notes' => 'Test production order',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('production_orders', [
            'company_id' => $this->company->id,
            'bom_id' => $bom->id,
            'planned_quantity' => 100,
            'status' => 'draft',
        ]);
    }

    /** @test */
    public function it_can_start_a_draft_order()
    {
        $order = $this->createDraftOrder();

        $response = $this->apiAs('postJson', "/manufacturing/orders/{$order->id}/start");

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('in_progress', $order->status);
    }

    /** @test */
    public function it_cannot_start_a_completed_order()
    {
        $order = $this->createDraftOrder();
        $order->update(['status' => 'completed']);

        $response = $this->apiAs('postJson', "/manufacturing/orders/{$order->id}/start");

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_cancel_an_in_progress_order()
    {
        $order = $this->createDraftOrder();
        $order->update(['status' => 'in_progress']);

        $response = $this->apiAs('postJson', "/manufacturing/orders/{$order->id}/cancel", [
            'reason' => 'Testing cancellation',
        ]);

        $response->assertOk();
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /** @test */
    public function it_can_complete_an_in_progress_order()
    {
        $order = $this->createDraftOrder();
        $order->update(['status' => 'in_progress']);

        $response = $this->apiAs('postJson', "/manufacturing/orders/{$order->id}/complete", [
            'actual_quantity' => 95,
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('completed', $order->status);
        $this->assertEquals(95, (float) $order->actual_quantity);
    }

    /** @test */
    public function it_can_list_orders_with_status_filter()
    {
        $this->createDraftOrder();
        $inProgress = $this->createDraftOrder();
        $inProgress->update(['status' => 'in_progress']);

        $response = $this->apiAs('getJson', '/manufacturing/orders?status=draft');

        $response->assertOk();
        $response->assertJsonPath('meta.total', 1);
    }

    // ========================================
    // MATERIAL / LABOR / OVERHEAD
    // ========================================

    /** @test */
    public function it_can_record_material_consumption()
    {
        $order = $this->createDraftOrder();
        $order->update(['status' => 'in_progress']);

        // Pre-fill a material from BOM
        $material = $order->materials()->create([
            'item_id' => $this->rawMaterial->id,
            'planned_quantity' => 35,
            'actual_quantity' => 0,
            'wastage_quantity' => 0,
            'unit_id' => $this->unit->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->apiAs('postJson', "/manufacturing/orders/{$order->id}/materials", [
            'material_id' => $material->id,
            'actual_quantity' => 34,
            'wastage_quantity' => 1,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_record_labor_cost()
    {
        $order = $this->createDraftOrder();
        $order->update(['status' => 'in_progress']);

        $response = $this->apiAs('postJson', "/manufacturing/orders/{$order->id}/labor", [
            'description' => 'Пекар — дневна смена',
            'hours' => 8,
            'rate_per_hour' => 15000, // 150 MKD/hr
            'work_date' => '2026-03-27',
        ]);

        $response->assertOk();
    }

    /** @test */
    public function it_can_record_overhead_cost()
    {
        $order = $this->createDraftOrder();
        $order->update(['status' => 'in_progress']);

        $response = $this->apiAs('postJson', "/manufacturing/orders/{$order->id}/overhead", [
            'description' => 'Електрична енергија',
            'amount' => 500000, // 5,000 MKD
            'allocation_method' => 'fixed',
        ]);

        $response->assertOk();
    }

    // ========================================
    // REPORTS
    // ========================================

    /** @test */
    public function it_can_fetch_cost_analysis_report()
    {
        $response = $this->apiAs('getJson', '/manufacturing/reports/cost-analysis?from=2026-01-01&to=2026-12-31');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'period',
                'summary' => ['total_orders', 'total_production_cost'],
                'by_product',
            ],
        ]);
    }

    /** @test */
    public function it_can_fetch_variance_report()
    {
        $response = $this->apiAs('getJson', '/manufacturing/reports/variance?from=2026-01-01&to=2026-12-31');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'summary' => ['total_orders', 'net_variance'],
                'orders',
            ],
        ]);
    }

    /** @test */
    public function it_can_fetch_wastage_report()
    {
        $response = $this->apiAs('getJson', '/manufacturing/reports/wastage?from=2026-01-01&to=2026-12-31');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'summary' => ['total_wastage_cost'],
                'by_material',
            ],
        ]);
    }

    // ========================================
    // PDF ENDPOINTS
    // ========================================

    /** @test */
    public function it_returns_404_for_nonexistent_order_pdf()
    {
        $response = $this->apiAs('getJson', '/manufacturing/orders/99999/pdf/order');

        $response->assertStatus(404);
    }

    // ========================================
    // HELPERS
    // ========================================

    private function createDraftOrder(): ProductionOrder
    {
        $bom = Bom::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'output_item_id' => $this->finishedGood->id,
            'output_unit_id' => $this->unit->id,
            'created_by' => $this->user->id,
        ]);

        return ProductionOrder::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'bom_id' => $bom->id,
            'output_item_id' => $this->finishedGood->id,
            'planned_quantity' => 100,
            'status' => 'draft',
            'order_date' => '2026-03-27',
            'output_warehouse_id' => $this->warehouse->id,
            'created_by' => $this->user->id,
        ]);
    }
}

// CLAUDE-CHECKPOINT
