<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use App\Models\AffiliateEvent;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Multi-Level Commission Logic Tests
 *
 * Tests the 3-way commission split functionality:
 * - Direct accountant: 15%
 * - Upline: 5%
 * - Sales rep: 5%
 * Total: 25% of company subscription
 *
 * @ticket AC-01-10 to AC-01-14
 */
class CommissionServiceMultiLevelTest extends TestCase
{
    use RefreshDatabase;

    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = app(CommissionService::class);
    }

    /** @test */
    public function it_calculates_direct_commission_only_when_no_upline_or_sales_rep()
    {
        // Create standalone accountant (no upline, no sales rep)
        $user = User::factory()->create(['partner_subscription_tier' => 'free']);
        $partner = Partner::create([
            'name' => 'Solo Accountant',
            'email' => 'solo@test.com',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // €100 subscription
        $result = $this->commissionService->recordRecurring(
            $company->id,
            100.00,
            now()->format('Y-m')
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(20.00, $result['direct_commission']); // 20% of €100
        $this->assertNull($result['upline_commission']);
        $this->assertNull($result['sales_rep_commission']);

        // Verify only 1 event created
        $this->assertEquals(1, AffiliateEvent::count());

        $event = AffiliateEvent::first();
        $this->assertEquals(20.00, $event->amount);
        $this->assertNull($event->upline_amount);
        $this->assertNull($event->sales_rep_amount);
        $this->assertEquals('direct_only', $event->metadata['split_type']);
    }

    /** @test */
    public function it_calculates_2way_commission_with_upline_only()
    {
        // Create upline
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::create([
            'name' => 'Upline Accountant',
            'email' => 'upline@test.com',
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        // Create direct accountant with upline
        $user = User::factory()->create([
            'partner_subscription_tier' => 'free',
            'referrer_user_id' => $uplineUser->id,
        ]);
        $partner = Partner::create([
            'name' => 'Direct Accountant',
            'email' => 'direct@test.com',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // €100 subscription
        $result = $this->commissionService->recordRecurring(
            $company->id,
            100.00,
            now()->format('Y-m')
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(15.00, $result['direct_commission']); // 15% when upline exists
        $this->assertEquals(5.00, $result['upline_commission']); // 5% to upline
        $this->assertNull($result['sales_rep_commission']);

        // Verify 2 events created (direct + upline)
        $this->assertEquals(2, AffiliateEvent::count());

        // Check direct event
        $directEvent = AffiliateEvent::where('affiliate_partner_id', $partner->id)->first();
        $this->assertEquals(15.00, $directEvent->amount);
        $this->assertEquals(5.00, $directEvent->upline_amount);
        $this->assertNull($directEvent->sales_rep_amount);
        $this->assertEquals('2-way_upline', $directEvent->metadata['split_type']);

        // Check upline event
        $uplineEvent = AffiliateEvent::where('affiliate_partner_id', $uplinePartner->id)->first();
        $this->assertEquals(5.00, $uplineEvent->amount);
        $this->assertEquals('upline', $uplineEvent->metadata['type']);
    }

    /** @test */
    public function it_calculates_2way_commission_with_sales_rep_only()
    {
        // Create sales rep
        $salesRepUser = User::factory()->create(['name' => 'Sales Rep']);

        // Create accountant with sales rep
        $user = User::factory()->create([
            'partner_subscription_tier' => 'free',
            'sales_rep_id' => $salesRepUser->id,
        ]);
        $partner = Partner::create([
            'name' => 'Accountant with Sales Rep',
            'email' => 'accountant@test.com',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // €100 subscription
        $result = $this->commissionService->recordRecurring(
            $company->id,
            100.00,
            now()->format('Y-m')
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(20.00, $result['direct_commission']); // Still 20% (no upline)
        $this->assertNull($result['upline_commission']);
        $this->assertEquals(5.00, $result['sales_rep_commission']); // 5% to sales rep

        // Verify 2 events created (direct + sales rep)
        $this->assertEquals(2, AffiliateEvent::count());

        // Check direct event
        $directEvent = AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->whereNull('metadata->type')
            ->first();
        $this->assertEquals(20.00, $directEvent->amount);
        $this->assertNull($directEvent->upline_amount);
        $this->assertEquals(5.00, $directEvent->sales_rep_amount);
        $this->assertEquals('2-way_sales_rep', $directEvent->metadata['split_type']);

        // Check sales rep event
        $salesRepEvent = AffiliateEvent::where('sales_rep_id', $salesRepUser->id)->first();
        $this->assertEquals(5.00, $salesRepEvent->amount);
        $this->assertEquals('sales_rep', $salesRepEvent->metadata['type']);
    }

    /** @test */
    public function it_calculates_3way_commission_with_upline_and_sales_rep()
    {
        // Create upline
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::create([
            'name' => 'Upline Accountant',
            'email' => 'upline@test.com',
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        // Create sales rep
        $salesRepUser = User::factory()->create(['name' => 'Sales Rep']);

        // Create direct accountant with both upline and sales rep
        $user = User::factory()->create([
            'partner_subscription_tier' => 'free',
            'referrer_user_id' => $uplineUser->id,
            'sales_rep_id' => $salesRepUser->id,
        ]);
        $partner = Partner::create([
            'name' => 'Direct Accountant',
            'email' => 'direct@test.com',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // €100 subscription
        $result = $this->commissionService->recordRecurring(
            $company->id,
            100.00,
            now()->format('Y-m')
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(15.00, $result['direct_commission']); // 15% to direct
        $this->assertEquals(5.00, $result['upline_commission']); // 5% to upline
        $this->assertEquals(5.00, $result['sales_rep_commission']); // 5% to sales rep
        // Total: 25% of subscription goes to affiliates

        // Verify 3 events created (direct + upline + sales rep)
        $this->assertEquals(3, AffiliateEvent::count());

        // Check direct event
        $directEvent = AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->whereNull('metadata->type')
            ->first();
        $this->assertEquals(15.00, $directEvent->amount);
        $this->assertEquals(5.00, $directEvent->upline_amount);
        $this->assertEquals(5.00, $directEvent->sales_rep_amount);
        $this->assertEquals('3-way', $directEvent->metadata['split_type']);

        // Verify total commission is 25%
        $totalCommission = AffiliateEvent::sum('amount');
        $this->assertEquals(25.00, $totalCommission);
    }

    /** @test */
    public function it_calculates_correct_amounts_for_standard_tier()
    {
        // Test with actual pricing: Standard tier = €29/month
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::create([
            'name' => 'Upline',
            'email' => 'upline@test.com',
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        $salesRepUser = User::factory()->create();

        $user = User::factory()->create([
            'referrer_user_id' => $uplineUser->id,
            'sales_rep_id' => $salesRepUser->id,
        ]);
        $partner = Partner::create([
            'name' => 'Direct',
            'email' => 'direct@test.com',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->commissionService->recordRecurring(
            $company->id,
            29.00, // Standard tier price
            now()->format('Y-m')
        );

        $this->assertTrue($result['success']);
        // 15% of €29 = €4.35
        $this->assertEquals(4.35, $result['direct_commission']);
        // 5% of €29 = €1.45
        $this->assertEquals(1.45, $result['upline_commission']);
        // 5% of €29 = €1.45
        $this->assertEquals(1.45, $result['sales_rep_commission']);
        // Total: €7.25 (25% of €29)

        $totalCommission = AffiliateEvent::sum('amount');
        $this->assertEquals(7.25, $totalCommission);
    }

    /** @test */
    public function it_handles_inactive_upline_gracefully()
    {
        // Create inactive upline
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::create([
            'name' => 'Inactive Upline',
            'email' => 'inactive@test.com',
            'user_id' => $uplineUser->id,
            'is_active' => false, // INACTIVE
        ]);

        $user = User::factory()->create([
            'referrer_user_id' => $uplineUser->id, // Has upline but they're inactive
        ]);
        $partner = Partner::create([
            'name' => 'Direct',
            'email' => 'direct@test.com',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $company = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $this->commissionService->recordRecurring(
            $company->id,
            100.00,
            now()->format('Y-m')
        );

        $this->assertTrue($result['success']);
        // Should fall back to direct only (20%) since upline is inactive
        $this->assertEquals(20.00, $result['direct_commission']);
        $this->assertNull($result['upline_commission']);

        // Only 1 event created (no upline event)
        $this->assertEquals(1, AffiliateEvent::count());
    }

    /** @test */
    public function it_records_all_commission_types_in_single_month()
    {
        // Scenario: Accountant has upline + sales rep, brings 3 companies
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::create([
            'name' => 'Upline',
            'email' => 'upline@test.com',
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        $salesRepUser = User::factory()->create();

        $user = User::factory()->create([
            'referrer_user_id' => $uplineUser->id,
            'sales_rep_id' => $salesRepUser->id,
        ]);
        $partner = Partner::create([
            'name' => 'Accountant',
            'email' => 'accountant@test.com',
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $monthRef = now()->format('Y-m');

        // Company 1: €29
        $company1 = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company1->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->commissionService->recordRecurring($company1->id, 29.00, $monthRef);

        // Company 2: €59
        $company2 = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company2->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->commissionService->recordRecurring($company2->id, 59.00, $monthRef);

        // Company 3: €149
        $company3 = Company::factory()->create();
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company3->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->commissionService->recordRecurring($company3->id, 149.00, $monthRef);

        // Total events: 3 companies × 3 events each = 9 events
        $this->assertEquals(9, AffiliateEvent::count());

        // Calculate partner's total earnings for the month
        $partnerEarnings = AffiliateEvent::where('affiliate_partner_id', $partner->id)
            ->whereNull('metadata->type')
            ->sum('amount');

        // Direct commissions: 15% of (€29 + €59 + €149) = 15% of €237 = €35.55
        $this->assertEquals(35.55, $partnerEarnings);

        // Calculate upline's total
        $uplineEarnings = AffiliateEvent::where('affiliate_partner_id', $uplinePartner->id)->sum('amount');
        // 5% of €237 = €11.85
        $this->assertEquals(11.85, $uplineEarnings);

        // Calculate sales rep's total
        $salesRepEarnings = AffiliateEvent::where('sales_rep_id', $salesRepUser->id)->sum('amount');
        // 5% of €237 = €11.85
        $this->assertEquals(11.85, $salesRepEarnings);

        // Total commissions paid out: 25% of €237 = €59.25
        $totalCommissions = AffiliateEvent::sum('amount');
        $this->assertEquals(59.25, $totalCommissions);
    }
}

// CLAUDE-CHECKPOINT
