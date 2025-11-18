<?php

namespace Database\Factories;

use App\Models\AffiliateLink;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AffiliateLinkFactory extends Factory
{
    protected $model = AffiliateLink::class;

    public function definition(): array
    {
        return [
            'partner_id' => Partner::factory(),
            'code' => Str::random(10),
            'is_active' => true,
            'clicks' => $this->faker->numberBetween(0, 100),
            'conversions' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Inactive affiliate link
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Affiliate link with high engagement
     */
    public function highEngagement(): static
    {
        return $this->state(fn (array $attributes) => [
            'clicks' => $this->faker->numberBetween(100, 500),
            'conversions' => $this->faker->numberBetween(10, 50),
        ]);
    }
}

// CLAUDE-CHECKPOINT
