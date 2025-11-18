<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $currencyId = Currency::query()->value('id');

        if (! $currencyId) {
            $currency = Currency::create([
                'name' => 'Macedonian Denar',
                'code' => 'MKD',
                'symbol' => 'ден',
                'precision' => 2,
                'thousand_separator' => ',',
                'decimal_separator' => '.',
                'swap_currency_symbol' => false,
            ]);
            $currencyId = $currency->id;
        }

        return [
            'name' => $this->faker->name(),
            'company_name' => $this->faker->company(),
            'contact_name' => $this->faker->name(),
            'website' => $this->faker->url(),
            'enable_portal' => true,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'role' => 'super admin',
            'password' => Hash::make('secret'),
            'currency_id' => $currencyId,
            'account_type' => 'company', // Default to company user
            'kyc_status' => 'pending',
            'partner_tier' => 'free',
        ];
    }
}
// CLAUDE-CHECKPOINT
