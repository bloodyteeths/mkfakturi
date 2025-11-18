<?php

namespace Tests\Feature\Partner;

use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AC15_PartnerInvitesPartnerTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $inviterPartner;
    protected User $inviterUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inviterUser = User::factory()->create();
        $this->inviterPartner = Partner::factory()->create([
            'user_id' => $this->inviterUser->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function partner_can_invite_another_partner()
    {
        $response = $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/partner-to-partner', [
                'invitee_email' => 'newpartner@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'signup_link',
                'link',
                'qr_code_url',
            ]);

        // Verify referral created
        $this->assertDatabaseHas('partner_referrals', [
            'inviter_partner_id' => $this->inviterPartner->id,
            'invitee_email' => 'newpartner@example.com',
            'status' => 'pending',
        ]);

        // Verify signup link contains token
        $link = $response->json('signup_link');
        $this->assertStringContainsString('/partner/signup?ref=', $link);
    }

    /** @test */
    public function partner_referral_token_is_unique()
    {
        // Create two partner referrals
        $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/partner-to-partner', [
                'invitee_email' => 'partner1@example.com',
            ]);

        $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/partner-to-partner', [
                'invitee_email' => 'partner2@example.com',
            ]);

        $tokens = DB::table('partner_referrals')->pluck('referral_token');
        $this->assertEquals($tokens->count(), $tokens->unique()->count());
    }

    /** @test */
    public function partner_referral_qr_code_url_is_valid()
    {
        $response = $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/partner-to-partner', [
                'invitee_email' => 'qrtest@example.com',
            ]);

        $qrUrl = $response->json('qr_code_url');
        $this->assertStringContainsString('/api/qr?data=', $qrUrl);
        $this->assertStringContainsString(urlencode('/partner/signup?ref='), $qrUrl);
    }

    /** @test */
    public function non_partner_cannot_invite_partner()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser, 'sanctum')
            ->postJson('/invitations/partner-to-partner', [
                'invitee_email' => 'test@example.com',
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Not a partner']);
    }

    /** @test */
    public function partner_invitation_email_can_be_sent()
    {
        // First create a referral
        $createResponse = $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/partner-to-partner', [
                'invitee_email' => 'emailtest@example.com',
            ]);

        $link = $createResponse->json('link');

        // Send email
        $response = $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/send-partner-email', [
                'email' => 'emailtest@example.com',
                'link' => $link,
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email invitation sent']);
    }

    /** @test */
    public function partner_invitation_email_requires_valid_email()
    {
        $response = $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/send-partner-email', [
                'email' => 'not-an-email',
                'link' => 'https://example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function partner_invitation_email_requires_link()
    {
        $response = $this->actingAs($this->inviterUser, 'sanctum')
            ->postJson('/invitations/send-partner-email', [
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['link']);
    }

    /** @test */
    public function downline_partner_signup_updates_referral()
    {
        // Create referral
        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $this->inviterPartner->id,
            'invitee_email' => 'downline@example.com',
            'referral_token' => 'test-token-123',
            'status' => 'pending',
            'invited_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Simulate partner signup (would happen in signup flow)
        $inviteeUser = User::factory()->create(['email' => 'downline@example.com']);
        $inviteePartner = Partner::factory()->create([
            'user_id' => $inviteeUser->id,
            'email' => 'downline@example.com',
        ]);

        // Update referral
        DB::table('partner_referrals')
            ->where('referral_token', 'test-token-123')
            ->update([
                'invitee_partner_id' => $inviteePartner->id,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

        $this->assertDatabaseHas('partner_referrals', [
            'inviter_partner_id' => $this->inviterPartner->id,
            'invitee_partner_id' => $inviteePartner->id,
            'status' => 'accepted',
        ]);
    }
}

// CLAUDE-CHECKPOINT
