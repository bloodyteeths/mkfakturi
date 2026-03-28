<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Mk\Models\Manufacturing\Bom;

class BomFactory extends Factory
{
    protected $model = Bom::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'currency_id' => Currency::factory(),
            'name' => $this->faker->sentence(3),
            'output_item_id' => Item::factory(),
            'output_quantity' => $this->faker->numberBetween(1, 500),
            'output_unit_id' => Unit::first()?->id ?? 1,
            'expected_wastage_percent' => $this->faker->randomFloat(2, 0, 10),
            'labor_cost_per_unit' => $this->faker->numberBetween(100, 5000),
            'overhead_cost_per_unit' => $this->faker->numberBetween(100, 3000),
            'is_active' => true,
            'version' => 1,
            'created_by' => User::factory(),
        ];
    }
}
