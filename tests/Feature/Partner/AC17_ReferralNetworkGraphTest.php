<?php

namespace Tests\Feature\Partner;

use App\Models\Partner;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AC17_ReferralNetworkGraphTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
    }

    /** @test */
    public function super_admin_can_view_network_graph()
    {
        // Create partners
        $partner1 = Partner::factory()->create(['user_id' => User::factory()->create()->id]);
        $partner2 = Partner::factory()->create(['user_id' => User::factory()->create()->id]);

        // Create companies
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();

        // Create partner-company links
        DB::table('partner_company_links')->insert([
            'partner_id' => $partner1->id,
            'company_id' => $company1->id,
            'permissions' => json_encode(['view_reports']),
            'is_active' => true,
            'invitation_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'nodes' => [
                    '*' => ['id', 'label', 'type'],
                ],
                'edges' => [
                    '*' => ['from', 'to', 'type'],
                ],
                'meta' => ['total_nodes', 'total_edges', 'page', 'limit', 'total_pages'],
            ]);
    }

    /** @test */
    public function network_graph_supports_pagination()
    {
        // Create multiple partners
        $partners = Partner::factory()->count(15)->create([
            'user_id' => function() { return User::factory()->create()->id; },
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph?page=1&limit=10');

        $response->assertStatus(200);

        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['page']);
        $this->assertEquals(10, $meta['limit']);
        $this->assertGreaterThanOrEqual(15, $meta['total_nodes']);
        $this->assertLessThanOrEqual(10, count($response->json('nodes')));
    }

    /** @test */
    public function network_graph_supports_type_filtering()
    {
        Partner::factory()->count(5)->create([
            'user_id' => function() { return User::factory()->create()->id; },
        ]);
        Company::factory()->count(3)->create();

        // Filter partners only
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph?type=partners&limit=100');

        $response->assertStatus(200);
        $nodes = $response->json('nodes');
        foreach ($nodes as $node) {
            $this->assertEquals('partner', $node['type']);
        }

        // Filter companies only
        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph?type=companies&limit=100');

        $response->assertStatus(200);
        $nodes = $response->json('nodes');
        foreach ($nodes as $node) {
            $this->assertEquals('company', $node['type']);
        }
    }

    /** @test */
    public function network_graph_includes_partner_to_partner_edges()
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
            ->getJson('/referral-network/graph?limit=100');

        $response->assertStatus(200);

        $edges = collect($response->json('edges'));
        $partnerEdge = $edges->first(function ($edge) use ($upline, $downline) {
            return $edge['from'] === "partner_{$upline->id}"
                && $edge['to'] === "partner_{$downline->id}"
                && $edge['type'] === 'referred_partner';
        });

        $this->assertNotNull($partnerEdge);
    }

    /** @test */
    public function network_graph_includes_company_to_company_edges()
    {
        $inviterCompany = Company::factory()->create();
        $inviteeCompany = Company::factory()->create();

        DB::table('company_referrals')->insert([
            'inviter_company_id' => $inviterCompany->id,
            'invitee_company_id' => $inviteeCompany->id,
            'invitee_email' => 'test@example.com',
            'referral_token' => 'token456',
            'status' => 'accepted',
            'accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph?limit=100');

        $response->assertStatus(200);

        $edges = collect($response->json('edges'));
        $companyEdge = $edges->first(function ($edge) use ($inviterCompany, $inviteeCompany) {
            return $edge['from'] === "company_{$inviterCompany->id}"
                && $edge['to'] === "company_{$inviteeCompany->id}"
                && $edge['type'] === 'referred_company';
        });

        $this->assertNotNull($companyEdge);
    }

    /** @test */
    public function network_graph_includes_partner_to_company_edges()
    {
        $partner = Partner::factory()->create(['user_id' => User::factory()->create()->id]);
        $company = Company::factory()->create();

        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $company->id,
            'permissions' => json_encode(['view_reports']),
            'is_active' => true,
            'invitation_status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph?limit=100');

        $response->assertStatus(200);

        $edges = collect($response->json('edges'));
        $managementEdge = $edges->first(function ($edge) use ($partner, $company) {
            return $edge['from'] === "partner_{$partner->id}"
                && $edge['to'] === "company_{$company->id}"
                && $edge['type'] === 'manages';
        });

        $this->assertNotNull($managementEdge);
    }

    /** @test */
    public function network_graph_filters_inactive_partners_by_default()
    {
        $activePartner = Partner::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => true,
        ]);
        $inactivePartner = Partner::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph?limit=100');

        $response->assertStatus(200);

        $nodes = collect($response->json('nodes'));
        $activeNode = $nodes->first(fn($n) => $n['id'] === "partner_{$activePartner->id}");
        $inactiveNode = $nodes->first(fn($n) => $n['id'] === "partner_{$inactivePartner->id}");

        $this->assertNotNull($activeNode);
        $this->assertNull($inactiveNode);
    }

    /** @test */
    public function network_graph_can_include_inactive_partners()
    {
        $inactivePartner = Partner::factory()->create([
            'user_id' => User::factory()->create()->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->superAdmin, 'sanctum')
            ->getJson('/referral-network/graph?include_inactive=true&limit=100');

        $response->assertStatus(200);

        $nodes = collect($response->json('nodes'));
        $inactiveNode = $nodes->first(fn($n) => $n['id'] === "partner_{$inactivePartner->id}");

        $this->assertNotNull($inactiveNode);
    }

    /** @test */
    public function regular_user_cannot_view_network_graph()
    {
        $regularUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/referral-network/graph');

        $response->assertStatus(403);
    }
}

// CLAUDE-CHECKPOINT
