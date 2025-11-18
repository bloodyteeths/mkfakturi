<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\SerialNumberFormatter;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        // Create or reuse a simple company for numbering
        $company = \App\Models\Company::first() ?? \App\Models\Company::factory()->create();

        $sequenceNumber = (new SerialNumberFormatter)
            ->setModel(new Payment)
            ->setCompany($company->id)
            ->setNextNumbers();

        // Ensure we have a payment method for this company
        $paymentMethod = PaymentMethod::first() ?? PaymentMethod::factory()->create([
            'company_id' => $company->id,
        ]);

        // Ensure we have at least one currency
        $currency = Currency::first() ?? Currency::create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'swap_currency_symbol' => false,
        ]);

        return [
            'company_id' => $company->id,
            'payment_date' => $this->faker->date('Y-m-d', 'now'),
            'notes' => $this->faker->text(80),
            'amount' => $this->faker->randomDigitNotNull(),
            'sequence_number' => $sequenceNumber->nextSequenceNumber,
            'customer_sequence_number' => $sequenceNumber->nextCustomerSequenceNumber,
            'payment_number' => $sequenceNumber->getNextNumber(),
            'unique_hash' => str_random(60),
            'payment_method_id' => $paymentMethod->id,
            'customer_id' => Customer::factory(),
            'base_amount' => $this->faker->randomDigitNotNull(),
            'currency_id' => $currency->id,
        ];
    }
}
