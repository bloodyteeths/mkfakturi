<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Item;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

class ProductionOrderFactory extends Factory
{
    protected $model = ProductionOrder::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'currency_id' => Currency::factory(),
            'bom_id' => Bom::factory(),
            'output_item_id' => Item::factory(),
            'planned_quantity' => $this->faker->numberBetween(10, 500),
            'actual_quantity' => null,
            'status' => 'draft',
            'order_date' => $this->faker->date(),
            'expected_completion_date' => $this->faker->date(),
            'output_warehouse_id' => Warehouse::factory(),
            'total_material_cost' => 0,
            'total_labor_cost' => 0,
            'total_overhead_cost' => 0,
            'total_wastage_cost' => 0,
            'total_production_cost' => 0,
            'cost_per_unit' => 0,
            'material_variance' => 0,
            'labor_variance' => 0,
            'total_variance' => 0,
            'created_by' => User::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => 'draft']);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => 'in_progress']);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => 'completed',
            'actual_quantity' => $this->faker->numberBetween(10, 500),
            'completed_at' => now(),
        ]);
    }
}
