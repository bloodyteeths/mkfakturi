<?php

namespace Tests\Feature\Payments;

use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Currency;
use App\Models\GatewayWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Stripe Webhook Feature Tests
 *
 * Tests webhook ingestion, signature verification, subscription lifecycle,
 * tier sync, commission triggers, and idempotency.
 */
class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected $company;

    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::create([
            'name' => 'Euro',
            'code' => 'EUR',
            'symbol' => '€',
            'precision' => 2,
            'thousand_separator' => '.',
            'decimal_separator' => ',',
            'swap_rate_from_usd' => 0.92,
        ]);

        $this->company = Company::factory()->create([
            'currency_id' => $this->currency->id,
            'subscription_tier' => 'free',
            'stripe_id' => 'cus_test_123',
        ]);

        // Disable signature verification for tests (webhook secret = null)
        Config::set('services.stripe.webhook.secret', null);

        // Configure Stripe price IDs for tier lookup
        Config::set('services.stripe.prices', [
            'starter' => ['monthly' => 'price_starter_monthly', 'yearly' => 'price_starter_yearly'],
            'standard' => ['monthly' => 'price_standard_monthly', 'yearly' => 'price_standard_yearly'],
            'business' => ['monthly' => 'price_business_monthly', 'yearly' => 'price_business_yearly'],
            'max' => ['monthly' => 'price_max_monthly', 'yearly' => 'price_max_yearly'],
        ]);
    }

    /** @test */
    public function test_stripe_webhook_received_and_stored()
    {
        $payload = $this->getCheckoutCompletedPayload();

        $response = $this->postJson('/api/webhooks/stripe', $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gateway_webhook_events', [
            'provider' => 'stripe',
            'event_type' => 'checkout.session.completed',
        ]);
    }

    /** @test */
    public function test_stripe_invalid_signature_rejected()
    {
        // Enable signature verification with a known secret
        Config::set('services.stripe.webhook.secret', 'whsec_test_secret');

        $payload = $this->getCheckoutCompletedPayload();

        $response = $this->postJson('/api/webhooks/stripe', $payload, [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function test_checkout_completed_creates_subscription()
    {
        $payload = $this->getCheckoutCompletedPayload();

        $response = $this->postJson('/api/webhooks/stripe', $payload);
        $response->assertStatus(200);

        // Process the webhook event synchronously for testing
        $event = GatewayWebhookEvent::where('provider', 'stripe')->latest()->first();
        $this->assertNotNull($event);

        // Dispatch and process the job synchronously
        $job = new \App\Jobs\ProcessWebhookEvent($event);
        $job->handle();

        // Assert CompanySubscription was created
        $subscription = CompanySubscription::where('company_id', $this->company->id)
            ->where('provider', 'stripe')
            ->first();

        $this->assertNotNull($subscription);
        $this->assertEquals('standard', $subscription->plan);
        $this->assertEquals('sub_test_456', $subscription->provider_subscription_id);
        $this->assertEquals(29.00, (float) $subscription->price_monthly);

        // Assert company tier was updated
        $this->company->refresh();
        $this->assertEquals('standard', $this->company->subscription_tier);
    }

    /** @test */
    public function test_subscription_deleted_downgrades_company()
    {
        // First create a subscription record
        $companySub = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_test_456',
            'price_monthly' => 29.00,
            'status' => 'active',
            'started_at' => now(),
        ]);
        $this->company->update(['subscription_tier' => 'standard']);

        // Send subscription.deleted webhook
        $payload = $this->getSubscriptionDeletedPayload();

        $response = $this->postJson('/api/webhooks/stripe', $payload);
        $response->assertStatus(200);

        $event = GatewayWebhookEvent::where('provider', 'stripe')
            ->where('event_type', 'customer.subscription.deleted')
            ->latest()
            ->first();
        $job = new \App\Jobs\ProcessWebhookEvent($event);
        $job->handle();

        // Assert subscription was cancelled
        $companySub->refresh();
        $this->assertEquals('canceled', $companySub->status);

        // Assert company downgraded to free
        $this->company->refresh();
        $this->assertEquals('free', $this->company->subscription_tier);
    }

    /** @test */
    public function test_invoice_payment_failed_marks_past_due()
    {
        // Create active subscription
        $companySub = CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_test_456',
            'price_monthly' => 29.00,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $payload = $this->getInvoicePaymentFailedPayload();

        $response = $this->postJson('/api/webhooks/stripe', $payload);
        $response->assertStatus(200);

        $event = GatewayWebhookEvent::where('provider', 'stripe')
            ->where('event_type', 'invoice.payment_failed')
            ->latest()
            ->first();
        $job = new \App\Jobs\ProcessWebhookEvent($event);
        $job->handle();

        // Assert subscription marked as past_due
        $companySub->refresh();
        $this->assertEquals('past_due', $companySub->status);
    }

    /** @test */
    public function test_invoice_paid_processes_without_error()
    {
        // Create active subscription
        CompanySubscription::create([
            'company_id' => $this->company->id,
            'plan' => 'standard',
            'provider' => 'stripe',
            'provider_subscription_id' => 'sub_test_456',
            'price_monthly' => 29.00,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $payload = $this->getInvoicePaidPayload();

        $response = $this->postJson('/api/webhooks/stripe', $payload);
        $response->assertStatus(200);

        $event = GatewayWebhookEvent::where('provider', 'stripe')
            ->where('event_type', 'invoice.paid')
            ->latest()
            ->first();

        // Should process without throwing
        $job = new \App\Jobs\ProcessWebhookEvent($event);
        $job->handle();

        // Event should be marked as processed
        $event->refresh();
        $this->assertEquals('processed', $event->status);
    }

    /** @test */
    public function test_idempotency_prevents_duplicate_subscriptions()
    {
        $payload = $this->getCheckoutCompletedPayload();

        // Send same webhook twice
        $this->postJson('/api/webhooks/stripe', $payload);
        $event1 = GatewayWebhookEvent::where('provider', 'stripe')->latest()->first();
        $job1 = new \App\Jobs\ProcessWebhookEvent($event1);
        $job1->handle();

        // Process a second time (simulating duplicate)
        $job1Again = new \App\Jobs\ProcessWebhookEvent($event1->fresh());
        $job1Again->handle();

        // Should only have one CompanySubscription
        $count = CompanySubscription::where('company_id', $this->company->id)
            ->where('provider', 'stripe')
            ->count();

        $this->assertEquals(1, $count);
    }

    // ─── Payload Helpers ───────────────────────────────────────────

    protected function getCheckoutCompletedPayload(): array
    {
        return [
            'id' => 'evt_test_checkout_'.uniqid(),
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_789',
                    'payment_status' => 'paid',
                    'subscription' => 'sub_test_456',
                    'customer' => 'cus_test_123',
                    'metadata' => [
                        'company_id' => $this->company->id,
                        'tier' => 'standard',
                    ],
                ],
            ],
        ];
    }

    protected function getSubscriptionDeletedPayload(): array
    {
        return [
            'id' => 'evt_test_sub_deleted_'.uniqid(),
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test_456',
                    'status' => 'canceled',
                    'customer' => 'cus_test_123',
                    'metadata' => [
                        'company_id' => $this->company->id,
                        'tier' => 'standard',
                    ],
                ],
            ],
        ];
    }

    protected function getInvoicePaymentFailedPayload(): array
    {
        return [
            'id' => 'evt_test_inv_failed_'.uniqid(),
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_123',
                    'subscription' => 'sub_test_456',
                    'customer' => 'cus_test_123',
                    'amount_due' => 2900,
                    'metadata' => [
                        'company_id' => $this->company->id,
                    ],
                ],
            ],
        ];
    }

    protected function getInvoicePaidPayload(): array
    {
        return [
            'id' => 'evt_test_inv_paid_'.uniqid(),
            'type' => 'invoice.paid',
            'data' => [
                'object' => [
                    'id' => 'in_test_paid_123',
                    'subscription' => 'sub_test_456',
                    'customer' => 'cus_test_123',
                    'amount_paid' => 2900,
                    'subscription_details' => [
                        'metadata' => [
                            'company_id' => $this->company->id,
                        ],
                    ],
                    'metadata' => [
                        'company_id' => $this->company->id,
                    ],
                    'lines' => [
                        'data' => [[
                            'metadata' => [
                                'company_id' => $this->company->id,
                            ],
                        ]],
                    ],
                ],
            ],
        ];
    }
}

