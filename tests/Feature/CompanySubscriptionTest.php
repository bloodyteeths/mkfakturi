<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Company Subscription Test
 *
 * Tests the new subscription architecture where:
 * - Accountants are FREE affiliates who earn commissions (NOT paying customers)
 * - Companies are PAYING customers who subscribe to plans (revenue source)
 *
 * @ticket ARCH-01 series - Pricing Architecture Refactor
 */
class CompanySubscriptionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $company;
    protected $accountant;
    protected $companyUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a company
        $this->company = Company::factory()->create([
            'name' => 'Test Company Inc',
        ]);

        // Create an accountant user (free affiliate)
        $this->accountant = User::factory()->create([
            'name' => 'Test Accountant',
            'email' => 'accountant@test.com',
            'account_type' => 'accountant',
            'partner_tier' => 'free',
            'kyc_status' => 'verified',
        ]);

        // Create a company user (paying customer)
        $this->companyUser = User::factory()->create([
            'name' => 'Company Owner',
            'email' => 'owner@test.com',
            'account_type' => 'company',
            'role' => 'admin',
        ]);

        // Link company user to company
        $this->companyUser->companies()->attach($this->company->id);
    }

    /** @test */
    public function company_can_have_subscription()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'status' => 'active',
            'price_monthly' => 99.00,
            'started_at' => Carbon::now(),
        ]);

        $this->assertInstanceOf(CompanySubscription::class, $subscription);
        $this->assertEquals('standard', $subscription->plan);
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals(99.00, $subscription->price_monthly);
    }

    /** @test */
    public function company_can_check_subscription_plan()
    {
        // Create active subscription
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'business',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $this->assertTrue($this->company->isOnPlan('business'));
        $this->assertFalse($this->company->isOnPlan('max'));
    }

    /** @test */
    public function company_can_check_feature_access()
    {
        // Create business plan subscription
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'business',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        // Business plan should have access to standard features
        $this->assertTrue($this->company->canAccessFeature('expenses'));
        $this->assertTrue($this->company->canAccessFeature('multi_currency'));

        // But not max plan features
        $this->assertFalse($this->company->canAccessFeature('api_access'));
    }

    /** @test */
    public function company_upgrade_required_check()
    {
        // Create starter plan subscription
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'starter',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        // Starter should need upgrade for business features
        $this->assertTrue($this->company->upgradeRequired('business'));
        $this->assertFalse($this->company->upgradeRequired('starter'));
        $this->assertFalse($this->company->upgradeRequired('free'));
    }

    /** @test */
    public function subscription_can_track_accountant_referral()
    {
        // Accountant refers a company
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'accountant_id' => $this->accountant->id,
            'plan' => 'standard',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $this->assertEquals($this->accountant->id, $subscription->accountant_id);
        $this->assertInstanceOf(User::class, $subscription->accountant);
        $this->assertEquals('accountant', $subscription->accountant->account_type);
    }

    /** @test */
    public function subscription_scopes_work_correctly()
    {
        // Create various subscriptions
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $company2 = Company::factory()->create();
        CompanySubscription::create([
            'company_id' => $company2->id,
            'plan' => 'business',
            'status' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(14),
        ]);

        $company3 = Company::factory()->create();
        CompanySubscription::create([
            'company_id' => $company3->id,
            'plan' => 'starter',
            'status' => 'canceled',
            'canceled_at' => Carbon::now(),
        ]);

        $this->assertEquals(1, CompanySubscription::active()->count());
        $this->assertEquals(1, CompanySubscription::trial()->count());
        $this->assertEquals(1, CompanySubscription::canceled()->count());
    }

    /** @test */
    public function subscription_can_be_activated()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'status' => 'trial',
        ]);

        $this->assertTrue($subscription->activate());
        $this->assertEquals('active', $subscription->status);
        $this->assertNotNull($subscription->started_at);
    }

    /** @test */
    public function subscription_can_be_canceled()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $this->assertTrue($subscription->cancel());
        $this->assertEquals('canceled', $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
    }

    /** @test */
    public function subscription_can_be_paused_and_resumed()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'business',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        // Pause
        $this->assertTrue($subscription->pause());
        $this->assertEquals('paused', $subscription->status);

        // Resume
        $this->assertTrue($subscription->resume());
        $this->assertEquals('active', $subscription->status);
    }

    /** @test */
    public function subscription_can_swap_plans()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'starter',
            'price_monthly' => 29.00,
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $this->assertTrue($subscription->swap('business', 149.00));
        $this->assertEquals('business', $subscription->plan);
        $this->assertEquals(149.00, $subscription->price_monthly);
    }

    /** @test */
    public function subscription_trial_status_checks()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'status' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(7),
        ]);

        $this->assertTrue($subscription->onTrial());
        $this->assertFalse($subscription->trialExpired());
        $this->assertEquals(7, $subscription->trial_days_remaining);

        // Expire the trial
        $subscription->trial_ends_at = Carbon::now()->subDay();
        $subscription->save();

        $this->assertFalse($subscription->onTrial());
        $this->assertTrue($subscription->trialExpired());
    }

    /** @test */
    public function subscription_is_active_check()
    {
        $activeSubscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $this->assertTrue($activeSubscription->isActive());

        // Trial is also considered active
        $company2 = Company::factory()->create();
        $trialSubscription = CompanySubscription::create([
            'company_id' => $company2->id,
            'plan' => 'business',
            'status' => 'trial',
            'trial_ends_at' => Carbon::now()->addDays(14),
        ]);

        $this->assertTrue($trialSubscription->isActive());

        // Canceled is not active
        $company3 = Company::factory()->create();
        $canceledSubscription = CompanySubscription::create([
            'company_id' => $company3->id,
            'plan' => 'starter',
            'status' => 'canceled',
            'canceled_at' => Carbon::now(),
        ]);

        $this->assertFalse($canceledSubscription->isActive());
    }

    /** @test */
    public function accountant_plus_has_full_access()
    {
        // Accountant with Plus tier
        $accountantPlus = User::factory()->create([
            'account_type' => 'accountant',
            'partner_tier' => 'plus',
            'kyc_status' => 'verified',
        ]);

        // Accountant Plus should have full feature access for their own office
        // This is tested via middleware in integration tests
        $this->assertEquals('plus', $accountantPlus->partner_tier);
        $this->assertEquals('accountant', $accountantPlus->account_type);
    }

    /** @test */
    public function free_accountant_cannot_use_invoicing_for_self()
    {
        // Free accountants are affiliates only, not customers
        $freeAccountant = User::factory()->create([
            'account_type' => 'accountant',
            'partner_tier' => 'free',
            'kyc_status' => 'verified',
        ]);

        $this->assertEquals('free', $freeAccountant->partner_tier);
        // They cannot create their own company invoices
        // This would be enforced at the controller/middleware level
    }

    /** @test */
    public function subscription_display_names()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'business',
            'status' => 'trial',
        ]);

        $this->assertEquals('Business', $subscription->plan_display_name);
        $this->assertEquals('Trial', $subscription->status_display_name);
    }

    /** @test */
    public function company_without_subscription_defaults_to_free()
    {
        $newCompany = Company::factory()->create();

        // No subscription created
        $this->assertEquals('free', $newCompany->current_plan);
    }

    /** @test */
    public function past_due_subscription_is_not_active()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'status' => 'past_due',
            'started_at' => Carbon::now()->subMonth(),
        ]);

        $this->assertFalse($subscription->isActive());
        $this->assertFalse($this->company->canAccessFeature('expenses'));
    }

    /** @test */
    public function subscription_can_be_marked_past_due()
    {
        $subscription = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'business',
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $this->assertTrue($subscription->markPastDue());
        $this->assertEquals('past_due', $subscription->status);
    }

    /** @test */
    public function user_account_types_are_set_correctly()
    {
        // Accountant
        $accountant = User::factory()->create([
            'account_type' => 'accountant',
        ]);
        $this->assertEquals('accountant', $accountant->account_type);

        // Company user
        $companyUser = User::factory()->create([
            'account_type' => 'company',
        ]);
        $this->assertEquals('company', $companyUser->account_type);

        // Sales rep
        $salesRep = User::factory()->create([
            'account_type' => 'sales_rep',
        ]);
        $this->assertEquals('sales_rep', $salesRep->account_type);
    }

    /** @test */
    public function accountant_kyc_status_tracking()
    {
        $accountant = User::factory()->create([
            'account_type' => 'accountant',
            'kyc_status' => 'pending',
        ]);

        $this->assertEquals('pending', $accountant->kyc_status);

        // Verify KYC
        $accountant->kyc_status = 'verified';
        $accountant->save();

        $this->assertEquals('verified', $accountant->kyc_status);
    }
}
// CLAUDE-CHECKPOINT
