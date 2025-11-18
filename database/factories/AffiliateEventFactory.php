<?php

namespace Database\Factories;

use App\Models\AffiliateEvent;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AffiliateEventFactory extends Factory
{
    protected $model = AffiliateEvent::class;

    public function definition(): array
    {
        $eventTypes = ['signup', 'subscription', 'renewal', 'upgrade'];
        $commissionTypes = ['direct', 'upline', 'sales_rep'];

        return [
            'partner_id' => Partner::factory(),
            'user_id' => User::factory(),
            'event_type' => $this->faker->randomElement($eventTypes),
            'commission_type' => $this->faker->randomElement($commissionTypes),
            'commission_amount' => $this->faker->randomFloat(2, 5, 100),
            'subscription_month' => $this->faker->numberBetween(1, 24),
            'event_date' => $this->faker->dateTimeBetween('-90 days', 'now'),
            'metadata' => json_encode([
                'plan' => $this->faker->randomElement(['basic', 'professional', 'enterprise']),
                'amount' => $this->faker->randomFloat(2, 20, 500),
            ]),
        ];
    }

    /**
     * Direct commission (22% first year, 20% after)
     */
    public function direct(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'direct',
            'commission_amount' => $attributes['subscription_month'] <= 12
                ? round($attributes['commission_amount'] * 0.22, 2)
                : round($attributes['commission_amount'] * 0.20, 2),
        ]);
    }

    /**
     * Upline commission (5%)
     */
    public function upline(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'upline',
            'commission_amount' => round($attributes['commission_amount'] * 0.05, 2),
        ]);
    }

    /**
     * Sales rep commission (5%)
     */
    public function salesRep(): static
    {
        return $this->state(fn (array $attributes) => [
            'commission_type' => 'sales_rep',
            'commission_amount' => round($attributes['commission_amount'] * 0.05, 2),
        ]);
    }

    /**
     * Subscription event
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'subscription',
            'subscription_month' => $this->faker->numberBetween(1, 24),
        ]);
    }

    /**
     * Signup event
     */
    public function signup(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => 'signup',
            'commission_amount' => 0, // Signup events track conversions but no commission
            'subscription_month' => null,
        ]);
    }
}

// CLAUDE-CHECKPOINT
