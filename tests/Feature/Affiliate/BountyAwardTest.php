<?php

namespace Tests\Feature\Affiliate;

use Tests\TestCase;
use App\Models\Partner;
use App\Models\Company;
use App\Models\User;
use App\Models\AffiliateEvent;
use App\Jobs\AwardBounties;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class BountyAwardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create necessary tables
        $this->artisan('migrate');
    }

    /** @test */
    public function it_awards_accountant_bounty_when_partner_has_3_active_companies()
    {
        // Create a verified partner
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'kyc_status' => 'verified',
            'is_active' => true,
        ]);

        // Create 3 active paying companies
        for ($i = 0; $i < 3; $i++) {
            $company = Company::factory()->create();

            // Link partner to company
            DB::table('partner_company_links')->insert([
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create active subscription
            DB::table('company_subscriptions')->insert([
                'company_id' => $company->id,
                'tier' => 'standard',
                'status' => 'active',
                'provider' => 'paddle',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert bounty was awarded
        $this->assertDatabaseHas('affiliate_events', [
            'affiliate_partner_id' => $partner->id,
            'event_type' => 'partner_bounty',
            'amount' => 300.00,
        ]);
    }

    /** @test */
    public function it_awards_accountant_bounty_when_partner_registered_30_days_ago()
    {
        // Create a verified partner registered 31 days ago
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'kyc_status' => 'verified',
            'is_active' => true,
            'created_at' => now()->subDays(31),
        ]);

        // No companies needed for this test

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert bounty was awarded
        $this->assertDatabaseHas('affiliate_events', [
            'affiliate_partner_id' => $partner->id,
            'event_type' => 'partner_bounty',
            'amount' => 300.00,
        ]);
    }

    /** @test */
    public function it_does_not_award_accountant_bounty_if_kyc_not_verified()
    {
        // Create an unverified partner
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'kyc_status' => 'pending',
            'is_active' => true,
        ]);

        // Create 3 active paying companies
        for ($i = 0; $i < 3; $i++) {
            $company = Company::factory()->create();

            DB::table('partner_company_links')->insert([
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('company_subscriptions')->insert([
                'company_id' => $company->id,
                'tier' => 'standard',
                'status' => 'active',
                'provider' => 'paddle',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert bounty was NOT awarded
        $this->assertDatabaseMissing('affiliate_events', [
            'affiliate_partner_id' => $partner->id,
            'event_type' => 'partner_bounty',
        ]);
    }

    /** @test */
    public function it_does_not_award_duplicate_accountant_bounty()
    {
        // Create a verified partner
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'kyc_status' => 'verified',
            'is_active' => true,
            'created_at' => now()->subDays(31),
        ]);

        // Award bounty manually first
        AffiliateEvent::create([
            'affiliate_partner_id' => $partner->id,
            'event_type' => 'partner_bounty',
            'amount' => 300.00,
        ]);

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert only ONE bounty exists
        $this->assertEquals(1, AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->where('event_type', 'partner_bounty')
            ->count());
    }

    /** @test */
    public function it_awards_company_bounty_for_first_paying_company()
    {
        // Create a partner
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        // Create first paying company
        $company = Company::factory()->create();

        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        DB::table('company_subscriptions')->insert([
            'company_id' => $company->id,
            'tier' => 'standard',
            'status' => 'active',
            'provider' => 'paddle',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert company bounty was awarded
        $this->assertDatabaseHas('affiliate_events', [
            'company_id' => $company->id,
            'event_type' => 'company_bounty',
            'amount' => 50.00,
        ]);
    }

    /** @test */
    public function it_does_not_award_company_bounty_if_subscription_is_trial()
    {
        // Create a partner
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        // Create company with trial subscription
        $company = Company::factory()->create();

        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('company_subscriptions')->insert([
            'company_id' => $company->id,
            'tier' => 'standard',
            'status' => 'trialing', // Trial status, not active
            'provider' => 'paddle',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert company bounty was NOT awarded
        $this->assertDatabaseMissing('affiliate_events', [
            'company_id' => $company->id,
            'event_type' => 'company_bounty',
        ]);
    }

    /** @test */
    public function it_does_not_award_duplicate_company_bounty()
    {
        // Create a partner
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        // Create first paying company
        $company = Company::factory()->create();

        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('company_subscriptions')->insert([
            'company_id' => $company->id,
            'tier' => 'standard',
            'status' => 'active',
            'provider' => 'paddle',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Award bounty manually first
        AffiliateEvent::create([
            'affiliate_partner_id' => $partner->id,
            'company_id' => $company->id,
            'event_type' => 'company_bounty',
            'amount' => 50.00,
        ]);

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert only ONE company bounty exists for this partner
        $this->assertEquals(1, AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->where('event_type', 'company_bounty')
            ->count());
    }

    /** @test */
    public function it_awards_only_first_company_bounty_when_multiple_paying_companies_exist()
    {
        // Create a partner
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        // Create multiple paying companies
        $companies = [];
        for ($i = 0; $i < 3; $i++) {
            $company = Company::factory()->create();
            $companies[] = $company;

            DB::table('partner_company_links')->insert([
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'is_active' => true,
                'created_at' => now()->subDays(10 - $i),
                'updated_at' => now()->subDays(10 - $i),
            ]);

            DB::table('company_subscriptions')->insert([
                'company_id' => $company->id,
                'tier' => 'standard',
                'status' => 'active',
                'provider' => 'paddle',
                'created_at' => now()->subDays(10 - $i), // First company has oldest subscription
                'updated_at' => now()->subDays(10 - $i),
            ]);
        }

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert only ONE company bounty was awarded (for first company)
        $this->assertEquals(1, AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->where('event_type', 'company_bounty')
            ->count());

        // Assert it was awarded for the first company (oldest subscription)
        $this->assertDatabaseHas('affiliate_events', [
            'affiliate_partner_id' => $partner->id,
            'company_id' => $companies[0]->id,
            'event_type' => 'company_bounty',
            'amount' => 50.00,
        ]);
    }

    /** @test */
    public function it_does_not_award_accountant_bounty_if_less_than_3_companies_and_less_than_30_days()
    {
        // Create a verified partner registered 29 days ago with only 2 companies
        $user = User::factory()->create();
        $partner = Partner::factory()->create([
            'user_id' => $user->id,
            'kyc_status' => 'verified',
            'is_active' => true,
            'created_at' => now()->subDays(29), // 29 days (not enough)
        ]);

        // Create 2 active paying companies (not enough)
        for ($i = 0; $i < 2; $i++) {
            $company = Company::factory()->create();

            DB::table('partner_company_links')->insert([
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('company_subscriptions')->insert([
                'company_id' => $company->id,
                'tier' => 'standard',
                'status' => 'active',
                'provider' => 'paddle',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Run the job
        $job = new AwardBounties();
        $job->handle(app(CommissionService::class));

        // Assert bounty was NOT awarded
        $this->assertDatabaseMissing('affiliate_events', [
            'affiliate_partner_id' => $partner->id,
            'event_type' => 'partner_bounty',
        ]);
    }
}

// CLAUDE-CHECKPOINT
