<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountMappingFactory extends Factory
{
    protected $model = AccountMapping::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'entity_type' => $this->faker->randomElement(AccountMapping::getEntityTypes()),
            'entity_id' => $this->faker->numberBetween(1, 100),
            'debit_account_id' => Account::factory(),
            'credit_account_id' => Account::factory(),
            'transaction_type' => $this->faker->randomElement(AccountMapping::getTransactionTypes()),
            'meta' => null,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => AccountMapping::ENTITY_CUSTOMER,
            'transaction_type' => AccountMapping::TRANSACTION_INVOICE,
        ]);
    }

    public function supplier(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => AccountMapping::ENTITY_SUPPLIER,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);
    }

    public function expenseCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => AccountMapping::ENTITY_EXPENSE_CATEGORY,
            'transaction_type' => AccountMapping::TRANSACTION_EXPENSE,
        ]);
    }

    public function paymentMethod(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => AccountMapping::ENTITY_PAYMENT_METHOD,
            'transaction_type' => AccountMapping::TRANSACTION_PAYMENT,
        ]);
    }

    public function defaultMapping(): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_type' => AccountMapping::ENTITY_DEFAULT,
            'entity_id' => null,
        ]);
    }
}
// CLAUDE-CHECKPOINT
