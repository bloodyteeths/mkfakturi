<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class WarehouseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $company = Company::first() ?? Company::factory()->create();

        return [
            'company_id' => $company->id,
            'name' => $this->faker->company().' Warehouse',
            'code' => strtoupper($this->faker->unique()->lexify('WH-???')),
            'address' => $this->faker->address(),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    /**
     * Indicate that this is the default warehouse.
     */
    public function default(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_default' => true,
            ];
        });
    }

    /**
     * Indicate that this warehouse is inactive.
     */
    public function inactive(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }
}
// CLAUDE-CHECKPOINT
