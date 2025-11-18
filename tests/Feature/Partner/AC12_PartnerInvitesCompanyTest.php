<?php

namespace Tests\Feature\Partner;

use App\Models\Partner;
use App\Models\User;
use App\Models\AffiliateLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AC12_PartnerInvitesCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected Partner $partner;
    protected User $partnerUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create partner with user
        $this->partnerUser = User::factory()->create();
        $this->partner = Partner::factory()->create([
            'user_id' => $this->partnerUser->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function partner_can_generate_affiliate_link()
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/invitations/partner-to-company');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'link',
                'qr_code_url',
            ]);

        // Verify link format
        $link = $response->json('link');
        $this->assertStringContainsString('/signup?ref=', $link);

        // Verify affiliate link created in database
        $this->assertDatabaseHas('affiliate_links', [
            'partner_id' => $this->partner->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function affiliate_link_is_idempotent()
    {
        // First call creates link
        $response1 = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/invitations/partner-to-company');

        $code1 = AffiliateLink::where('partner_id', $this->partner->id)->first()->code;

        // Second call returns same link
        $response2 = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/invitations/partner-to-company');

        $code2 = AffiliateLink::where('partner_id', $this->partner->id)->first()->code;

        $this->assertEquals($code1, $code2);
        $this->assertEquals($response1->json('link'), $response2->json('link'));
    }

    /** @test */
    public function qr_code_url_is_valid()
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/invitations/partner-to-company');

        $qrUrl = $response->json('qr_code_url');
        $this->assertStringContainsString('/api/qr?data=', $qrUrl);
        $this->assertStringContainsString(urlencode('/signup?ref='), $qrUrl);
    }

    /** @test */
    public function non_partner_cannot_generate_affiliate_link()
    {
        $regularUser = User::factory()->create();

        $response = $this->actingAs($regularUser, 'sanctum')
            ->postJson('/invitations/partner-to-company');

        $response->assertStatus(403)
            ->assertJson(['message' => 'Not a partner']);
    }

    /** @test */
    public function email_invitation_can_be_sent()
    {
        $affiliateLink = AffiliateLink::factory()->create([
            'partner_id' => $this->partner->id,
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/invitations/send-email', [
                'email' => 'newcompany@example.com',
                'link' => url('/signup?ref=' . $affiliateLink->code),
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email invitation sent']);
    }

    /** @test */
    public function email_invitation_requires_valid_email()
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/invitations/send-email', [
                'email' => 'invalid-email',
                'link' => url('/signup?ref=ABC123'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function email_invitation_requires_link()
    {
        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->postJson('/invitations/send-email', [
                'email' => 'test@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['link']);
    }
}

// CLAUDE-CHECKPOINT
