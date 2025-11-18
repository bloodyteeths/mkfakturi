<?php

namespace Tests\Feature\Partner;

use App\Models\Partner;
use App\Models\Company;
use App\Models\User;
use App\Models\AffiliateLink;
use App\Models\AffiliateEvent;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AC18_MultiLevelCommissionsTest extends TestCase
{
    use RefreshDatabase;

    protected CommissionService $commissionService;
    protected Partner $directPartner;
    protected Partner $uplinePartner;
    protected Company $company;
    protected User $companyUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commissionService = app(CommissionService::class);

        // Create upline partner
        $uplineUser = User::factory()->create();
        $this->uplinePartner = Partner::factory()->create([
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        // Create direct partner (downline of uplinePartner)
        $directUser = User::factory()->create();
        $this->directPartner = Partner::factory()->create([
            'user_id' => $directUser->id,
            'is_active' => true,
        ]);

        // Create upline relationship
        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $this->uplinePartner->id,
            'invitee_partner_id' => $this->directPartner->id,
            'invitee_email' => $this->directPartner->email,
            'referral_token' => 'upline-token',
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create company user
        $this->companyUser = User::factory()->create();
        $this->company = Company::factory()->create();

        // Create affiliate link
        AffiliateLink::factory()->create([
            'partner_id' => $this->directPartner->id,
            'code' => 'test-affiliate-code',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function direct_partner_receives_22_percent_commission_for_first_year()
    {
        $subscriptionAmount = 100.00;

        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $this->directPartner->id,
            'event_type' => 'subscription',
            'commission_type' => 'direct',
            'commission_amount' => 22.00, // 22% of 100
        ]);
    }

    /** @test */
    public function direct_partner_receives_20_percent_commission_after_first_year()
    {
        $subscriptionAmount = 100.00;

        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            13, // Month 13 = Year 2
            $subscriptionAmount
        );

        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $this->directPartner->id,
            'event_type' => 'subscription',
            'commission_type' => 'direct',
            'commission_amount' => 20.00, // 20% of 100
        ]);
    }

    /** @test */
    public function upline_partner_receives_5_percent_commission()
    {
        $subscriptionAmount = 100.00;

        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        // Verify upline commission recorded
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $this->uplinePartner->id,
            'event_type' => 'subscription',
            'commission_type' => 'upline',
            'commission_amount' => 5.00, // 5% of 100
        ]);
    }

    /** @test */
    public function sales_rep_receives_5_percent_commission_if_assigned()
    {
        // Create sales rep partner
        $salesRepUser = User::factory()->create();
        $salesRep = Partner::factory()->create([
            'user_id' => $salesRepUser->id,
            'is_active' => true,
        ]);

        // Assign sales rep to company
        $this->company->update(['sales_rep_partner_id' => $salesRep->id]);

        $subscriptionAmount = 100.00;

        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $salesRep->id,
            'event_type' => 'subscription',
            'commission_type' => 'sales_rep',
            'commission_amount' => 5.00,
        ]);
    }

    /** @test */
    public function multi_level_commissions_are_recorded_in_one_transaction()
    {
        $subscriptionAmount = 100.00;

        // Record should create multiple commission events atomically
        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        $events = AffiliateEvent::where('user_id', $this->companyUser->id)
            ->where('event_type', 'subscription')
            ->get();

        // Should have at least 2 events: direct + upline
        $this->assertGreaterThanOrEqual(2, $events->count());

        // Verify direct commission
        $directEvent = $events->firstWhere('commission_type', 'direct');
        $this->assertNotNull($directEvent);
        $this->assertEquals($this->directPartner->id, $directEvent->partner_id);

        // Verify upline commission
        $uplineEvent = $events->firstWhere('commission_type', 'upline');
        $this->assertNotNull($uplineEvent);
        $this->assertEquals($this->uplinePartner->id, $uplineEvent->partner_id);
    }

    /** @test */
    public function duplicate_commission_events_are_prevented()
    {
        $subscriptionAmount = 100.00;

        // Record commission first time
        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        $firstCount = AffiliateEvent::where('user_id', $this->companyUser->id)->count();

        // Try to record same commission again
        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        $secondCount = AffiliateEvent::where('user_id', $this->companyUser->id)->count();

        // Count should be the same (no duplicates)
        $this->assertEquals($firstCount, $secondCount);
    }

    /** @test */
    public function commission_amounts_are_rounded_to_two_decimals()
    {
        $subscriptionAmount = 33.33;

        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        $directEvent = AffiliateEvent::where('partner_id', $this->directPartner->id)
            ->where('commission_type', 'direct')
            ->first();

        // 22% of 33.33 = 7.3326, should round to 7.33
        $this->assertEquals(7.33, $directEvent->commission_amount);

        $uplineEvent = AffiliateEvent::where('partner_id', $this->uplinePartner->id)
            ->where('commission_type', 'upline')
            ->first();

        // 5% of 33.33 = 1.6665, should round to 1.67
        $this->assertEquals(1.67, $uplineEvent->commission_amount);
    }

    /** @test */
    public function inactive_partners_do_not_receive_commissions()
    {
        // Deactivate upline partner
        $this->uplinePartner->update(['is_active' => false]);

        $subscriptionAmount = 100.00;

        $this->commissionService->recordCommission(
            $this->companyUser,
            $this->directPartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        // Direct partner should still get commission
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $this->directPartner->id,
            'commission_type' => 'direct',
        ]);

        // Inactive upline should NOT get commission
        $this->assertDatabaseMissing('affiliate_events', [
            'partner_id' => $this->uplinePartner->id,
            'commission_type' => 'upline',
        ]);
    }

    /** @test */
    public function partner_without_upline_only_gets_direct_commission()
    {
        // Create standalone partner with no upline
        $standaloneUser = User::factory()->create();
        $standalonePartner = Partner::factory()->create([
            'user_id' => $standaloneUser->id,
            'is_active' => true,
        ]);

        $subscriptionAmount = 100.00;

        $this->commissionService->recordCommission(
            $this->companyUser,
            $standalonePartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        // Should only have direct commission
        $events = AffiliateEvent::where('user_id', $this->companyUser->id)
            ->where('event_type', 'subscription')
            ->get();

        $this->assertEquals(1, $events->count());
        $this->assertEquals('direct', $events->first()->commission_type);
    }
}

// CLAUDE-CHECKPOINT
