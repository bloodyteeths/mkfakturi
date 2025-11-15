<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeRateLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExchangeRateLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ExchangeRateLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $company = \App\Models\Company::first() ?? \App\Models\Company::factory()->create();
        $baseCurrency = Currency::first() ?? Currency::factory()->create();
        $targetCurrency = Currency::where('id', '!=', $baseCurrency->id)->first() ?? $baseCurrency;

        return [
            'company_id' => $company->id,
            'base_currency_id' => $baseCurrency->id,
            'currency_id' => $targetCurrency->id,
            'exchange_rate' => $this->faker->randomDigitNotNull(),
        ];
    }
}
