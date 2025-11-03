<?php

namespace Tests\Feature\Partner;

use App\Models\Commission;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Partner Portal API Tests
 *
 * Tests for partner dashboard, commissions, and clients endpoints
 * with mocked data safety flag functionality.
 */
class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $partnerUser;
    protected Partner $partner;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed database with basic data
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        // Create a partner user
        $this->partnerUser = User::factory()->create([
            'email' => 'partner@example.com',
            'role' => 'partner',
        ]);

        // Create partner record
        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'name' => 'Test Partner',
            'email' => 'partner@example.com',
            'commission_rate' => 5.0,
            'is_active' => true,
        ]);

        // Create a test company
        $this->company = Company::factory()->create();
    }

    /** @test */
    public function test_dashboard_returns_mocked_when_flag_on()
    {
        // Enable partner portal, keep mocked data ON
        config(['features.partner_portal.enabled' => true]);
        config(['features.partner_mocked_data.enabled' => true]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'mocked' => true,
                'data' => [
                    'active_clients' => 12,
                    'monthly_commissions' => 85000,
                    'processed_invoices' => 234,
                ]
            ])
            ->assertJsonStructure([
                'mocked',
                'warning',
                'data' => [
                    'active_clients',
                    'monthly_commissions',
                    'processed_invoices',
                    'commission_rate',
                    'pending_payout',
                    'total_earned',
                ]
            ]);

        $this->assertTrue($response->json('mocked'));
    }

    /** @test */
    public function test_dashboard_returns_real_when_flag_off()
    {
        // Enable partner portal, disable mocked data
        config(['features.partner_portal.enabled' => true]);
        config(['features.partner_mocked_data.enabled' => false]);

        // Create partner-company link
        $this->company->partnerLinks()->create([
            'partner_id' => $this->partner->id,
            'is_active' => true,
        ]);

        // Create some real data
        Commission::create([
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'commission_type' => Commission::TYPE_INVOICE,
            'base_amount' => 10000,
            'commission_rate' => 5.0,
            'commission_amount' => 500,
            'currency_id' => 1, // Use default currency from seeder
            'status' => Commission::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'mocked' => false,
            ])
            ->assertJsonStructure([
                'mocked',
                'data' => [
                    'active_clients',
                    'monthly_commissions',
                    'processed_invoices',
                ]
            ]);

        $this->assertFalse($response->json('mocked'));
    }

    /** @test */
    public function test_commissions_api_respects_flag()
    {
        config(['features.partner_portal.enabled' => true]);
        config(['features.partner_mocked_data.enabled' => true]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/commissions');

        $response->assertStatus(200)
            ->assertJson([
                'mocked' => true,
            ])
            ->assertJsonStructure([
                'mocked',
                'warning',
                'data',
                'total',
                'per_page',
                'current_page',
            ]);
    }

    /** @test */
    public function test_commissions_returns_real_data_when_flag_off()
    {
        config(['features.partner_portal.enabled' => true]);
        config(['features.partner_mocked_data.enabled' => false]);

        // Create partner-company link
        $this->company->partnerLinks()->create([
            'partner_id' => $this->partner->id,
            'is_active' => true,
        ]);

        // Create real commission
        $commission = Commission::create([
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'commission_type' => Commission::TYPE_INVOICE,
            'base_amount' => 10000,
            'commission_rate' => 5.0,
            'commission_amount' => 500,
            'currency_id' => 1, // Use default currency from seeder
            'status' => Commission::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/commissions');

        $response->assertStatus(200)
            ->assertJson([
                'mocked' => false,
            ])
            ->assertJsonStructure([
                'mocked',
                'data' => [
                    '*' => [
                        'id',
                        'partner_id',
                        'company_id',
                        'commission_amount',
                        'status',
                    ]
                ]
            ]);

        // Verify real data is returned
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function test_clients_api_returns_mocked_when_flag_on()
    {
        config(['features.partner_portal.enabled' => true]);
        config(['features.partner_mocked_data.enabled' => true]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/clients');

        $response->assertStatus(200)
            ->assertJson([
                'mocked' => true,
            ])
            ->assertJsonStructure([
                'mocked',
                'warning',
                'data',
                'total',
                'per_page',
                'current_page',
            ]);
    }

    /** @test */
    public function test_clients_returns_real_data_when_flag_off()
    {
        config(['features.partner_portal.enabled' => true]);
        config(['features.partner_mocked_data.enabled' => false]);

        // Create partner-company link
        $this->company->partnerLinks()->create([
            'partner_id' => $this->partner->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/clients');

        $response->assertStatus(200)
            ->assertJson([
                'mocked' => false,
            ]);

        // Verify real data is returned
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function test_partner_authentication_required()
    {
        config(['features.partner_portal.enabled' => true]);

        $response = $this->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_feature_flag_guards_endpoints()
    {
        // Disable partner portal feature
        config(['features.partner_portal.enabled' => false]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(403)
            ->assertJsonStructure([
                'error',
                'message',
            ]);
    }

    /** @test */
    public function test_mocked_data_always_safe()
    {
        // This test verifies that even with database data, mocked flag ON returns safe data
        config(['features.partner_portal.enabled' => true]);
        config(['features.partner_mocked_data.enabled' => true]);

        // Create real data in database
        $this->company->partnerLinks()->create([
            'partner_id' => $this->partner->id,
            'is_active' => true,
        ]);

        Commission::create([
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'commission_type' => Commission::TYPE_INVOICE,
            'base_amount' => 10000,
            'commission_rate' => 5.0,
            'commission_amount' => 500,
            'currency_id' => 1, // Use default currency from seeder
            'status' => Commission::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/dashboard');

        // Should still return mocked data, not real data
        $response->assertStatus(200)
            ->assertJson([
                'mocked' => true,
                'data' => [
                    'active_clients' => 12,  // Mocked value, not 1 from database
                    'monthly_commissions' => 85000,  // Mocked value, not 500 from database
                ]
            ]);
    }

    /** @test */
    public function test_profile_endpoint_returns_partner_info()
    {
        config(['features.partner_portal.enabled' => true]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'company_name',
                    'commission_rate',
                    'is_active',
                    'created_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $this->partner->id,
                    'name' => 'Test Partner',
                    'email' => 'partner@example.com',
                    'commission_rate' => '5.00',
                    'is_active' => true,
                ]
            ]);
    }

    /** @test */
    public function test_non_partner_user_cannot_access_endpoints()
    {
        config(['features.partner_portal.enabled' => true]);

        // Create a regular user (not a partner)
        $regularUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'User is not registered as a partner'
            ]);
    }

    /** @test */
    public function test_inactive_partner_cannot_access_endpoints()
    {
        config(['features.partner_portal.enabled' => true]);

        // Deactivate partner
        $this->partner->update(['is_active' => false]);

        $response = $this->actingAs($this->partnerUser, 'sanctum')
            ->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Partner account is inactive'
            ]);
    }
}

// CLAUDE-CHECKPOINT
