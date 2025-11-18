<?php

namespace Tests\Feature\Partner;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AC14_CompanyInvitesCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected Company $inviterCompany;
    protected User $companyUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyUser = User::factory()->create();
        $this->inviterCompany = Company::factory()->create();
    }

    /** @test */
    public function company_can_invite_another_company()
    {
        $response = $this->actingAs($this->companyUser, 'sanctum')
            ->postJson('/invitations/company-to-company', [
                'inviter_company_id' => $this->inviterCompany->id,
                'invitee_email' => 'newcompany@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'signup_link',
            ]);

        // Verify referral created
        $this->assertDatabaseHas('company_referrals', [
            'inviter_company_id' => $this->inviterCompany->id,
            'invitee_email' => 'newcompany@example.com',
            'status' => 'pending',
        ]);

        // Verify signup link contains token
        $link = $response->json('signup_link');
        $this->assertStringContainsString('/signup?company_ref=', $link);
    }

    /** @test */
    public function referral_token_is_unique()
    {
        // Create first referral
        $this->actingAs($this->companyUser, 'sanctum')
            ->postJson('/invitations/company-to-company', [
                'inviter_company_id' => $this->inviterCompany->id,
                'invitee_email' => 'company1@example.com',
            ]);

        // Create second referral
        $this->actingAs($this->companyUser, 'sanctum')
            ->postJson('/invitations/company-to-company', [
                'inviter_company_id' => $this->inviterCompany->id,
                'invitee_email' => 'company2@example.com',
            ]);

        $tokens = DB::table('company_referrals')->pluck('referral_token');
        $this->assertEquals($tokens->count(), $tokens->unique()->count());
    }

    /** @test */
    public function company_invitation_requires_valid_email()
    {
        $response = $this->actingAs($this->companyUser, 'sanctum')
            ->postJson('/invitations/company-to-company', [
                'inviter_company_id' => $this->inviterCompany->id,
                'invitee_email' => 'not-an-email',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['invitee_email']);
    }

    /** @test */
    public function company_invitation_requires_existing_inviter()
    {
        $response = $this->actingAs($this->companyUser, 'sanctum')
            ->postJson('/invitations/company-to-company', [
                'inviter_company_id' => 99999,
                'invitee_email' => 'test@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['inviter_company_id']);
    }

    /** @test */
    public function can_retrieve_pending_company_referrals()
    {
        // Create multiple referrals
        DB::table('company_referrals')->insert([
            [
                'inviter_company_id' => $this->inviterCompany->id,
                'invitee_email' => 'pending1@example.com',
                'referral_token' => 'token1',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'inviter_company_id' => $this->inviterCompany->id,
                'invitee_email' => 'accepted@example.com',
                'referral_token' => 'token2',
                'status' => 'accepted',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($this->companyUser, 'sanctum')
            ->getJson('/invitations/pending-company?company_id=' . $this->inviterCompany->id);

        $response->assertStatus(200)
            ->assertJsonCount(1); // Only pending invitation

        $invitation = $response->json()[0];
        $this->assertEquals('pending1@example.com', $invitation['invitee_email']);
    }

    /** @test */
    public function pending_company_referrals_requires_company_id()
    {
        $response = $this->actingAs($this->companyUser, 'sanctum')
            ->getJson('/invitations/pending-company');

        $response->assertStatus(422)
            ->assertJson(['message' => 'company_id required']);
    }
}

// CLAUDE-CHECKPOINT
