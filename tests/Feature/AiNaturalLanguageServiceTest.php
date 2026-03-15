<?php

namespace Tests\Feature;

use App\Models\AiDraft;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Services\AiNaturalLanguageService;
use App\Services\AiProvider\GeminiProvider;
use App\Services\UsageLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AiNaturalLanguageServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->company = Company::factory()->create([
            'owner_id' => $this->user->id,
            'subscription_tier' => 'business',
        ]);

        // Enable the mcp_ai_tools feature flag (required for /api/v1/ai/* routes)
        config(['features.mcp_ai_tools.enabled' => true]);

        // Mock usage limit service to allow everything
        $this->app->bind(UsageLimitService::class, function () {
            $mock = \Mockery::mock(UsageLimitService::class)->makePartial();
            $mock->shouldReceive('canUse')->andReturn(true);
            $mock->shouldReceive('incrementUsage')->andReturnNull();
            $mock->shouldReceive('canUseAiFeature')->andReturn(true);

            return $mock;
        });
    }

    // ─── AiDraft Model Tests ────────────────────────────────────

    /** @test */
    public function ai_draft_model_has_correct_constants()
    {
        $this->assertEquals('pending', AiDraft::STATUS_PENDING);
        $this->assertEquals('used', AiDraft::STATUS_USED);
        $this->assertEquals('expired', AiDraft::STATUS_EXPIRED);
        $this->assertEquals('invoice', AiDraft::ENTITY_INVOICE);
        $this->assertEquals('bill', AiDraft::ENTITY_BILL);
        $this->assertEquals('expense', AiDraft::ENTITY_EXPENSE);
        $this->assertEquals('payment', AiDraft::ENTITY_PAYMENT);
    }

    /** @test */
    public function ai_draft_can_be_created_and_retrieved()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [
                'customer_name' => 'Test Customer',
                'items' => [['name' => 'Consulting', 'quantity' => 10, 'unit_price' => 300000]],
            ],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        $this->assertDatabaseHas('ai_drafts', [
            'id' => $draft->id,
            'entity_type' => 'invoice',
            'status' => 'pending',
        ]);

        $retrieved = AiDraft::find($draft->id);
        $this->assertEquals('Test Customer', $retrieved->entity_data['customer_name']);
        $this->assertCount(1, $retrieved->entity_data['items']);
    }

    /** @test */
    public function ai_draft_is_usable_when_pending_and_not_expired()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        $this->assertTrue($draft->isUsable());
    }

    /** @test */
    public function ai_draft_is_not_usable_when_expired()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_EXPENSE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertFalse($draft->isUsable());
    }

    /** @test */
    public function ai_draft_is_not_usable_when_used()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_BILL,
            'entity_data' => [],
            'status' => AiDraft::STATUS_USED,
            'expires_at' => now()->addHour(),
        ]);

        $this->assertFalse($draft->isUsable());
    }

    /** @test */
    public function ai_draft_mark_used_changes_status()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        $draft->markUsed();
        $this->assertEquals(AiDraft::STATUS_USED, $draft->fresh()->status);
    }

    /** @test */
    public function ai_draft_usable_scope_filters_correctly()
    {
        AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_USED,
            'expires_at' => now()->addHour(),
        ]);

        AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertEquals(1, AiDraft::usable()->count());
    }

    // ─── Service Tests (with mocked Gemini) ─────────────────────

    /** @test */
    public function service_creates_invoice_draft_from_command()
    {
        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => 'Марков ДООЕЛ',
                'items' => [
                    ['name' => 'Консалтинг', 'quantity' => 10, 'unit_price' => 300000],
                ],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.95,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Фактура за Марков, 10 часа консалтинг по 3000 ден', $this->company, $this->user);

        $this->assertEquals('create_invoice', $result['intent']);
        $this->assertNotEmpty($result['message']);
        $this->assertNull($result['clarification_needed']);

        // Direct creation returns redirect_url with /view, draft returns ?draft_id=
        $this->assertNotEmpty($result['redirect_url']);
        if ($result['draft_id']) {
            // Draft path
            $this->assertStringContains('/admin/invoices/create?draft_id=', $result['redirect_url']);
            $draft = AiDraft::find($result['draft_id']);
            $this->assertNotNull($draft);
            $this->assertEquals('invoice', $draft->entity_type);
            $this->assertEquals('pending', $draft->status);
        } else {
            // Direct creation path — real entity created
            $this->assertStringContains('/admin/invoices/', $result['redirect_url']);
            $this->assertStringContains('/view', $result['redirect_url']);
        }
    }

    /** @test */
    public function service_creates_bill_draft()
    {
        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_bill',
            'entities' => [
                'supplier_name' => 'Телеком',
                'items' => [
                    ['name' => 'Интернет', 'quantity' => 1, 'unit_price' => 250000],
                ],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.9,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Нова сметка од Телеком 2500 ден', $this->company, $this->user);

        $this->assertEquals('create_bill', $result['intent']);
        $this->assertNotNull($result['draft_id']);
        $this->assertStringContains('/admin/bills/create?draft_id=', $result['redirect_url']);
    }

    /** @test */
    public function service_creates_expense_draft()
    {
        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_expense',
            'entities' => [
                'amount' => 500000,
                'notes' => 'канцелариски материјали',
                'date' => '2026-03-14',
                'category' => 'office_supplies',
            ],
            'confidence' => 0.85,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Платив 5000 за канцелариски материјали', $this->company, $this->user);

        $this->assertEquals('create_expense', $result['intent']);
        $this->assertNotNull($result['draft_id']);
        $this->assertStringContains('/admin/expenses/create?draft_id=', $result['redirect_url']);
    }

    /** @test */
    public function service_returns_text_for_questions()
    {
        $this->mockGeminiResponse(json_encode([
            'intent' => 'question',
            'entities' => [],
            'confidence' => 0.9,
            'clarification_needed' => null,
            'answer' => 'Вашиот промет овој месец е 150,000 ден.',
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Колку ни е прометот овој месец?', $this->company, $this->user);

        $this->assertEquals('question', $result['intent']);
        $this->assertNull($result['draft_id']);
        $this->assertNull($result['redirect_url']);
        $this->assertStringContains('150,000', $result['message']);
    }

    /** @test */
    public function service_returns_clarification_when_needed()
    {
        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [],
            'confidence' => 0.5,
            'clarification_needed' => 'Дали мислевте на Марков ДОО или Марков ДООЕЛ?',
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Фактура за Марков', $this->company, $this->user);

        $this->assertNotNull($result['clarification_needed']);
        $this->assertNull($result['draft_id']);
        $this->assertStringContains('Марков', $result['clarification_needed']);
    }

    /** @test */
    public function service_resolves_customer_by_name()
    {
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Марков ДООЕЛ',
        ]);

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => 'Марков',
                'items' => [['name' => 'Service', 'quantity' => 1, 'unit_price' => 100000]],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.9,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Фактура за Марков', $this->company, $this->user);

        // Service may create entity directly or fall back to draft
        if ($result['draft_id']) {
            $draft = AiDraft::find($result['draft_id']);
            $this->assertEquals($customer->id, $draft->entity_data['customer_id']);
            $this->assertEquals('Марков ДООЕЛ', $draft->entity_data['customer_name']);
        } else {
            // Direct creation — check redirect URL points to view
            $this->assertNotNull($result['redirect_url']);
            $this->assertStringContains('/view', $result['redirect_url']);
        }
    }

    /** @test */
    public function service_handles_malformed_ai_response()
    {
        $this->mockGeminiResponse('This is not JSON at all');

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Фактура за Марков', $this->company, $this->user);

        $this->assertEquals('question', $result['intent']);
        $this->assertNull($result['draft_id']);
    }

    /** @test */
    public function service_handles_gemini_exception()
    {
        $mockProvider = \Mockery::mock(GeminiProvider::class);
        $mockProvider->shouldReceive('generate')->andThrow(new \Exception('API timeout'));
        $this->app->instance(GeminiProvider::class, $mockProvider);

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process('Фактура за Марков', $this->company, $this->user);

        $this->assertEquals('question', $result['intent']);
        $this->assertNull($result['draft_id']);
        // Error message is now locale-aware (Macedonian by default)
        $this->assertTrue(
            str_contains($result['message'], 'went wrong') || str_contains($result['message'], 'тргна наопаку'),
            "Error message should contain 'went wrong' or 'тргна наопаку'"
        );
    }

    /** @test */
    public function get_draft_returns_usable_draft()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => ['customer_name' => 'Test'],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        $service = app(AiNaturalLanguageService::class);
        $found = $service->getDraft($draft->id, $this->company->id);

        $this->assertNotNull($found);
        $this->assertEquals($draft->id, $found->id);
    }

    /** @test */
    public function get_draft_returns_null_for_expired()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
        ]);

        $service = app(AiNaturalLanguageService::class);
        $found = $service->getDraft($draft->id, $this->company->id);

        $this->assertNull($found);
    }

    /** @test */
    public function get_draft_returns_null_for_wrong_company()
    {
        $otherCompany = Company::factory()->create();

        $draft = AiDraft::create([
            'company_id' => $otherCompany->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        $service = app(AiNaturalLanguageService::class);
        $found = $service->getDraft($draft->id, $this->company->id);

        $this->assertNull($found);
    }

    // ─── Controller Tests ───────────────────────────────────────

    /** @test */
    public function assistant_endpoint_requires_authentication()
    {
        $response = $this->postJson('/api/v1/ai/assistant', [
            'message' => 'Фактура за Марков',
        ]);

        $this->assertIn($response->getStatusCode(), [401, 302]);
    }

    /** @test */
    public function draft_endpoint_returns_draft_data()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => ['customer_name' => 'Марков', 'items' => []],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->getJson("/api/v1/ai/drafts/{$draft->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['id', 'entity_type', 'entity_data', 'expires_at']);
        $response->assertJson([
            'entity_type' => 'invoice',
            'entity_data' => ['customer_name' => 'Марков'],
        ]);
    }

    /** @test */
    public function draft_use_endpoint_marks_draft_used()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_BILL,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->postJson("/api/v1/ai/drafts/{$draft->id}/use");

        $response->assertStatus(200);
        $this->assertEquals(AiDraft::STATUS_USED, $draft->fresh()->status);
    }

    /** @test */
    public function draft_endpoint_returns_404_for_expired()
    {
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_INVOICE,
            'entity_data' => [],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->getJson("/api/v1/ai/drafts/{$draft->id}");

        $response->assertStatus(404);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function mockGeminiResponse(string $responseBody): void
    {
        $mockProvider = \Mockery::mock(GeminiProvider::class);
        $mockProvider->shouldReceive('generate')->andReturn($responseBody);
        $this->app->instance(GeminiProvider::class, $mockProvider);
    }

    /**
     * Custom assertion: string contains substring.
     */
    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'"
        );
    }

    /**
     * Custom assertion: value is in array.
     */
    private function assertIn($value, array $array): void
    {
        $this->assertTrue(
            in_array($value, $array),
            "Failed asserting that {$value} is in [" . implode(', ', $array) . ']'
        );
    }
}
// CLAUDE-CHECKPOINT
