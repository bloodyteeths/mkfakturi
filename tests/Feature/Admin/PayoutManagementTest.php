<?php

namespace Tests\Feature\Admin;

use App\Models\AffiliateEvent;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected Partner $partner;

    protected Currency $currency;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден',
            'precision' => 2,
            'thousand_separator' => '.',
            'decimal_separator' => ',',
            'swap_rate_from_usd' => 57.0,
        ]);

        $this->superAdmin = User::factory()->create([
            'role' => 'super admin',
            'currency_id' => $this->currency->id,
        ]);

        $this->company = Company::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $this->superAdmin->companies()->attach($this->company->id);

        $this->partner = Partner::factory()->create([
            'name' => 'Test Partner',
            'email' => 'partner@test.mk',
            'bank_name' => 'Komercijalna Banka',
            'bank_account' => 'MK07300000000012345',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function superadmin_can_list_payouts()
    {
        Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'pending',
            'payout_date' => now()->addDays(5),
            'payout_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/payouts');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [['id', 'partner_id', 'amount', 'status', 'partner_name', 'partner_bank_account']],
            'total',
        ]);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.partner_name', 'Test Partner');
        $response->assertJsonPath('data.0.partner_bank_account', 'MK07300000000012345');
    }

    /** @test */
    public function superadmin_can_filter_payouts_by_status()
    {
        Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 100.00,
            'status' => 'pending',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
        ]);

        Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 200.00,
            'status' => 'completed',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
            'payment_reference' => 'SEPA-001',
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/payouts?status=pending');

        $response->assertStatus(200);
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.status', 'pending');
    }

    /** @test */
    public function superadmin_can_view_payout_details()
    {
        $payout = Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'pending',
            'payout_date' => now()->addDays(5),
            'payout_method' => 'bank_transfer',
        ]);

        AffiliateEvent::create([
            'affiliate_partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'event_type' => 'recurring_commission',
            'amount' => 500.00,
            'month_ref' => now()->subMonth()->format('Y-m'),
            'paid_at' => now(),
            'payout_id' => $payout->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/payouts/{$payout->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $payout->id);
        $response->assertJsonPath('partner_name', 'Test Partner');
        $response->assertJsonPath('partner_bank_name', 'Komercijalna Banka');
        $response->assertJsonFragment(['event_type' => 'recurring_commission']);
    }

    /** @test */
    public function superadmin_can_get_payout_stats()
    {
        Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 300.00,
            'status' => 'pending',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
        ]);

        Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 200.00,
            'status' => 'completed',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
            'payment_reference' => 'SEPA-001',
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/payouts/stats');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals(300.0, (float) $data['total_pending_amount']);
        $this->assertEquals(1, $data['total_pending_count']);
        $this->assertEquals(200.0, (float) $data['total_completed_all_time']);
    }

    /** @test */
    public function superadmin_can_mark_payout_as_completed()
    {
        $payout = Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'pending',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/payouts/{$payout->id}/complete", [
                'payment_reference' => 'SEPA-2026-02-001',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'completed');
        $response->assertJsonPath('payment_reference', 'SEPA-2026-02-001');

        $payout->refresh();
        $this->assertEquals('completed', $payout->status);
        $this->assertEquals('SEPA-2026-02-001', $payout->payment_reference);
        $this->assertNotNull($payout->processed_at);
        $this->assertEquals($this->superAdmin->id, $payout->processed_by);
    }

    /** @test */
    public function mark_completed_requires_payment_reference()
    {
        $payout = Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'pending',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/payouts/{$payout->id}/complete", []);

        $response->assertStatus(422);
    }

    /** @test */
    public function cannot_mark_completed_payout_as_completed_again()
    {
        $payout = Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'completed',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
            'payment_reference' => 'SEPA-OLD',
            'processed_at' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/payouts/{$payout->id}/complete", [
                'payment_reference' => 'SEPA-NEW',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function superadmin_can_cancel_payout_and_events_are_released()
    {
        $payout = Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'pending',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
        ]);

        $event = AffiliateEvent::create([
            'affiliate_partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'event_type' => 'recurring_commission',
            'amount' => 500.00,
            'month_ref' => now()->subMonth()->format('Y-m'),
            'paid_at' => now(),
            'payout_id' => $payout->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/payouts/{$payout->id}/cancel", [
                'reason' => 'Wrong bank details',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'cancelled');

        // Event should be released back to unpaid
        $event->refresh();
        $this->assertNull($event->paid_at);
        $this->assertNull($event->payout_id);
    }

    /** @test */
    public function superadmin_can_mark_payout_as_failed()
    {
        $payout = Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'pending',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/payouts/{$payout->id}/fail", [
                'reason' => 'IBAN invalid',
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'failed');

        $payout->refresh();
        $this->assertEquals('failed', $payout->status);
        $this->assertEquals('IBAN invalid', $payout->details['failure_reason']);
    }

    /** @test */
    public function superadmin_can_export_csv()
    {
        Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 500.00,
            'status' => 'pending',
            'payout_date' => now()->addDays(5),
            'payout_method' => 'bank_transfer',
        ]);

        // Also create a completed one (should NOT be in export)
        Payout::create([
            'partner_id' => $this->partner->id,
            'amount' => 200.00,
            'status' => 'completed',
            'payout_date' => now(),
            'payout_method' => 'bank_transfer',
            'payment_reference' => 'SEPA-OLD',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withHeader('company', $this->company->id)
            ->get('/api/v1/payouts/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Test Partner', $content);
        $this->assertStringContainsString('MK07300000000012345', $content);
        $this->assertStringContainsString('500.00', $content);
        // Completed payout should not be in export
        $this->assertStringNotContainsString('200.00', $content);
    }

    /** @test */
    public function non_superadmin_cannot_access_payouts()
    {
        $regularUser = User::factory()->create([
            'role' => 'admin',
            'currency_id' => $this->currency->id,
        ]);
        $regularUser->companies()->attach($this->company->id);

        $response = $this->actingAs($regularUser)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/payouts');

        $response->assertStatus(403);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_payouts()
    {
        $response = $this->getJson('/api/v1/payouts');

        $response->assertStatus(401);
    }
}
// CLAUDE-CHECKPOINT
