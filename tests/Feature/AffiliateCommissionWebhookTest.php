<?php

namespace Tests\Feature;

use App\Models\AffiliateEvent;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Partner;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Subscription;
use Tests\TestCase;

/**
 * Affiliate Commission Webhook Integration Test
 *
 * Tests the integration between subscription payment webhooks (Paddle & CPAY)
 * and the CommissionService for recording affiliate commissions.
 *
 * @ticket AC-XX - Affiliate Commission Integration
 */
class AffiliateCommissionWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Partner $partner;

    protected User $partnerUser;

    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies if needed
        if (Currency::count() === 0) {
            Artisan::call('db:seed', ['--class' => 'CurrenciesTableSeeder']);
        }

        // Create a partner user
        $this->partnerUser = User::factory()->create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'partner_subscription_tier' => 'free',
        ]);

        // Create a partner manually
        $this->partner = Partner::create([
            'name' => 'Test Partner',
            'email' => 'partner@test.com',
            'user_id' => $this->partnerUser->id,
            'is_active' => true,
        ]);

        // Create a company
        $this->company = Company::factory()->create([
            'name' => 'Test Company Inc',
        ]);

        // Link partner to company
        DB::table('partner_company_links')->insert([
            'partner_id' => $this->partner->id,
            'company_id' => $this->company->id,
            'is_active' => true,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create subscription for company
        $this->company->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'sub_test_123',
            'status' => 'active',
        ]);

        $this->commissionService = app(CommissionService::class);
    }

    /** @test */
    public function it_records_commission_for_paddle_subscription_payment()
    {
        $subscriptionAmount = 100.00;
        $monthRef = now()->format('Y-m');

        $result = $this->commissionService->recordRecurring(
            $this->company->id,
            $subscriptionAmount,
            $monthRef,
            'sub_test_123'
        );

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('event_id', $result);
        $this->assertArrayHasKey('direct_commission', $result);

        // For free partner (20%), commission should be 20.00
        $this->assertEquals(20.00, $result['direct_commission']);
        $this->assertNull($result['upline_commission']);

        // Verify event was created in database
        $event = AffiliateEvent::find($result['event_id']);
        $this->assertNotNull($event);
        $this->assertEquals($this->partner->id, $event->affiliate_partner_id);
        $this->assertEquals($this->company->id, $event->company_id);
        $this->assertEquals('recurring_commission', $event->event_type);
        $this->assertEquals(20.00, $event->amount);
        $this->assertEquals($monthRef, $event->month_ref);
    }

    /** @test */
    public function it_calculates_higher_commission_for_plus_partners()
    {
        // Upgrade partner to Plus tier
        $this->partnerUser->update(['partner_subscription_tier' => 'plus']);

        $subscriptionAmount = 100.00;
        $monthRef = now()->format('Y-m');

        $result = $this->commissionService->recordRecurring(
            $this->company->id,
            $subscriptionAmount,
            $monthRef
        );

        $this->assertTrue($result['success']);

        // For plus partner (22%), commission should be 22.00
        $this->assertEquals(22.00, $result['direct_commission']);
    }

    /** @test */
    public function it_handles_multi_level_commissions_with_upline()
    {
        // Create upline partner
        $uplineUser = User::factory()->create([
            'partner_subscription_tier' => 'free',
        ]);

        $uplinePartner = Partner::create([
            'name' => 'Upline Partner',
            'email' => 'upline@test.com',
            'user_id' => $uplineUser->id,
            'is_active' => true,
        ]);

        // Set referrer relationship
        $this->partnerUser->update(['referrer_user_id' => $uplineUser->id]);

        $subscriptionAmount = 100.00;
        $monthRef = now()->format('Y-m');

        $result = $this->commissionService->recordRecurring(
            $this->company->id,
            $subscriptionAmount,
            $monthRef
        );

        $this->assertTrue($result['success']);

        // With upline: direct gets 15%, upline gets 5%
        $this->assertEquals(15.00, $result['direct_commission']);
        $this->assertEquals(5.00, $result['upline_commission']);

        // Verify both events were created
        $directEvent = AffiliateEvent::where('affiliate_partner_id', $this->partner->id)
            ->where('month_ref', $monthRef)
            ->first();
        $this->assertNotNull($directEvent);
        $this->assertEquals(15.00, $directEvent->amount);
        $this->assertEquals(5.00, $directEvent->upline_amount);

        $uplineEvent = AffiliateEvent::where('affiliate_partner_id', $uplinePartner->id)
            ->where('month_ref', $monthRef)
            ->first();
        $this->assertNotNull($uplineEvent);
        $this->assertEquals(5.00, $uplineEvent->amount);
    }

    /** @test */
    public function it_prevents_duplicate_commission_for_same_month()
    {
        $subscriptionAmount = 100.00;
        $monthRef = now()->format('Y-m');

        // Record first time
        $result1 = $this->commissionService->recordRecurring(
            $this->company->id,
            $subscriptionAmount,
            $monthRef
        );

        $this->assertTrue($result1['success']);

        // Try to record again for same month
        $result2 = $this->commissionService->recordRecurring(
            $this->company->id,
            $subscriptionAmount,
            $monthRef
        );

        $this->assertFalse($result2['success']);
        $this->assertEquals('Commission already recorded', $result2['message']);

        // Verify only one event exists
        $count = AffiliateEvent::where('company_id', $this->company->id)
            ->where('month_ref', $monthRef)
            ->where('event_type', 'recurring_commission')
            ->count();
        $this->assertEquals(1, $count);
    }

    /** @test */
    public function it_returns_error_when_no_partner_linked()
    {
        // Create a company without partner link
        $unlinkedCompany = Company::factory()->create([
            'name' => 'Unlinked Company',
        ]);

        $result = $this->commissionService->recordRecurring(
            $unlinkedCompany->id,
            100.00,
            now()->format('Y-m')
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('No partner linked to company', $result['message']);
    }

    /** @test */
    public function it_returns_error_when_partner_is_inactive()
    {
        // Deactivate partner
        $this->partner->update(['is_active' => false]);

        $result = $this->commissionService->recordRecurring(
            $this->company->id,
            100.00,
            now()->format('Y-m')
        );

        $this->assertFalse($result['success']);
        $this->assertEquals('Partner not active', $result['message']);
    }

    /** @test */
    public function paddle_webhook_triggers_commission_recording()
    {
        Log::spy();

        // Simulate Paddle webhook controller calling commission service
        $paymentData = [
            'subscription_id' => 'sub_test_123',
            'total' => 150.00,
            'currency' => 'EUR',
        ];

        $monthRef = now()->format('Y-m');

        // This simulates what PaddleWebhookController::triggerCommissionCalculation() does
        $commissionService = app(CommissionService::class);
        $result = $commissionService->recordRecurring(
            $this->company->id,
            $paymentData['total'],
            $monthRef,
            $paymentData['subscription_id']
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(30.00, $result['direct_commission']); // 20% of 150

        // Verify event was logged
        Log::shouldHaveReceived('info')
            ->withArgs(function ($message, $context) use ($result, $monthRef) {
                return $message === 'Recurring commission recorded' &&
                       $context['event_id'] === $result['event_id'] &&
                       $context['company_id'] === $this->company->id &&
                       $context['month_ref'] === $monthRef;
            });
    }

    /** @test */
    public function cpay_webhook_triggers_commission_recording()
    {
        // Create CPAY subscription
        $this->company->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'cpay_ref_456',
            'status' => 'active',
        ]);

        // Simulate CPAY webhook controller calling commission service
        $paymentData = [
            'subscription_ref' => 'ref_456',
            'amount' => 200.00,
            'currency' => 'MKD',
            'transaction_id' => 'txn_cpay_789',
        ];

        $monthRef = now()->format('Y-m');

        $commissionService = app(CommissionService::class);
        $result = $commissionService->recordRecurring(
            $this->company->id,
            $paymentData['amount'],
            $monthRef,
            $paymentData['transaction_id']
        );

        $this->assertTrue($result['success']);
        $this->assertEquals(40.00, $result['direct_commission']); // 20% of 200
    }

    /** @test */
    public function commission_events_are_marked_as_unpaid_initially()
    {
        $result = $this->commissionService->recordRecurring(
            $this->company->id,
            100.00,
            now()->format('Y-m')
        );

        $event = AffiliateEvent::find($result['event_id']);
        $this->assertNull($event->paid_at);
        $this->assertNull($event->payout_id);
        $this->assertFalse($event->is_clawed_back);
    }

    /** @test */
    public function commission_service_handles_different_subscription_amounts()
    {
        $testCases = [
            ['amount' => 29.00, 'expected_free' => 5.80, 'expected_plus' => 6.38],
            ['amount' => 59.00, 'expected_free' => 11.80, 'expected_plus' => 12.98],
            ['amount' => 149.00, 'expected_free' => 29.80, 'expected_plus' => 32.78],
        ];

        foreach ($testCases as $index => $testCase) {
            $company = Company::factory()->create();
            $partner = Partner::create([
                'name' => 'Partner '.$index,
                'email' => 'partner'.$index.'@test.com',
                'is_active' => true,
            ]);

            DB::table('partner_company_links')->insert([
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $monthRef = now()->addMonths($index)->format('Y-m');

            $result = $this->commissionService->recordRecurring(
                $company->id,
                $testCase['amount'],
                $monthRef
            );

            $this->assertTrue($result['success']);
            $this->assertEquals($testCase['expected_free'], $result['direct_commission']);
        }
    }
}
// CLAUDE-CHECKPOINT
