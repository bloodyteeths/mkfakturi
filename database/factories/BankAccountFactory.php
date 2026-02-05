<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Company;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'currency_id' => Currency::factory(),
            'account_name' => $this->faker->company() . ' Account',
            'account_number' => $this->faker->bankAccountNumber(),
            'iban' => $this->faker->iban('MK'),
            'swift_code' => $this->faker->swiftBicNumber(),
            'bank_name' => $this->faker->randomElement(['Stopanska Banka', 'Komercijalna Banka', 'NLB Tutunska Banka', 'Halk Bank']),
            'bank_code' => $this->faker->numerify('###'),
            'branch' => $this->faker->city(),
            'account_type' => $this->faker->randomElement(['checking', 'savings', 'business']),
            'opening_balance' => $this->faker->randomFloat(2, 0, 100000),
            'current_balance' => $this->faker->randomFloat(2, 0, 100000),
            'is_primary' => false,
            'is_active' => true,
            'notes' => null,
        ];
    }

    /**
     * Mark the account as primary
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Mark the account as inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
