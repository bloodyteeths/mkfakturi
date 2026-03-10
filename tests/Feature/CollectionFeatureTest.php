<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Models\ReminderHistory;
use Modules\Mk\Models\ReminderTemplate;
use Modules\Mk\Services\CollectionService;
use Tests\TestCase;

class CollectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Customer $customer;

    protected Currency $currency;

    protected CollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::firstOrCreate(
            ['code' => 'MKD'],
            ['name' => 'Macedonian Denar', 'symbol' => 'ден.', 'precision' => 2, 'thousand_separator' => '.', 'decimal_separator' => ',', 'swap_currency_symbol' => false]
        );

        $this->company = Company::factory()->create([
            'name' => 'Test Company',
        ]);

        $this->user = User::factory()->create([
            'role' => 'super admin',
        ]);

        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'currency_id' => $this->currency->id,
        ]);

        $this->service = app(CollectionService::class);
    }

    protected function createOverdueInvoice(int $daysOverdue, int $amountCents = 100000): Invoice
    {
        return Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'status' => Invoice::STATUS_SENT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'due_date' => Carbon::today()->subDays($daysOverdue)->format('Y-m-d'),
            'invoice_date' => Carbon::today()->subDays($daysOverdue + 30)->format('Y-m-d'),
            'total' => $amountCents,
            'due_amount' => $amountCents,
            'tax' => 0,
            'sub_total' => $amountCents,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'invoice_number' => 'INV-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'sequence_number' => mt_rand(1, 9999),
            'unique_hash' => uniqid(),
        ]);
    }

    // ---- Overdue Invoice Tests ----

    public function test_get_overdue_invoices_returns_correct_structure(): void
    {
        $this->createOverdueInvoice(5);
        $this->createOverdueInvoice(15);
        $this->createOverdueInvoice(45);
        $this->createOverdueInvoice(100);

        $result = $this->service->getOverdueInvoices($this->company->id);

        $this->assertArrayHasKey('invoices', $result);
        $this->assertArrayHasKey('aging', $result);
        $this->assertArrayHasKey('interest_rate', $result);
        $this->assertArrayHasKey('total_interest', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(4, $result['invoices']);
    }

    public function test_escalation_levels_assigned_correctly(): void
    {
        $friendly = $this->createOverdueInvoice(3);
        $firm = $this->createOverdueInvoice(15);
        $final = $this->createOverdueInvoice(45);
        $legal = $this->createOverdueInvoice(100);

        $result = $this->service->getOverdueInvoices($this->company->id);
        $invoices = collect($result['invoices'])->keyBy('id');

        $this->assertEquals('friendly', $invoices[$friendly->id]['escalation_level']);
        $this->assertEquals('firm', $invoices[$firm->id]['escalation_level']);
        $this->assertEquals('final', $invoices[$final->id]['escalation_level']);
        $this->assertEquals('legal', $invoices[$legal->id]['escalation_level']);
    }

    public function test_aging_buckets_calculated_correctly(): void
    {
        $this->createOverdueInvoice(5, 10000);   // 0-30
        $this->createOverdueInvoice(20, 20000);   // 0-30
        $this->createOverdueInvoice(45, 30000);   // 31-60
        $this->createOverdueInvoice(75, 40000);   // 61-90
        $this->createOverdueInvoice(120, 50000);  // 90+

        $result = $this->service->getOverdueInvoices($this->company->id);

        $this->assertEquals(30000, $result['aging']['0_30']);   // 10000 + 20000
        $this->assertEquals(30000, $result['aging']['31_60']);
        $this->assertEquals(40000, $result['aging']['61_90']);
        $this->assertEquals(50000, $result['aging']['90_plus']);
    }

    public function test_interest_calculation_uses_default_rate(): void
    {
        $invoice = $this->createOverdueInvoice(365, 10000000); // 100k MKD, 1 year

        $result = $this->service->getOverdueInvoices($this->company->id);
        $inv = $result['invoices'][0];

        // Expected: 10,000,000 * (13.25/100/365) * 365 = 1,325,000
        $this->assertEquals(13.25, $result['interest_rate']);
        $this->assertEquals(1325000, $inv['interest']);
        $this->assertEquals(10000000 + 1325000, $inv['total_with_interest']);
    }

    public function test_interest_calculation_uses_custom_rate(): void
    {
        CompanySetting::setSettings([
            'interest_annual_rate' => '10.0',
        ], $this->company->id);

        $invoice = $this->createOverdueInvoice(365, 10000000); // 100k MKD, 1 year

        $result = $this->service->getOverdueInvoices($this->company->id);

        $this->assertEquals(10.0, $result['interest_rate']);
        // 10,000,000 * 10/100/365 * 365 = 1,000,000
        $this->assertEquals(1000000, $result['invoices'][0]['interest']);
    }

    public function test_escalation_level_filter(): void
    {
        $this->createOverdueInvoice(3);    // friendly
        $this->createOverdueInvoice(15);   // firm
        $this->createOverdueInvoice(100);  // legal

        $result = $this->service->getOverdueInvoices($this->company->id, ['escalation_level' => 'firm']);
        $this->assertCount(1, $result['invoices']);
        $this->assertEquals('firm', $result['invoices'][0]['escalation_level']);
    }

    public function test_search_filter_by_invoice_number(): void
    {
        $target = $this->createOverdueInvoice(10);
        $this->createOverdueInvoice(20);

        $result = $this->service->getOverdueInvoices($this->company->id, ['search' => $target->invoice_number]);
        $this->assertCount(1, $result['invoices']);
        $this->assertEquals($target->id, $result['invoices'][0]['id']);
    }

    public function test_search_filter_by_customer_name(): void
    {
        $otherCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Unique Company LLC',
            'email' => 'other@test.com',
            'currency_id' => $this->currency->id,
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $otherCustomer->id,
            'currency_id' => $this->currency->id,
            'status' => Invoice::STATUS_SENT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'due_date' => Carbon::today()->subDays(10)->format('Y-m-d'),
            'invoice_date' => Carbon::today()->subDays(40)->format('Y-m-d'),
            'total' => 50000,
            'due_amount' => 50000,
            'tax' => 0,
            'sub_total' => 50000,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'invoice_number' => 'INV-SEARCH',
            'sequence_number' => mt_rand(1, 9999),
            'unique_hash' => uniqid(),
        ]);

        $this->createOverdueInvoice(10);

        $result = $this->service->getOverdueInvoices($this->company->id, ['search' => 'Unique Company']);
        $this->assertCount(1, $result['invoices']);
        $this->assertEquals('Unique Company LLC', $result['invoices'][0]['customer_name']);
    }

    public function test_pagination_works(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->createOverdueInvoice($i + 1);
        }

        $page1 = $this->service->getOverdueInvoices($this->company->id, ['page' => 1, 'per_page' => 10]);
        $this->assertCount(10, $page1['invoices']);
        $this->assertEquals(15, $page1['pagination']['total']);
        $this->assertEquals(1, $page1['pagination']['page']);
        $this->assertEquals(2, $page1['pagination']['last_page']);

        $page2 = $this->service->getOverdueInvoices($this->company->id, ['page' => 2, 'per_page' => 10]);
        $this->assertCount(5, $page2['invoices']);
        $this->assertEquals(2, $page2['pagination']['page']);
    }

    public function test_can_send_flag_after_cooldown(): void
    {
        $invoice = $this->createOverdueInvoice(10);

        // Before any reminders, can_send should be true
        $result = $this->service->getOverdueInvoices($this->company->id);
        $this->assertTrue($result['invoices'][0]['can_send']);

        // Create a recent reminder
        ReminderHistory::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'friendly',
            'sent_at' => now()->subHours(2),
            'sent_via' => 'email',
            'amount_due' => $invoice->due_amount,
        ]);

        $result = $this->service->getOverdueInvoices($this->company->id);
        $this->assertFalse($result['invoices'][0]['can_send']);

        // Create an old reminder (>24h)
        ReminderHistory::query()->delete();
        ReminderHistory::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'friendly',
            'sent_at' => now()->subHours(25),
            'sent_via' => 'email',
            'amount_due' => $invoice->due_amount,
        ]);

        $result = $this->service->getOverdueInvoices($this->company->id);
        $this->assertTrue($result['invoices'][0]['can_send']);
    }

    public function test_draft_invoices_excluded(): void
    {
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'status' => Invoice::STATUS_DRAFT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'due_date' => Carbon::today()->subDays(10)->format('Y-m-d'),
            'invoice_date' => Carbon::today()->subDays(40)->format('Y-m-d'),
            'total' => 50000,
            'due_amount' => 50000,
            'tax' => 0,
            'sub_total' => 50000,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'invoice_number' => 'DRAFT-001',
            'sequence_number' => mt_rand(1, 9999),
            'unique_hash' => uniqid(),
        ]);

        $result = $this->service->getOverdueInvoices($this->company->id);
        $this->assertCount(0, $result['invoices']);
    }

    // ---- Send Reminder Tests ----

    public function test_payment_reminder_email_renders_without_error(): void
    {
        $invoice = $this->createOverdueInvoice(15);
        $invoice->loadMissing('customer', 'company', 'currency');

        $template = ReminderTemplate::create([
            'company_id' => $this->company->id,
            'escalation_level' => 'friendly',
            'days_after_due' => 7,
            'subject_mk' => 'Потсетник за {INVOICE_NUMBER}',
            'body_mk' => '<p>Почитувани {CUSTOMER_NAME}, фактурата {INVOICE_NUMBER} од {AMOUNT_DUE} е задоцнета.</p>',
            'subject_en' => 'Reminder for {INVOICE_NUMBER}',
            'body_en' => '<p>Dear {CUSTOMER_NAME}, invoice {INVOICE_NUMBER} of {AMOUNT_DUE} is overdue.</p>',
            'subject_tr' => 'Hatirlatma {INVOICE_NUMBER}',
            'body_tr' => '<p>Hatirlatma</p>',
            'subject_sq' => 'Kujtese {INVOICE_NUMBER}',
            'body_sq' => '<p>Kujtese</p>',
            'is_active' => true,
        ]);

        $mailable = new \App\Mail\PaymentReminder($invoice, $this->customer, $template, 'friendly', 'mk');

        // This should render without "No hint path defined for [mail]" error
        $html = $mailable->render();

        $this->assertStringContainsString($invoice->invoice_number, $html);
        $this->assertStringContainsString('Facturino', $html);
        $this->assertStringNotContainsString('mail::', $html);
    }

    public function test_send_reminder_creates_history_record(): void
    {
        Mail::fake();
        $invoice = $this->createOverdueInvoice(10);

        $result = $this->service->sendReminder($this->company->id, $invoice->id, 'friendly');

        $this->assertTrue($result['success']);
        $this->assertEquals('customer@test.com', $result['sent_to']);
        $this->assertEquals('friendly', $result['level']);

        $this->assertDatabaseHas('reminder_history', [
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'friendly',
            'sent_via' => 'email',
        ]);
    }

    public function test_send_reminder_rate_limits_at_24h(): void
    {
        Mail::fake();
        $invoice = $this->createOverdueInvoice(10);

        // First send should work
        $this->service->sendReminder($this->company->id, $invoice->id, 'friendly');

        // Second send within 24h should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('already sent');
        $this->service->sendReminder($this->company->id, $invoice->id, 'firm');
    }

    public function test_send_reminder_fails_without_customer_email(): void
    {
        Mail::fake();
        $noEmailCustomer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'No Email Customer',
            'email' => null,
            'currency_id' => $this->currency->id,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $noEmailCustomer->id,
            'currency_id' => $this->currency->id,
            'status' => Invoice::STATUS_SENT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'due_date' => Carbon::today()->subDays(10)->format('Y-m-d'),
            'invoice_date' => Carbon::today()->subDays(40)->format('Y-m-d'),
            'total' => 50000,
            'due_amount' => 50000,
            'tax' => 0,
            'sub_total' => 50000,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'invoice_number' => 'INV-NOEMAIL',
            'sequence_number' => mt_rand(1, 9999),
            'unique_hash' => uniqid(),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('no email');
        $this->service->sendReminder($this->company->id, $invoice->id, 'friendly');
    }

    public function test_send_reminder_fails_for_nonexistent_invoice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');
        $this->service->sendReminder($this->company->id, 999999, 'friendly');
    }

    // ---- Template Tests ----

    public function test_get_templates_seeds_defaults(): void
    {
        $templates = $this->service->getTemplates($this->company->id);

        $this->assertCount(4, $templates);
        $levels = array_column($templates, 'escalation_level');
        $this->assertContains('friendly', $levels);
        $this->assertContains('firm', $levels);
        $this->assertContains('final', $levels);
        $this->assertContains('legal', $levels);
    }

    public function test_seed_defaults_does_not_duplicate(): void
    {
        $this->service->seedDefaults($this->company->id);
        $this->service->seedDefaults($this->company->id);

        $count = ReminderTemplate::forCompany($this->company->id)->count();
        $this->assertEquals(4, $count);
    }

    public function test_template_locale_fallback(): void
    {
        $this->service->seedDefaults($this->company->id);
        $template = ReminderTemplate::forCompany($this->company->id)
            ->where('escalation_level', 'friendly')
            ->first();

        // MK subject should exist
        $mkSubject = $template->getSubjectForLocale('mk');
        $this->assertNotEmpty($mkSubject);
        $this->assertStringContainsString('{INVOICE_NUMBER}', $mkSubject);

        // EN subject should exist
        $enSubject = $template->getSubjectForLocale('en');
        $this->assertNotEmpty($enSubject);
    }

    // ---- History Tests ----

    public function test_get_history_returns_paginated_results(): void
    {
        $invoice = $this->createOverdueInvoice(10);

        for ($i = 0; $i < 5; $i++) {
            ReminderHistory::create([
                'company_id' => $this->company->id,
                'invoice_id' => $invoice->id,
                'customer_id' => $this->customer->id,
                'escalation_level' => 'friendly',
                'sent_at' => now()->subDays($i),
                'sent_via' => 'email',
                'amount_due' => $invoice->due_amount,
            ]);
        }

        $result = $this->service->getHistory($this->company->id);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(5, $result['items']);
        $this->assertEquals(5, $result['pagination']['total']);
    }

    public function test_get_history_date_range_filter(): void
    {
        $invoice = $this->createOverdueInvoice(10);

        // Create 3 entries: 1 old, 2 recent
        ReminderHistory::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'friendly',
            'sent_at' => now()->subDays(30),
            'sent_via' => 'email',
            'amount_due' => $invoice->due_amount,
        ]);
        ReminderHistory::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'firm',
            'sent_at' => now()->subDays(2),
            'sent_via' => 'email',
            'amount_due' => $invoice->due_amount,
        ]);
        ReminderHistory::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'final',
            'sent_at' => now(),
            'sent_via' => 'email',
            'amount_due' => $invoice->due_amount,
        ]);

        // Filter to last 7 days
        $result = $this->service->getHistory($this->company->id, [
            'from_date' => now()->subDays(7)->format('Y-m-d'),
            'to_date' => now()->format('Y-m-d'),
        ]);

        $this->assertCount(2, $result['items']);
    }

    // ---- Effectiveness Tests ----

    public function test_effectiveness_returns_per_level_stats(): void
    {
        $invoice = $this->createOverdueInvoice(10);

        // 2 friendly sent, 1 paid
        ReminderHistory::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'friendly',
            'sent_at' => now()->subDays(10),
            'sent_via' => 'email',
            'amount_due' => 50000,
            'paid_at' => now()->subDays(5),
        ]);
        ReminderHistory::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'escalation_level' => 'friendly',
            'sent_at' => now()->subDays(3),
            'sent_via' => 'email',
            'amount_due' => 60000,
        ]);

        $result = $this->service->getEffectiveness($this->company->id);

        $this->assertArrayHasKey('by_level', $result);
        $this->assertArrayHasKey('overview', $result);

        $friendly = $result['by_level']['friendly'];
        $this->assertEquals(2, $friendly['total_sent']);
        $this->assertEquals(1, $friendly['total_paid']);
        $this->assertEquals(50.0, $friendly['paid_percentage']);
    }

    // ---- Opomena Tests ----

    public function test_opomena_data_generation(): void
    {
        $invoice = $this->createOverdueInvoice(30, 500000);

        $data = $this->service->getOpomenaData($this->company->id, $invoice->id);

        $this->assertArrayHasKey('invoice', $data);
        $this->assertArrayHasKey('company', $data);
        $this->assertArrayHasKey('customer', $data);
        $this->assertArrayHasKey('currency_symbol', $data);
        $this->assertArrayHasKey('due_date', $data);
        $this->assertArrayHasKey('days_overdue', $data);
        $this->assertArrayHasKey('due_amount', $data);
        $this->assertArrayHasKey('interest_rate', $data);
        $this->assertArrayHasKey('interest_amount', $data);
        $this->assertArrayHasKey('total_with_interest', $data);
        $this->assertArrayHasKey('today', $data);
        $this->assertArrayHasKey('reminder_count', $data);

        $this->assertEquals(30, $data['days_overdue']);
        $this->assertEquals(500000, $data['due_amount']);
        $this->assertEquals(13.25, $data['interest_rate']);

        // Interest: 500000 * (13.25/100/365) * 30
        $expectedInterest = (int) round(500000 * (13.25 / 100 / 365) * 30);
        $this->assertEquals($expectedInterest, $data['interest_amount']);
        $this->assertEquals(500000 + $expectedInterest, $data['total_with_interest']);
    }

    public function test_opomena_fails_for_nonexistent_invoice(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getOpomenaData($this->company->id, 999999);
    }

    // ---- API Endpoint Tests ----

    public function test_api_overdue_returns_correct_json(): void
    {
        $this->createOverdueInvoice(10);

        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->getJson('/api/v1/collections/overdue');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'summary' => [
                    'total_overdue_amount',
                    'invoice_count',
                    'customer_count',
                    'avg_days_overdue',
                    'total_interest',
                    'interest_rate',
                ],
                'aging' => ['0_30', '31_60', '61_90', '90_plus'],
                'pagination' => ['total', 'page', 'per_page', 'last_page'],
            ]);
    }

    public function test_api_history_returns_correct_json(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->getJson('/api/v1/collections/history');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['total', 'page', 'per_page', 'last_page'],
            ]);
    }

    public function test_api_effectiveness_returns_correct_json(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->getJson('/api/v1/collections/effectiveness');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'by_level' => [
                        'friendly' => ['total_sent', 'total_paid', 'paid_percentage', 'avg_days_to_pay'],
                        'firm',
                        'final',
                        'legal',
                    ],
                    'overview',
                ],
            ]);
    }

    public function test_api_templates_returns_correct_json(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->getJson('/api/v1/collections/templates');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_api_send_reminder_validates_input(): void
    {
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->postJson('/api/v1/collections/send-reminder', []);

        $response->assertUnprocessable();
    }

    public function test_api_template_crud(): void
    {
        // Create
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->postJson('/api/v1/collections/templates', [
                'escalation_level' => 'friendly',
                'days_after_due' => 5,
                'subject_mk' => 'Тест',
                'subject_en' => 'Test',
                'subject_tr' => 'Test',
                'subject_sq' => 'Test',
                'body_mk' => '<p>Тест</p>',
                'body_en' => '<p>Test</p>',
                'body_tr' => '<p>Test</p>',
                'body_sq' => '<p>Test</p>',
                'is_active' => true,
            ]);

        $response->assertCreated();
        $templateId = $response->json('data.id');
        $this->assertNotNull($templateId);

        // Update
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->putJson("/api/v1/collections/templates/{$templateId}", [
                'days_after_due' => 10,
            ]);

        $response->assertOk();
        $this->assertEquals(10, $response->json('data.days_after_due'));

        // Delete
        $response = $this->actingAs($this->user)
            ->withHeaders(['company' => (string) $this->company->id])
            ->deleteJson("/api/v1/collections/templates/{$templateId}");

        $response->assertOk();
        $this->assertDatabaseMissing('reminder_templates', ['id' => $templateId]);
    }
}

// CLAUDE-CHECKPOINT
