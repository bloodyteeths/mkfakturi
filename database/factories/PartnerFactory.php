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
            'address' => $this->faker->address(),
            'vat_number' => 'MK' . $this->faker->numerify('##########'),
            'bank_name' => $this->faker->randomElement(['Komercijalna Banka', 'Stopanska Banka', 'NLB Banka', 'ProCredit Bank']),
            'bank_account' => $this->faker->numerify('###-############-##'),
            'kyc_status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'kyc_submitted_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'kyc_approved_at' => $this->faker->optional()->dateTimeBetween('-20 days', 'now'),
            'is_active' => $this->faker->boolean(80), // 80% active
            'partner_tier' => $this->faker->randomElement(['standard', 'plus']),
            'activation_date' => $this->faker->optional()->dateTimeBetween('-60 days', 'now'),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Partner with approved KYC
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => 'approved',
            'kyc_submitted_at' => now()->subDays(10),
            'kyc_approved_at' => now()->subDays(5),
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
     * Partner Plus tier
     */
    public function plus(): static
    {
        return $this->state(fn (array $attributes) => [
            'partner_tier' => 'plus',
            'kyc_status' => 'approved',
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
            'kyc_submitted_at' => now()->subDays(2),
            'kyc_approved_at' => null,
        ]);
    }
}

// CLAUDE-CHECKPOINT
