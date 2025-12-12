<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'code' => $this->faker->unique()->numerify('####'),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(Account::getTypes()),
            'parent_id' => null,
            'is_active' => true,
            'system_defined' => false,
            'description' => $this->faker->optional()->sentence(),
            'meta' => null,
        ];
    }

    public function asset(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_ASSET,
        ]);
    }

    public function liability(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_LIABILITY,
        ]);
    }

    public function equity(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_EQUITY,
        ]);
    }

    public function revenue(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_REVENUE,
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Account::TYPE_EXPENSE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function systemDefined(): static
    {
        return $this->state(fn (array $attributes) => [
            'system_defined' => true,
        ]);
    }
}
// CLAUDE-CHECKPOINT
