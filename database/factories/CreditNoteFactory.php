<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CreditNote;
use App\Models\Currency;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditNoteFactory extends Factory
{
    protected $model = CreditNote::class;

    public function definition(): array
    {
        $company = Company::first() ?? Company::factory()->create();
        $currency = Currency::first() ?? Currency::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        return [
            'credit_note_number' => 'CN-' . $this->faker->unique()->numerify('####'),
            'credit_note_date' => now(),
            'status' => 'draft',
            'sub_total' => 10000,
            'tax' => 1800,
            'total' => 11800,
            'discount' => 0,
            'discount_val' => 0,
            'tax_per_item' => 'YES',
            'discount_per_item' => 'NO',
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'exchange_rate' => 1,
            'unique_hash' => $this->faker->uuid(),
        ];
    }
}
