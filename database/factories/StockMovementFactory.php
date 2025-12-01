<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StockMovement::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $company = Company::first() ?? Company::factory()->create();

        return [
            'company_id' => $company->id,
            'warehouse_id' => Warehouse::factory()->create(['company_id' => $company->id])->id,
            'item_id' => Item::factory()->create(['company_id' => $company->id])->id,
            'source_type' => StockMovement::SOURCE_INITIAL,
            'source_id' => null,
            'quantity' => $this->faker->numberBetween(1, 100),
            'unit_cost' => $this->faker->numberBetween(100, 10000), // cents
            'total_cost' => null, // Will be calculated
            'movement_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'notes' => $this->faker->optional()->sentence(),
            'balance_quantity' => 0, // Should be calculated by service
            'balance_value' => 0, // Should be calculated by service
            'meta' => null,
            'created_by' => null,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (StockMovement $movement) {
            // Calculate total_cost if not set
            if ($movement->total_cost === null && $movement->unit_cost !== null) {
                $movement->total_cost = (int) abs($movement->quantity * $movement->unit_cost);
            }
        });
    }

    /**
     * Create a stock IN movement (positive quantity).
     */
    public function stockIn(?float $quantity = null): Factory
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $qty = $quantity ?? $this->faker->numberBetween(1, 100);

            return [
                'quantity' => abs($qty),
                'source_type' => StockMovement::SOURCE_BILL_ITEM,
            ];
        });
    }

    /**
     * Create a stock OUT movement (negative quantity).
     */
    public function stockOut(?float $quantity = null): Factory
    {
        return $this->state(function (array $attributes) use ($quantity) {
            $qty = $quantity ?? $this->faker->numberBetween(1, 50);

            return [
                'quantity' => -abs($qty),
                'source_type' => StockMovement::SOURCE_INVOICE_ITEM,
                'unit_cost' => null, // OUT movements don't have unit_cost
            ];
        });
    }

    /**
     * Create an initial stock movement.
     */
    public function initial(?float $quantity = null, ?int $unitCost = null): Factory
    {
        return $this->state(function (array $attributes) use ($quantity, $unitCost) {
            return [
                'quantity' => $quantity ?? $this->faker->numberBetween(10, 500),
                'unit_cost' => $unitCost ?? $this->faker->numberBetween(100, 5000),
                'source_type' => StockMovement::SOURCE_INITIAL,
                'source_id' => null,
            ];
        });
    }

    /**
     * Create an adjustment movement.
     */
    public function adjustment(float $quantity): Factory
    {
        return $this->state(function (array $attributes) use ($quantity) {
            return [
                'quantity' => $quantity,
                'source_type' => StockMovement::SOURCE_ADJUSTMENT,
                'source_id' => null,
            ];
        });
    }

    /**
     * Set a specific warehouse.
     */
    public function forWarehouse(Warehouse $warehouse): Factory
    {
        return $this->state(function (array $attributes) use ($warehouse) {
            return [
                'warehouse_id' => $warehouse->id,
                'company_id' => $warehouse->company_id,
            ];
        });
    }

    /**
     * Set a specific item.
     */
    public function forItem(Item $item): Factory
    {
        return $this->state(function (array $attributes) use ($item) {
            return [
                'item_id' => $item->id,
                'company_id' => $item->company_id,
            ];
        });
    }
}
// CLAUDE-CHECKPOINT
