<?php

namespace Tests\Unit\Services\Manufacturing;

use Illuminate\Support\Collection;
use Modules\Mk\Services\CostAllocationService;
use Tests\TestCase;

/**
 * Unit tests for CostAllocationService.
 *
 * Tests all 4 allocation methods: weight, market_value, fixed_ratio, manual.
 * Verifies rounding, remainder handling, edge cases.
 */
class CostAllocationServiceTest extends TestCase
{
    protected CostAllocationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CostAllocationService;
    }

    // ========================================
    // ALLOCATE BY WEIGHT
    // ========================================

    /** @test */
    public function weight_allocation_distributes_proportionally_by_quantity()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 60, 'allocation_method' => 'weight'],
            ['id' => 2, 'quantity' => 40, 'allocation_method' => 'weight'],
        ]);

        $result = $this->service->allocateByWeight($outputs, 100000);

        $this->assertEquals(60000, $result[1]['allocated_cost']);
        $this->assertEquals(40000, $result[2]['allocated_cost']);
        $this->assertAllocationsSum($result, 100000);
    }

    /** @test */
    public function weight_allocation_handles_remainder_on_last_item()
    {
        // 3 items, 100000 / 3 = 33333.33... — remainder goes to last
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 10, 'allocation_method' => 'weight'],
            ['id' => 2, 'quantity' => 10, 'allocation_method' => 'weight'],
            ['id' => 3, 'quantity' => 10, 'allocation_method' => 'weight'],
        ]);

        $result = $this->service->allocateByWeight($outputs, 100000);

        $this->assertEquals(33333, $result[1]['allocated_cost']);
        $this->assertEquals(33333, $result[2]['allocated_cost']);
        $this->assertEquals(100000 - 33333 - 33333, $result[3]['allocated_cost']); // 33334
        $this->assertAllocationsSum($result, 100000);
    }

    /** @test */
    public function weight_allocation_calculates_cost_per_unit()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 100, 'allocation_method' => 'weight'],
            ['id' => 2, 'quantity' => 50, 'allocation_method' => 'weight'],
        ]);

        $result = $this->service->allocateByWeight($outputs, 150000);

        // 100/150 * 150000 = 100000, per unit = 100000/100 = 1000
        $this->assertEquals(1000, $result[1]['cost_per_unit']);
    }

    /** @test */
    public function weight_allocation_handles_single_output()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 50, 'allocation_method' => 'weight'],
        ]);

        $result = $this->service->allocateByWeight($outputs, 250000);

        $this->assertEquals(250000, $result[1]['allocated_cost']);
        $this->assertEquals(100.0, $result[1]['allocation_percent']);
    }

    /** @test */
    public function weight_allocation_handles_zero_total_quantity()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 0, 'allocation_method' => 'weight'],
            ['id' => 2, 'quantity' => 0, 'allocation_method' => 'weight'],
        ]);

        $result = $this->service->allocateByWeight($outputs, 100000);

        // All should be 0 percent, last item gets remainder
        $this->assertEquals(0, $result[1]['allocated_cost']);
        $this->assertEquals(100000, $result[2]['allocated_cost']);
    }

    // ========================================
    // ALLOCATE BY FIXED RATIO
    // ========================================

    /** @test */
    public function fixed_ratio_uses_user_defined_percentages()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 100, 'allocation_method' => 'fixed_ratio', 'allocation_percent' => 70],
            ['id' => 2, 'quantity' => 50, 'allocation_method' => 'fixed_ratio', 'allocation_percent' => 30],
        ]);

        $result = $this->service->allocateByFixedRatio($outputs, 200000);

        $this->assertEquals(140000, $result[1]['allocated_cost']);
        $this->assertEquals(60000, $result[2]['allocated_cost']);
        $this->assertAllocationsSum($result, 200000);
    }

    /** @test */
    public function fixed_ratio_handles_uneven_percentages()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 10, 'allocation_method' => 'fixed_ratio', 'allocation_percent' => 33.33],
            ['id' => 2, 'quantity' => 10, 'allocation_method' => 'fixed_ratio', 'allocation_percent' => 33.33],
            ['id' => 3, 'quantity' => 10, 'allocation_method' => 'fixed_ratio', 'allocation_percent' => 33.34],
        ]);

        $result = $this->service->allocateByFixedRatio($outputs, 100000);

        $this->assertAllocationsSum($result, 100000);
    }

    // ========================================
    // ALLOCATE BY MARKET VALUE
    // ========================================

    /** @test */
    public function market_value_uses_selling_price_times_quantity()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 100, 'allocation_method' => 'market_value', 'selling_price' => 5000],
            ['id' => 2, 'quantity' => 50, 'allocation_method' => 'market_value', 'selling_price' => 2000],
        ]);

        // Market values: 100*5000=500000, 50*2000=100000, total=600000
        // Output 1: 500000/600000 * 300000 = 250000
        // Output 2: 100000/600000 * 300000 = 50000
        $result = $this->service->allocateByMarketValue($outputs, 300000);

        $this->assertEquals(250000, $result[1]['allocated_cost']);
        $this->assertEquals(50000, $result[2]['allocated_cost']);
        $this->assertAllocationsSum($result, 300000);
    }

    // ========================================
    // MANUAL ALLOCATION
    // ========================================

    /** @test */
    public function manual_allocation_validates_total_matches()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 100, 'allocation_method' => 'manual', 'allocated_cost' => 80000],
            ['id' => 2, 'quantity' => 50, 'allocation_method' => 'manual', 'allocated_cost' => 20000],
        ]);

        $result = $this->service->validateManualAllocation($outputs, 100000);

        $this->assertArrayNotHasKey('_warning', $result);
        $this->assertEquals(80000, $result[1]['allocated_cost']);
        $this->assertEquals(20000, $result[2]['allocated_cost']);
    }

    /** @test */
    public function manual_allocation_warns_on_mismatch()
    {
        $outputs = $this->makeOutputs([
            ['id' => 1, 'quantity' => 100, 'allocation_method' => 'manual', 'allocated_cost' => 50000],
            ['id' => 2, 'quantity' => 50, 'allocation_method' => 'manual', 'allocated_cost' => 30000],
        ]);

        $result = $this->service->validateManualAllocation($outputs, 100000);

        $this->assertArrayHasKey('_warning', $result);
        $this->assertStringContainsString('80000', $result['_warning']);
        $this->assertStringContainsString('100000', $result['_warning']);
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Create a collection of mock output objects.
     */
    private function makeOutputs(array $definitions): Collection
    {
        return collect($definitions)->map(function ($def) {
            return (object) [
                'id' => $def['id'],
                'quantity' => $def['quantity'],
                'allocation_method' => $def['allocation_method'] ?? 'weight',
                'allocation_percent' => $def['allocation_percent'] ?? 0,
                'allocated_cost' => $def['allocated_cost'] ?? 0,
                'item' => isset($def['selling_price'])
                    ? (object) ['selling_price' => $def['selling_price']]
                    : null,
            ];
        });
    }

    /**
     * Assert that non-meta allocations sum to exact total.
     */
    private function assertAllocationsSum(array $result, int $expectedTotal): void
    {
        $sum = 0;
        foreach ($result as $key => $val) {
            if (is_string($key) && str_starts_with($key, '_')) {
                continue;
            }
            $sum += $val['allocated_cost'];
        }
        $this->assertEquals($expectedTotal, $sum, "Allocation sum {$sum} does not match expected {$expectedTotal}");
    }
}

// CLAUDE-CHECKPOINT
