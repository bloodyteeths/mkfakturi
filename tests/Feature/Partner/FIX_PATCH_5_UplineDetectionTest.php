<?php

namespace Tests\Feature\Partner;

use App\Models\Partner;
use App\Models\Company;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FIX_PATCH_5_UplineDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commissionService = app(CommissionService::class);
    }

    /** @test */
    public function upline_detected_via_partner_referrals_table()
    {
        // Create upline partner
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::factory()->create([
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        // Create downline partner
        $downlineUser = User::factory()->create();
        $downlinePartner = Partner::factory()->create([
            'user_id' => $downlineUser->id,
            'is_active' => true,
        ]);

        // Create partner referral (AC-15 flow)
        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $uplinePartner->id,
            'invitee_partner_id' => $downlinePartner->id,
            'invitee_email' => $downlinePartner->email,
            'referral_token' => 'test-token-123',
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create company user
        $companyUser = User::factory()->create();
        $company = Company::factory()->create();

        $subscriptionAmount = 100.00;

        // Record commission
        $this->commissionService->recordCommission(
            $companyUser,
            $downlinePartner,
            'subscription',
            1,
            $subscriptionAmount
        );

        // Verify upline commission was recorded
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $uplinePartner->id,
            'event_type' => 'subscription',
            'commission_type' => 'upline',
            'commission_amount' => 5.00, // 5% of 100
        ]);

        // Verify downline direct commission
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $downlinePartner->id,
            'event_type' => 'subscription',
            'commission_type' => 'direct',
        ]);
    }

    /** @test */
    public function upline_not_detected_if_referral_status_pending()
    {
        // Create upline partner
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::factory()->create([
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        // Create downline partner
        $downlineUser = User::factory()->create();
        $downlinePartner = Partner::factory()->create([
            'user_id' => $downlineUser->id,
            'is_active' => true,
        ]);

        // Create partner referral with PENDING status
        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $uplinePartner->id,
            'invitee_partner_id' => $downlinePartner->id,
            'invitee_email' => $downlinePartner->email,
            'referral_token' => 'pending-token',
            'status' => 'pending', // NOT accepted
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $companyUser = User::factory()->create();
        $company = Company::factory()->create();

        $this->commissionService->recordCommission(
            $companyUser,
            $downlinePartner,
            'subscription',
            1,
            100.00
        );

        // Verify NO upline commission for pending referral
        $this->assertDatabaseMissing('affiliate_events', [
            'partner_id' => $uplinePartner->id,
            'commission_type' => 'upline',
        ]);
    }

    /** @test */
    public function upline_not_detected_if_upline_partner_inactive()
    {
        // Create INACTIVE upline partner
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::factory()->create([
            'user_id' => $uplineUser->id,
            'is_active' => false, // INACTIVE
        ]);

        // Create downline partner
        $downlineUser = User::factory()->create();
        $downlinePartner = Partner::factory()->create([
            'user_id' => $downlineUser->id,
            'is_active' => true,
        ]);

        // Create accepted referral
        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $uplinePartner->id,
            'invitee_partner_id' => $downlinePartner->id,
            'invitee_email' => $downlinePartner->email,
            'referral_token' => 'inactive-token',
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $companyUser = User::factory()->create();
        $company = Company::factory()->create();

        $this->commissionService->recordCommission(
            $companyUser,
            $downlinePartner,
            'subscription',
            1,
            100.00
        );

        // Verify NO upline commission for inactive partner
        $this->assertDatabaseMissing('affiliate_events', [
            'partner_id' => $uplinePartner->id,
            'commission_type' => 'upline',
        ]);
    }

    /** @test */
    public function fallback_to_legacy_referrer_user_id_if_no_partner_referral()
    {
        // Create upline user (legacy flow)
        $uplineUser = User::factory()->create();
        $uplinePartner = Partner::factory()->create([
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        // Create downline user with legacy referrer_user_id
        $downlineUser = User::factory()->create([
            'referrer_user_id' => $uplineUser->id, // Legacy field
        ]);
        $downlinePartner = Partner::factory()->create([
            'user_id' => $downlineUser->id,
            'is_active' => true,
        ]);

        // NO partner_referrals entry exists (testing fallback)

        $companyUser = User::factory()->create();
        $company = Company::factory()->create();

        $this->commissionService->recordCommission(
            $companyUser,
            $downlinePartner,
            'subscription',
            1,
            100.00
        );

        // Verify upline commission via fallback logic
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $uplinePartner->id,
            'commission_type' => 'upline',
            'commission_amount' => 5.00,
        ]);
    }

    /** @test */
    public function multi_level_chain_via_partner_referrals()
    {
        // Create 3-level chain: Level1 -> Level2 -> Level3
        $level1User = User::factory()->create();
        $level1Partner = Partner::factory()->create([
            'user_id' => $level1User->id,
            'is_active' => true,
        ]);

        $level2User = User::factory()->create();
        $level2Partner = Partner::factory()->create([
            'user_id' => $level2User->id,
            'is_active' => true,
        ]);

        $level3User = User::factory()->create();
        $level3Partner = Partner::factory()->create([
            'user_id' => $level3User->id,
            'is_active' => true,
        ]);

        // Create referral chain
        DB::table('partner_referrals')->insert([
            [
                'inviter_partner_id' => $level1Partner->id,
                'invitee_partner_id' => $level2Partner->id,
                'invitee_email' => $level2Partner->email,
                'referral_token' => 'chain1',
                'status' => 'accepted',
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inviter_partner_id' => $level2Partner->id,
                'invitee_partner_id' => $level3Partner->id,
                'invitee_email' => $level3Partner->email,
                'referral_token' => 'chain2',
                'status' => 'accepted',
                'accepted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $companyUser = User::factory()->create();
        $company = Company::factory()->create();

        // Level3 (bottom of chain) generates commission
        $this->commissionService->recordCommission(
            $companyUser,
            $level3Partner,
            'subscription',
            1,
            100.00
        );

        // Verify Level3 direct commission
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $level3Partner->id,
            'commission_type' => 'direct',
        ]);

        // Verify Level2 upline commission (immediate upline)
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $level2Partner->id,
            'commission_type' => 'upline',
            'commission_amount' => 5.00,
        ]);

        // Note: Current implementation only supports 1-level upline
        // Level1 would need additional logic for 2nd-tier upline commissions
        // This is expected behavior per current AC-18 spec
    }

    /** @test */
    public function partner_referral_takes_precedence_over_legacy_referrer()
    {
        // Create upline via partner_referrals
        $newUplineUser = User::factory()->create();
        $newUplinePartner = Partner::factory()->create([
            'user_id' => $newUplineUser->id,
            'is_active' => true,
        ]);

        // Create old upline via legacy field
        $oldUplineUser = User::factory()->create();
        $oldUplinePartner = Partner::factory()->create([
            'user_id' => $oldUplineUser->id,
            'is_active' => true,
        ]);

        // Create downline with BOTH referral methods
        $downlineUser = User::factory()->create([
            'referrer_user_id' => $oldUplineUser->id, // Legacy
        ]);
        $downlinePartner = Partner::factory()->create([
            'user_id' => $downlineUser->id,
            'is_active' => true,
        ]);

        // New AC-15 referral should take precedence
        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $newUplinePartner->id,
            'invitee_partner_id' => $downlinePartner->id,
            'invitee_email' => $downlinePartner->email,
            'referral_token' => 'new-method',
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $companyUser = User::factory()->create();
        $company = Company::factory()->create();

        $this->commissionService->recordCommission(
            $companyUser,
            $downlinePartner,
            'subscription',
            1,
            100.00
        );

        // Verify NEW upline (partner_referrals) receives commission
        $this->assertDatabaseHas('affiliate_events', [
            'partner_id' => $newUplinePartner->id,
            'commission_type' => 'upline',
        ]);

        // Verify OLD upline (legacy referrer_user_id) does NOT receive commission
        $this->assertDatabaseMissing('affiliate_events', [
            'partner_id' => $oldUplinePartner->id,
            'commission_type' => 'upline',
        ]);
    }
}

// CLAUDE-CHECKPOINT
