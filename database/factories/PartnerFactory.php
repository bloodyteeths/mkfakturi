<?php

namespace Database\Factories;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'company_name' => $this->faker->company(),
            'phone' => $this->faker->phoneNumber(),
            'tax_id' => 'MK' . $this->faker->numerify('##########'),
            'registration_number' => $this->faker->numerify('########'),
            'bank_name' => $this->faker->randomElement(['Komercijalna Banka', 'Stopanska Banka', 'NLB Banka', 'ProCredit Bank']),
            'bank_account' => $this->faker->numerify('###-############-##'),
            'commission_rate' => $this->faker->randomFloat(2, 5, 25),
            'is_active' => $this->faker->boolean(80), // 80% active
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Partner with verified KYC
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => 'verified',
            'is_active' => true,
        ]);
    }

    /**
     * Inactive partner
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * High commission partner
     */
    public function highCommission(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_rate' => 25.00,
            'kyc_status' => 'verified',
            'is_active' => true,
        ]);
    }

    /**
     * Partner with pending KYC
     */
    public function pendingKyc(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => 'pending',
        ]);
    }
}

// CLAUDE-CHECKPOINT
