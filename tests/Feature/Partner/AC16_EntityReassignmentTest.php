<?php

namespace Tests\Feature\Partner;

use App\Models\Partner;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AC16_EntityReassignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected Partner $oldPartner;
    protected Partner $newPartner;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);

        $oldUser = User::factory()->create();
        $this->oldPartner = Partner::factory()->create(['user_id' => $oldUser->id]);

        $newUser = User::factory()->create();
        $this->newPartner = Partner::factory()->create(['user_id' => $newUser->id]);

        $this->company = Company::factory()->create();
    }

    /** @test */
    public function super_admin_can_reassign_company_to_new_partner()
    {
        // Create existing assignment
        DB::table('partner_company_links')->insert([
            'partner_id' => $this->oldPartner->id,
            'company_id' => $this->company->id,
            'permissions' => json_encode(['view_reports', 'manage_invoices']),
            'is_active' => true,
            'invitation_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/reassignments/company-partner', [
                'company_id' => $this->company->id,
                'old_partner_id' => $this->oldPartner->id,
                'new_partner_id' => $this->newPartner->id,
                'reason' => 'Partner change requested by client',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Company reassigned successfully']);

        // Verify old link deactivated
        $this->assertDatabaseHas('partner_company_links', [
            'partner_id' => $this->oldPartner->id,
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        // Verify new link created
        $this->assertDatabaseHas('partner_company_links', [
            'partner_id' => $this->newPartner->id,
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        // Verify reassignment log
        $this->assertDatabaseHas('entity_reassignments', [
            'entity_type' => 'company',
            'entity_id' => $this->company->id,
            'from_partner_id' => $this->oldPartner->id,
            'to_partner_id' => $this->newPartner->id,
            'reason' => 'Partner change requested by client',
        ]);
    }

    /** @test */
    public function super_admin_can_reassign_partner_upline()
    {
        $oldUpline = Partner::factory()->create(['user_id' => User::factory()->create()->id]);
        $newUpline = Partner::factory()->create(['user_id' => User::factory()->create()->id]);
        $downlinePartner = Partner::factory()->create(['user_id' => User::factory()->create()->id]);

        // Create existing upline relationship
        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $oldUpline->id,
            'invitee_partner_id' => $downlinePartner->id,
            'invitee_email' => $downlinePartner->email,
            'referral_token' => 'old-token',
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->postJson('/reassignments/partner-upline', [
                'partner_id' => $downlinePartner->id,
                'old_upline_id' => $oldUpline->id,
                'new_upline_id' => $newUpline->id,
                'reason' => 'Upline partner inactive',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Partner upline reassigned successfully']);

        // Verify old referral status changed
        $this->assertDatabaseHas('partner_referrals', [
            'inviter_partner_id' => $oldUpline->id,
            'invitee_partner_id' => $downlinePartner->id,
            'status' => 'reassigned',
        ]);

        // Verify new referral created
        $this->assertDatabaseHas('partner_referrals', [
            'inviter_partner_id' => $newUpline->id,
            'invitee_partner_id' => $downlinePartner->id,
            'status' => 'accepted',
        ]);

        // Verify reassignment log
        $this->assertDatabaseHas('entity_reassignments', [
            'entity_type' => 'partner_upline',
            'entity_id' => $downlinePartner->id,
            'from_partner_id' => $oldUpline->id,
            'to_partner_id' => $newUpline->id,
        ]);
    }

    /** @test */
    public function regular_user_cannot_reassign_entities()
    {
        $regularUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regularUser, 'sanctum')
            ->postJson('/reassignments/company-partner', [
                'company_id' => $this->company->id,
                'old_partner_id' => $this->oldPartner->id,
                'new_partner_id' => $this->newPartner->id,
                'reason' => 'Test',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function can_retrieve_reassignment_log()
    {
        // Create reassignment records
        DB::table('entity_reassignments')->insert([
            [
                'entity_type' => 'company',
                'entity_id' => $this->company->id,
                'from_partner_id' => $this->oldPartner->id,
                'to_partner_id' => $this->newPartner->id,
                'reason' => 'Test reassignment 1',
                'reassigned_by' => $this->superAdmin->id,
                'reassigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'entity_type' => 'partner_upline',
                'entity_id' => 999,
                'from_partner_id' => $this->oldPartner->id,
                'to_partner_id' => $this->newPartner->id,
                'reason' => 'Test reassignment 2',
                'reassigned_by' => $this->superAdmin->id,
                'reassigned_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/reassignments/log');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    /** @test */
    public function can_get_current_partner_for_company()
    {
        DB::table('partner_company_links')->insert([
            'partner_id' => $this->oldPartner->id,
            'company_id' => $this->company->id,
            'permissions' => json_encode(['view_reports']),
            'is_active' => true,
            'invitation_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/companies/{$this->company->id}/current-partner");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $this->oldPartner->id,
                'email' => $this->oldPartner->email,
            ]);
    }

    /** @test */
    public function can_get_upline_partner_for_partner()
    {
        $upline = Partner::factory()->create(['user_id' => User::factory()->create()->id]);
        $downline = Partner::factory()->create(['user_id' => User::factory()->create()->id]);

        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $upline->id,
            'invitee_partner_id' => $downline->id,
            'invitee_email' => $downline->email,
            'referral_token' => 'token123',
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson("/partners/{$downline->id}/upline");

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $upline->id,
                'email' => $upline->email,
            ]);
    }
}

// CLAUDE-CHECKPOINT
