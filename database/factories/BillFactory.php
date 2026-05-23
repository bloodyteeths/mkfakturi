<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        $company = Company::first() ?? Company::factory()->create();
        $currency = Currency::first() ?? Currency::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);

        return [
            'bill_number' => 'BILL-' . $this->faker->unique()->numerify('####'),
            'bill_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'DRAFT',
            'paid_status' => 'UNPAID',
            'sub_total' => 10000,
            'tax' => 1800,
            'total' => 11800,
            'due_amount' => 11800,
            'discount' => 0,
            'discount_val' => 0,
            'tax_per_item' => 'YES',
            'discount_per_item' => 'NO',
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'currency_id' => $currency->id,
            'exchange_rate' => 1,
            'unique_hash' => $this->faker->uuid(),
        ];
    }

    public function completed(): self
    {
        return $this->state(['status' => 'COMPLETED']);
    }

    public function reverseCharge(): self
    {
        return $this->state(['is_reverse_charge' => true]);
    }
}
