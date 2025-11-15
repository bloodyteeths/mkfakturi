<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Tax;
use App\Models\TaxType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tax::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $company = \App\Models\Company::first() ?? \App\Models\Company::factory()->create();
        $currency = Currency::first() ?? Currency::factory()->create();

        return [
            'tax_type_id' => TaxType::factory(),
            'percent' => function (array $item) {
                return TaxType::find($item['tax_type_id'])->percent;
            },
            'name' => function (array $item) {
                return TaxType::find($item['tax_type_id'])->name;
            },
            'company_id' => $company->id,
            'amount' => $this->faker->randomDigitNotNull(),
            'compound_tax' => $this->faker->randomDigitNotNull(),
            'base_amount' => $this->faker->randomDigitNotNull(),
            'currency_id' => $currency->id,
        ];
    }
}
