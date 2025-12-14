<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\CustomField;
use App\Models\RecurringInvoice;
use App\Models\UsageTracking;
use App\Services\UsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsageLimitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UsageLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UsageLimitService::class);
    }

    /** @test */
    public function it_returns_free_tier_for_company_without_subscription()
    {
        $company = Company::factory()->create();

        $tier = $this->service->getCompanyTier($company);

        $this->assertEquals('free', $tier);
    }

    /** @test */
    public function it_returns_correct_tier_for_company_with_active_subscription()
    {
        $company = Company::factory()->create();
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'standard',
            'status' => 'active',
        ]);

        $tier = $this->service->getCompanyTier($company);

        $this->assertEquals('standard', $tier);
    }

    /** @test */
    public function it_can_increment_usage_for_monthly_feature()
    {
        $company = Company::factory()->create();

        $this->service->incrementUsage($company, 'expenses_per_month');

        $this->assertDatabaseHas('usage_tracking', [
            'company_id' => $company->id,
            'feature' => 'expenses_per_month',
            'period' => now()->format('Y-m'),
            'count' => 1,
        ]);
    }

    /** @test */
    public function it_can_increment_usage_multiple_times()
    {
        $company = Company::factory()->create();

        $this->service->incrementUsage($company, 'expenses_per_month');
        $this->service->incrementUsage($company, 'expenses_per_month');
        $this->service->incrementUsage($company, 'expenses_per_month');

        $record = UsageTracking::where('company_id', $company->id)
            ->where('feature', 'expenses_per_month')
            ->first();

        $this->assertEquals(3, $record->count);
    }

    /** @test */
    public function it_can_check_if_company_can_use_feature_within_limits()
    {
        $company = Company::factory()->create();
        // Free tier has 5 expenses per month
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        // Add 4 expenses
        for ($i = 0; $i < 4; $i++) {
            $this->service->incrementUsage($company, 'expenses_per_month');
        }

        $canUse = $this->service->canUse($company, 'expenses_per_month');

        $this->assertTrue($canUse);
    }

    /** @test */
    public function it_returns_false_when_limit_is_exceeded()
    {
        $company = Company::factory()->create();
        // Free tier has 5 expenses per month
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        // Add 5 expenses (at limit)
        for ($i = 0; $i < 5; $i++) {
            $this->service->incrementUsage($company, 'expenses_per_month');
        }

        $canUse = $this->service->canUse($company, 'expenses_per_month');

        $this->assertFalse($canUse);
    }

    /** @test */
    public function it_returns_true_for_unlimited_features()
    {
        $company = Company::factory()->create();
        // Business tier has unlimited expenses
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'business',
            'status' => 'active',
        ]);

        // Add 1000 expenses
        UsageTracking::create([
            'company_id' => $company->id,
            'feature' => 'expenses_per_month',
            'period' => now()->format('Y-m'),
            'count' => 1000,
        ]);

        $canUse = $this->service->canUse($company, 'expenses_per_month');

        $this->assertTrue($canUse);
    }

    /** @test */
    public function it_gets_correct_usage_statistics()
    {
        $company = Company::factory()->create();
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        // Add 3 expenses (limit is 5)
        for ($i = 0; $i < 3; $i++) {
            $this->service->incrementUsage($company, 'expenses_per_month');
        }

        $usage = $this->service->getUsage($company, 'expenses_per_month');

        $this->assertEquals(3, $usage['used']);
        $this->assertEquals(5, $usage['limit']);
        $this->assertEquals(2, $usage['remaining']);
    }

    /** @test */
    public function it_counts_custom_fields_from_database()
    {
        $company = Company::factory()->create();
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        // Create 2 custom fields (limit is 2 on free)
        CustomField::factory()->count(2)->create([
            'company_id' => $company->id,
        ]);

        $usage = $this->service->getUsage($company, 'custom_fields');

        $this->assertEquals(2, $usage['used']);
        $this->assertEquals(2, $usage['limit']);
        $this->assertEquals(0, $usage['remaining']);
    }

    /** @test */
    public function it_counts_active_recurring_invoices_from_database()
    {
        $company = Company::factory()->create();
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'active',
        ]);

        // Create 1 active recurring invoice
        RecurringInvoice::factory()->create([
            'company_id' => $company->id,
            'status' => 'ACTIVE',
        ]);

        $usage = $this->service->getUsage($company, 'recurring_invoices_active');

        $this->assertEquals(1, $usage['used']);
        $this->assertEquals(1, $usage['limit']);
        $this->assertEquals(0, $usage['remaining']);
    }

    /** @test */
    public function it_can_decrement_usage()
    {
        $company = Company::factory()->create();

        // Add 3 expenses
        for ($i = 0; $i < 3; $i++) {
            $this->service->incrementUsage($company, 'expenses_per_month');
        }

        // Decrement one
        $this->service->decrementUsage($company, 'expenses_per_month');

        $record = UsageTracking::where('company_id', $company->id)
            ->where('feature', 'expenses_per_month')
            ->first();

        $this->assertEquals(2, $record->count);
    }

    /** @test */
    public function it_gets_all_usage_for_company()
    {
        $company = Company::factory()->create();
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'starter',
            'status' => 'active',
        ]);

        // Add some usage
        $this->service->incrementUsage($company, 'expenses_per_month');
        $this->service->incrementUsage($company, 'estimates_per_month');

        $allUsage = $this->service->getAllUsage($company);

        $this->assertArrayHasKey('expenses_per_month', $allUsage);
        $this->assertArrayHasKey('estimates_per_month', $allUsage);
        $this->assertArrayHasKey('custom_fields', $allUsage);
        $this->assertArrayHasKey('recurring_invoices_active', $allUsage);
        $this->assertArrayHasKey('ai_queries_per_month', $allUsage);

        $this->assertEquals(1, $allUsage['expenses_per_month']['used']);
        $this->assertEquals(1, $allUsage['estimates_per_month']['used']);
    }

    /** @test */
    public function it_returns_null_remaining_for_unlimited_features()
    {
        $company = Company::factory()->create();
        CompanySubscription::factory()->create([
            'company_id' => $company->id,
            'plan' => 'max',
            'status' => 'active',
        ]);

        $usage = $this->service->getUsage($company, 'expenses_per_month');

        $this->assertNull($usage['limit']);
        $this->assertNull($usage['remaining']);
    }
}
// CLAUDE-CHECKPOINT
