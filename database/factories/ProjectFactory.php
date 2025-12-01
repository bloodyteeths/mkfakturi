<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $company = Company::first() ?? Company::factory()->create();
        $currency = Currency::first() ?? Currency::factory()->create();

        return [
            'company_id' => $company->id,
            'name' => $this->faker->sentence(3),
            'code' => strtoupper($this->faker->bothify('PRJ-###')),
            'description' => $this->faker->paragraph(),
            'customer_id' => null,
            // Use only the base status values supported by original migration
            // (SQLite doesn't support ENUM expansion)
            'status' => $this->faker->randomElement([
                'open',
                'on_hold',
            ]),
            'budget_amount' => $this->faker->numberBetween(10000, 1000000),
            'currency_id' => $currency->id,
            'start_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end_date' => $this->faker->optional(0.7)->dateTimeBetween('now', '+1 year'),
            'creator_id' => null,
            'notes' => $this->faker->optional(0.5)->paragraph(),
        ];
    }

    /**
     * Indicate that the project is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Project::STATUS_OPEN,
        ]);
    }

    /**
     * Indicate that the project is in progress.
     * Note: Using 'open' for SQLite compatibility (in_progress not in original ENUM).
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
        ]);
    }

    /**
     * Indicate that the project is completed (closed).
     * Note: Using 'closed' for SQLite compatibility with original migration.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'closed',
        ]);
    }

    /**
     * Indicate that the project is on hold.
     */
    public function onHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Project::STATUS_ON_HOLD,
        ]);
    }

    /**
     * Indicate that the project has a customer.
     */
    public function withCustomer(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => Customer::factory(),
        ]);
    }
}
