<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invoice::class;

    public function sent()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_SENT,
            ];
        });
    }

    public function viewed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_VIEWED,
            ];
        });
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_COMPLETED,
            ];
        });
    }

    public function unpaid()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_UNPAID,
            ];
        });
    }

    public function partiallyPaid()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_PARTIALLY_PAID,
            ];
        });
    }

    public function paid()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => Invoice::STATUS_PAID,
            ];
        });
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Create or reuse a simple company for test data
        $company = \App\Models\Company::first() ?? \App\Models\Company::factory()->create();

        return [
            'invoice_date' => $this->faker->date('Y-m-d', 'now'),
            'due_date' => $this->faker->date('Y-m-d', 'now'),
            'invoice_number' => 'INV-'.$this->faker->unique()->numerify('######'),
            'sequence_number' => $this->faker->numberBetween(1, 1_000_000),
            'customer_sequence_number' => $this->faker->numberBetween(1, 1_000_000),
            'reference_number' => 'REF-'.$this->faker->unique()->numerify('######'),
            'template_name' => 'invoice1',
            'status' => Invoice::STATUS_DRAFT,
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'paid_status' => Invoice::STATUS_UNPAID,
            'company_id' => $company->id,
            'sub_total' => $this->faker->randomDigitNotNull(),
            'total' => $this->faker->randomDigitNotNull(),
            'discount_type' => $this->faker->randomElement(['percentage', 'fixed']),
            'discount_val' => function (array $invoice) {
                return $invoice['discount_type'] == 'percentage' ? $this->faker->numberBetween($min = 0, $max = 100) : $this->faker->randomDigitNotNull();
            },
            'discount' => function (array $invoice) {
                return $invoice['discount_type'] == 'percentage' ? (($invoice['discount_val'] * $invoice['total']) / 100) : $invoice['discount_val'];
            },
            'tax' => $this->faker->randomDigitNotNull(),
            'due_amount' => function (array $invoice) {
                return $invoice['total'];
            },
            'notes' => $this->faker->text(80),
            'unique_hash' => str_random(60),
            'customer_id' => null, // Must be passed in or use ->for(Customer::factory())
            'recurring_invoice_id' => null,
            'exchange_rate' => $this->faker->randomDigitNotNull(),
            'base_discount_val' => $this->faker->randomDigitNotNull(),
            'base_sub_total' => $this->faker->randomDigitNotNull(),
            'base_total' => $this->faker->randomDigitNotNull(),
            'base_tax' => $this->faker->randomDigitNotNull(),
            'base_due_amount' => $this->faker->randomDigitNotNull(),
            'currency_id' => Currency::first()->id ?? Currency::create([
                'name' => 'Macedonian Denar',
                'code' => 'MKD',
                'symbol' => 'ден',
                'precision' => 2,
                'thousand_separator' => ',',
                'decimal_separator' => '.',
                'swap_currency_symbol' => false,
            ])->id,
        ];
    }
}
