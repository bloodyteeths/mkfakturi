<?php

namespace Tests\Feature;

use App\Models\AffiliateEvent;
use App\Models\AffiliateLink;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

/**
 * Affiliate System End-to-End Test
 *
 * Tests the complete affiliate flow from partner creation through payout:
 * 1. Partner creates referral link
 * 2. Company signs up via referral
 * 3. Company subscribes to plan
 * 4. Commission recorded automatically
 * 5. Multi-level commission test
 * 6. Payout processing
 *
 * @ticket E2E-AFFILIATE
 */
class AffiliateSystemEndToEndTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;

    protected User $partnerUser;

    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies if needed
        if (Currency::count() === 0) {
            Artisan::call('db:seed', ['--class' => 'CurrenciesTableSeeder']);
        }

        $this->commissionService = app(CommissionService::class);
    }

    /** @test */
    public function complete_affiliate_flow_from_referral_to_payout()
    {
        // ===== STEP 1: Partner creates referral link =====
        $this->info('Step 1: Partner creates referral link');

        $this->partnerUser = User::factory()->create([
            'name' => 'Accountant Partner',
            'email' => 'accountant@test.com',
            'partner_subscription_tier' => 'free',
        ]);

        $this->partner = Partner::create([
            'name' => 'Accountant Partner',
            'email' => 'accountant@test.com',
            'user_id' => $this->partnerUser->id,
            'is_active' => true,
        ]);

        // Generate affiliate link
        $affiliateLink = AffiliateLink::create([
            'partner_id' => $this->partner->id,
            'code' => 'ACC'.strtoupper(Str::random(6)),
            'target' => 'company',
            'is_active' => true,
        ]);

        $this->assertNotNull($affiliateLink);
        $this->assertDatabaseHas('affiliate_links', [
            'partner_id' => $this->partner->id,
            'code' => $affiliateLink->code,
            'is_active' => true,
        ]);

        $this->info('✓ Affiliate link created: '.$affiliateLink->code);

        // ===== STEP 2: Company signs up via referral =====
        $this->info('Step 2: Company signs up via referral');

        // Simulate registration with referral code
        $companyUser = User::factory()->create([
            'name' => 'Company Owner',
            'email' => 'owner@company.com',
            'referrer_user_id' => $this->partnerUser->id, // Tracked from ?ref=CODE
        ]);

        $company = Company::factory()->create([
            'name' => 'Test Company Inc',
        ]);

        // Link partner to company
        DB::table('partner_company_links')->insert([
            'partner_id' => $this->partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals($this->partnerUser->id, $companyUser->referrer_user_id);
        $this->assertDatabaseHas('partner_company_links', [
            'partner_id' => $this->partner->id,
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $this->info('✓ Company registered with referrer_user_id set');

        // ===== STEP 3: Company subscribes to plan =====
        $this->info('Step 3: Company subscribes to plan');

        // Create Paddle subscription (mock)
        $subscription = $company->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_test_'.Str::random(10),
            'status' => 'active',
        ]);

        $this->assertNotNull($subscription);
        $this->info('✓ Subscription created: '.$subscription->paddle_id);

        // ===== STEP 4: Commission recorded automatically =====
        $this->info('Step 4: Webhook triggers commission calculation');

        $subscriptionAmount = 29.00; // Starter plan
        $monthRef = now()->format('Y-m');

        // Simulate Paddle webhook calling CommissionService
        $result = $this->commissionService->recordRecurring(
            $company->id,
            $subscriptionAmount,
            $monthRef,
            $subscription->paddle_id
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(5.80, $result['direct_commission']); // 20% of 29.00
        $this->assertNull($result['upline_commission']);

        $event = AffiliateEvent::find($result['event_id']);
        $this->assertNotNull($event);
        $this->assertEquals($this->partner->id, $event->affiliate_partner_id);
        $this->assertEquals('recurring_commission', $event->event_type);
        $this->assertEquals(5.80, $event->amount);
        $this->assertEquals($monthRef, $event->month_ref);
        $this->assertNull($event->paid_at); // Initially unpaid
        $this->assertFalse($event->is_clawed_back);

        $this->info('✓ Commission recorded: €5.80 (20% of €29.00)');

        // ===== STEP 5: Simulate recurring payments for multiple months =====
        $this->info('Step 5: Simulate 11 more months of recurring payments');

        for ($i = 1; $i <= 11; $i++) {
            $futureMonth = now()->addMonths($i)->format('Y-m');
            $this->commissionService->recordRecurring(
                $company->id,
                $subscriptionAmount,
                $futureMonth,
                $subscription->paddle_id
            );
        }

        // Verify 12 months total (1 initial + 11 additional)
        $totalEvents = AffiliateEvent::where('company_id', $company->id)
            ->where('event_type', 'recurring_commission')
            ->count();
        $this->assertEquals(12, $totalEvents);

        $totalCommissions = AffiliateEvent::where('affiliate_partner_id', $this->partner->id)
            ->where('is_clawed_back', false)
            ->sum('amount');
        $this->assertEquals(69.60, $totalCommissions); // 12 * 5.80

        $this->info('✓ 12 months of commissions recorded: €69.60 total');

        // ===== STEP 6: Payout processing (dry run) =====
        $this->info('Step 6: Test payout command (dry-run)');

        // Add a few more companies to exceed €100 threshold
        $this->createAdditionalCompanies($this->partner, 2, 50.00);

        $unpaidTotal = $this->partner->getUnpaidCommissionsTotal();
        $this->assertGreaterThan(100.00, $unpaidTotal);

        $this->info("✓ Total unpaid commissions: €{$unpaidTotal}");

        // Run payout command in dry-run mode
        Artisan::call('affiliate:process-payouts', [
            '--dry-run' => true,
            '--force' => true,
        ]);

        // Verify no payout was created (dry-run)
        $this->assertEquals(0, Payout::count());

        // ===== STEP 7: Actual payout processing =====
        $this->info('Step 7: Process actual payout');

        // Backdate events to be outside clawback period (30+ days ago)
        AffiliateEvent::where('affiliate_partner_id', $this->partner->id)
            ->update(['created_at' => now()->subDays(35)]);

        // Run actual payout
        Artisan::call('affiliate:process-payouts', [
            '--force' => true,
        ]);

        // Verify payout was created
        $payout = Payout::where('partner_id', $this->partner->id)->first();
        $this->assertNotNull($payout);
        $this->assertEquals('pending', $payout->status);
        $this->assertGreaterThan(100.00, $payout->amount);

        // Verify events are marked as paid
        $paidEvents = AffiliateEvent::where('affiliate_partner_id', $this->partner->id)
            ->whereNotNull('paid_at')
            ->count();
        $this->assertGreaterThan(0, $paidEvents);

        $this->info("✓ Payout created: Payout #{$payout->id} for €{$payout->amount}");
        $this->info('✓ Events marked as paid');

        $this->info('========================================');
        $this->info('✓✓✓ COMPLETE AFFILIATE FLOW SUCCESSFUL ✓✓✓');
        $this->info('========================================');
    }

    /** @test */
    public function multi_level_commission_flow()
    {
        $this->info('Testing Multi-Level Commission Flow');

        // ===== Create partner chain: Sales Rep → Accountant → Company =====

        // 1. Sales Rep (upline)
        $salesRepUser = User::factory()->create([
            'name' => 'Sales Rep',
            'email' => 'salesrep@test.com',
            'partner_subscription_tier' => 'free',
        ]);

        $salesRep = Partner::create([
            'name' => 'Sales Rep',
            'email' => 'salesrep@test.com',
            'user_id' => $salesRepUser->id,
            'is_active' => true,
        ]);

        // 2. Accountant (direct partner, referred by sales rep)
        $accountantUser = User::factory()->create([
            'name' => 'Accountant',
            'email' => 'accountant@test.com',
            'partner_subscription_tier' => 'free',
            'referrer_user_id' => $salesRepUser->id, // Key: referred by sales rep
        ]);

        $accountant = Partner::create([
            'name' => 'Accountant',
            'email' => 'accountant@test.com',
            'user_id' => $accountantUser->id,
            'is_active' => true,
        ]);

        // 3. Company (referred by accountant)
        $company = Company::factory()->create([
            'name' => 'Multi-Level Test Co',
        ]);

        DB::table('partner_company_links')->insert([
            'partner_id' => $accountant->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 4. Company subscribes
        $company->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_multilevel_test',
            'status' => 'active',
        ]);

        // 5. Record commission
        $subscriptionAmount = 100.00;
        $monthRef = now()->format('Y-m');

        $result = $this->commissionService->recordRecurring(
            $company->id,
            $subscriptionAmount,
            $monthRef,
            'sub_multilevel_test'
        );

        // ===== Verify multi-level split: 15% direct + 5% upline =====
        $this->assertTrue($result['success']);
        $this->assertEquals(15.00, $result['direct_commission']); // Accountant gets 15%
        $this->assertEquals(5.00, $result['upline_commission']); // Sales Rep gets 5%

        // Verify accountant event
        $accountantEvent = AffiliateEvent::where('affiliate_partner_id', $accountant->id)
            ->where('month_ref', $monthRef)
            ->first();
        $this->assertNotNull($accountantEvent);
        $this->assertEquals(15.00, $accountantEvent->amount);
        $this->assertEquals(5.00, $accountantEvent->upline_amount);
        $this->assertEquals($salesRep->id, $accountantEvent->upline_partner_id);

        // Verify sales rep event
        $salesRepEvent = AffiliateEvent::where('affiliate_partner_id', $salesRep->id)
            ->where('month_ref', $monthRef)
            ->first();
        $this->assertNotNull($salesRepEvent);
        $this->assertEquals(5.00, $salesRepEvent->amount);
        $this->assertArrayHasKey('type', $salesRepEvent->metadata);
        $this->assertEquals('upline', $salesRepEvent->metadata['type']);

        $this->info('✓ Multi-level commission verified: Accountant €15, Sales Rep €5');
    }

    /** @test */
    public function partner_plus_gets_higher_commission_rate()
    {
        $this->info('Testing Partner Plus Commission Rate');

        // Create Partner Plus user
        $partnerPlusUser = User::factory()->create([
            'partner_subscription_tier' => 'plus', // Paid for Plus tier
        ]);

        $partnerPlus = Partner::create([
            'name' => 'Partner Plus',
            'email' => 'plus@test.com',
            'user_id' => $partnerPlusUser->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();

        DB::table('partner_company_links')->insert([
            'partner_id' => $partnerPlus->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Record commission
        $result = $this->commissionService->recordRecurring(
            $company->id,
            100.00,
            now()->format('Y-m')
        );

        // Partner Plus should get 22% instead of 20%
        $this->assertEquals(22.00, $result['direct_commission']);
        $this->info('✓ Partner Plus commission: €22 (22% of €100)');
    }

    /** @test */
    public function prevents_duplicate_commission_for_same_month()
    {
        $this->setupBasicPartnerCompany();

        $monthRef = now()->format('Y-m');

        // First commission
        $result1 = $this->commissionService->recordRecurring(
            $this->partner->companies()->first()->id,
            100.00,
            $monthRef
        );

        $this->assertTrue($result1['success']);

        // Try duplicate
        $result2 = $this->commissionService->recordRecurring(
            $this->partner->companies()->first()->id,
            100.00,
            $monthRef
        );

        $this->assertFalse($result2['success']);
        $this->assertEquals('Commission already recorded', $result2['message']);
        $this->info('✓ Duplicate commission prevented');
    }

    /** @test */
    public function payout_only_processes_events_outside_clawback_period()
    {
        $this->setupBasicPartnerCompany();
        $company = $this->partner->companies()->first();

        // Create events inside clawback period (recent)
        $recentEvent = AffiliateEvent::create([
            'affiliate_partner_id' => $this->partner->id,
            'company_id' => $company->id,
            'event_type' => 'recurring_commission',
            'amount' => 50.00,
            'month_ref' => now()->format('Y-m'),
            'created_at' => now()->subDays(10), // Too recent
        ]);

        // Create events outside clawback period (old)
        $oldEvent = AffiliateEvent::create([
            'affiliate_partner_id' => $this->partner->id,
            'company_id' => $company->id,
            'event_type' => 'recurring_commission',
            'amount' => 60.00,
            'month_ref' => now()->subMonth()->format('Y-m'),
            'created_at' => now()->subDays(40), // Outside clawback period
        ]);

        // Run payout
        Artisan::call('affiliate:process-payouts', ['--force' => true]);

        // Verify only old event was paid
        $recentEvent->refresh();
        $oldEvent->refresh();

        $this->assertNull($recentEvent->paid_at);
        $this->assertNotNull($oldEvent->paid_at);

        $this->info('✓ Only events outside clawback period were paid');
    }

    /** @test */
    public function payout_respects_minimum_threshold()
    {
        $this->setupBasicPartnerCompany();
        $company = $this->partner->companies()->first();

        // Create commission below threshold (€99)
        AffiliateEvent::create([
            'affiliate_partner_id' => $this->partner->id,
            'company_id' => $company->id,
            'event_type' => 'recurring_commission',
            'amount' => 99.00,
            'month_ref' => now()->format('Y-m'),
            'created_at' => now()->subDays(40),
        ]);

        Artisan::call('affiliate:process-payouts', ['--force' => true]);

        // No payout should be created (below €100 threshold)
        $this->assertEquals(0, Payout::count());

        $this->info('✓ Payout skipped for amount below threshold');

        // Add more to exceed threshold
        AffiliateEvent::create([
            'affiliate_partner_id' => $this->partner->id,
            'company_id' => $company->id,
            'event_type' => 'recurring_commission',
            'amount' => 10.00,
            'month_ref' => now()->addMonth()->format('Y-m'),
            'created_at' => now()->subDays(40),
        ]);

        Artisan::call('affiliate:process-payouts', ['--force' => true]);

        // Now payout should be created (€109 total)
        $this->assertEquals(1, Payout::count());
        $payout = Payout::first();
        $this->assertEquals(109.00, $payout->amount);

        $this->info('✓ Payout created after exceeding threshold: €109');
    }

    // ===== Helper Methods =====

    protected function setupBasicPartnerCompany()
    {
        $this->partnerUser = User::factory()->create([
            'partner_subscription_tier' => 'free',
        ]);

        $this->partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'user_id' => $this->partnerUser->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();

        DB::table('partner_company_links')->insert([
            'partner_id' => $this->partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function createAdditionalCompanies(Partner $partner, int $count, float $commissionAmount)
    {
        for ($i = 0; $i < $count; $i++) {
            $company = Company::factory()->create([
                'name' => "Additional Company {$i}",
            ]);

            DB::table('partner_company_links')->insert([
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            AffiliateEvent::create([
                'affiliate_partner_id' => $partner->id,
                'company_id' => $company->id,
                'event_type' => 'recurring_commission',
                'amount' => $commissionAmount,
                'month_ref' => now()->format('Y-m'),
                'created_at' => now()->subDays(35), // Outside clawback
            ]);
        }
    }

    protected function info(string $message)
    {
        echo "\n".$message;
    }
}

// CLAUDE-CHECKPOINT
