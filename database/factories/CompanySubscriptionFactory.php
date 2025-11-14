<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanySubscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompanySubscription::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'accountant_id' => null,
            'plan' => 'free',
            'provider' => null,
            'provider_subscription_id' => null,
            'price_monthly' => 0.00,
            'status' => 'trial',
            'started_at' => null,
            'trial_ends_at' => Carbon::now()->addDays(14),
            'canceled_at' => null,
        ];
    }

    /**
     * State for active subscription
     */
    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'active',
                'started_at' => Carbon::now()->subMonth(),
                'trial_ends_at' => null,
            ];
        });
    }

    /**
     * State for canceled subscription
     */
    public function canceled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'canceled',
                'canceled_at' => Carbon::now(),
            ];
        });
    }

    /**
     * State for past due subscription
     */
    public function pastDue(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'past_due',
                'started_at' => Carbon::now()->subMonth(),
            ];
        });
    }

    /**
     * State for starter plan
     */
    public function starter(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'plan' => 'starter',
                'price_monthly' => 29.00,
            ];
        });
    }

    /**
     * State for standard plan
     */
    public function standard(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'plan' => 'standard',
                'price_monthly' => 99.00,
            ];
        });
    }

    /**
     * State for business plan
     */
    public function business(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'plan' => 'business',
                'price_monthly' => 149.00,
            ];
        });
    }

    /**
     * State for max plan
     */
    public function max(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'plan' => 'max',
                'price_monthly' => 299.00,
            ];
        });
    }

    /**
     * State for subscription with accountant referral
     */
    public function withAccountant(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'accountant_id' => User::factory()->create([
                    'account_type' => 'accountant',
                    'partner_tier' => 'free',
                    'kyc_status' => 'verified',
                ]),
            ];
        });
    }
}
// CLAUDE-CHECKPOINT
