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

/**
 * AI Natural Language Service Integration Tests with Real MK Fixture Data
 *
 * Uses realistic Macedonian natural language commands from
 * tests/fixtures/mk-documents/bank-transactions/sample-nl-commands.json
 * to verify the NL assistant correctly handles Cyrillic input,
 * intent classification, entity extraction, and customer resolution.
 *
 * Gemini is mocked but inputs are real Macedonian accounting commands.
 */
class AiNaturalLanguageFixtureTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $user;

    private array $fixtures;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->company = Company::factory()->create([
            'owner_id' => $this->user->id,
            'subscription_tier' => 'business',
        ]);

        // Load fixture data
        $fixturePath = base_path('tests/fixtures/mk-documents/bank-transactions/sample-nl-commands.json');
        $this->fixtures = json_decode(file_get_contents($fixturePath), true);

        // Enable the mcp_ai_tools feature flag
        config(['features.mcp_ai_tools.enabled' => true]);

        // Mock usage limit service
        $this->app->bind(UsageLimitService::class, function () {
            $mock = \Mockery::mock(UsageLimitService::class)->makePartial();
            $mock->shouldReceive('canUse')->andReturn(true);
            $mock->shouldReceive('incrementUsage')->andReturnNull();
            $mock->shouldReceive('canUseAiFeature')->andReturn(true);

            return $mock;
        });
    }

    // ─── Fixture Data Validation ─────────────────────────────────

    /** @test */
    public function nl_fixture_file_loads_correctly()
    {
        $this->assertCount(10, $this->fixtures);

        foreach ($this->fixtures as $i => $fixture) {
            $this->assertArrayHasKey('input', $fixture, "Fixture #{$i} missing input");
            $this->assertArrayHasKey('expected_intent', $fixture, "Fixture #{$i} missing expected_intent");
        }
    }

    /** @test */
    public function nl_fixture_covers_all_intents()
    {
        $intents = array_unique(array_column($this->fixtures, 'expected_intent'));
        sort($intents);

        $this->assertContains('create_invoice', $intents);
        $this->assertContains('create_bill', $intents);
        $this->assertContains('create_expense', $intents);
        $this->assertContains('record_payment', $intents);
        $this->assertContains('question', $intents);
    }

    /** @test */
    public function nl_fixture_has_cyrillic_and_latin_inputs()
    {
        $hasCyrillic = false;
        $hasLatin = false;

        foreach ($this->fixtures as $fixture) {
            if (preg_match('/[а-яА-ЯёЁ]/u', $fixture['input'])) {
                $hasCyrillic = true;
            }
            if (preg_match('/[a-zA-Z]/', $fixture['input'])) {
                $hasLatin = true;
            }
        }

        $this->assertTrue($hasCyrillic, 'Fixtures should contain Cyrillic inputs');
        $this->assertTrue($hasLatin, 'Fixtures should contain Latin inputs');
    }

    // ─── Invoice Intents ─────────────────────────────────────────

    /** @test */
    public function service_handles_mk_invoice_command_from_fixture()
    {
        // Fixture #0: "Фактура за Марков ДООЕЛ, 10 часа консалтинг по 3000 денари"
        $fixture = $this->getFixtureByIntent('create_invoice');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => $fixture['expected_entities']['customer_name'],
                'items' => $fixture['expected_entities']['items'],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.95,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('create_invoice', $result['intent']);
        $this->assertNotNull($result['draft_id']);
        $this->assertStringContainsString('/admin/invoices/create?draft_id=', $result['redirect_url']);

        // Verify draft stored correct entity data
        $draft = AiDraft::find($result['draft_id']);
        $this->assertEquals('invoice', $draft->entity_type);
        $this->assertNotEmpty($draft->entity_data['items']);
    }

    /** @test */
    public function service_handles_multi_item_invoice_from_fixture()
    {
        // Fixture #5: "Фактура за Петров ДОО: 5 часа развој по 5000 ден и 3 часа тестирање по 2000 ден"
        $fixture = $this->getFixtureByComment('Multi-item invoice');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => $fixture['expected_entities']['customer_name'],
                'items' => $fixture['expected_entities']['items'],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.93,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('create_invoice', $result['intent']);
        $draft = AiDraft::find($result['draft_id']);
        $this->assertCount(2, $draft->entity_data['items'], 'Multi-item invoice should have 2 items');
    }

    /** @test */
    public function service_handles_invoice_with_due_date_from_fixture()
    {
        // Fixture #7: "Фактура за Иванов ДООЕЛ за веб дизајн 45000 ден, рок до 15 април"
        $fixture = $this->getFixtureByComment('Invoice with due date');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => $fixture['expected_entities']['customer_name'],
                'items' => $fixture['expected_entities']['items'],
                'due_date' => $fixture['expected_entities']['due_date'],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.92,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $draft = AiDraft::find($result['draft_id']);
        $this->assertEquals('2026-04-15', $draft->entity_data['due_date']);
    }

    /** @test */
    public function service_handles_latin_invoice_command_from_fixture()
    {
        // Fixture #9: "Invoice for MARKOV DOOEL, 10 hours consulting at 3000 MKD"
        $fixture = $this->getFixtureByComment('Mixed Cyrillic/Latin');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => $fixture['expected_entities']['customer_name'],
                'items' => $fixture['expected_entities']['items'],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.90,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('create_invoice', $result['intent']);
        $this->assertNotNull($result['draft_id']);
    }

    // ─── Bill Intent ─────────────────────────────────────────────

    /** @test */
    public function service_handles_mk_bill_command_from_fixture()
    {
        // Fixture #1: "Нова сметка од Телеком за интернет 2500 денари"
        $fixture = $this->getFixtureByIntent('create_bill');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_bill',
            'entities' => [
                'supplier_name' => $fixture['expected_entities']['supplier_name'],
                'items' => $fixture['expected_entities']['items'],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.90,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('create_bill', $result['intent']);
        $draft = AiDraft::find($result['draft_id']);
        $this->assertEquals('bill', $draft->entity_type);
        $this->assertEquals('Телеком', $draft->entity_data['supplier_name']);
    }

    // ─── Expense Intent ──────────────────────────────────────────

    /** @test */
    public function service_handles_mk_expense_command_from_fixture()
    {
        // Fixture #2: "Платив 5000 денари за канцелариски материјали"
        $fixture = $this->getFixtureByIntent('create_expense');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_expense',
            'entities' => [
                'amount' => $fixture['expected_entities']['amount'],
                'notes' => $fixture['expected_entities']['notes'],
                'date' => '2026-03-14',
                'category' => 'office_supplies',
            ],
            'confidence' => 0.88,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('create_expense', $result['intent']);
        $draft = AiDraft::find($result['draft_id']);
        $this->assertEquals('expense', $draft->entity_type);
        $this->assertEquals(500000, $draft->entity_data['amount']);
    }

    /** @test */
    public function service_handles_rent_expense_from_fixture()
    {
        // Fixture #8: "Трошок за кирија 30000 денари за месец март"
        $fixture = $this->getFixtureByComment('Rent expense');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_expense',
            'entities' => [
                'amount' => $fixture['expected_entities']['amount'],
                'category' => $fixture['expected_entities']['category'],
                'notes' => 'Кирија за месец март',
                'date' => '2026-03-14',
            ],
            'confidence' => 0.91,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('create_expense', $result['intent']);
        $draft = AiDraft::find($result['draft_id']);
        $this->assertEquals(3000000, $draft->entity_data['amount']);
    }

    // ─── Payment Intent ──────────────────────────────────────────

    /** @test */
    public function service_handles_mk_payment_command_from_fixture()
    {
        // Fixture #3: "Примив 15000 од Петров ДОО за фактура INV-2026-001"
        $fixture = $this->getFixtureByIntent('record_payment');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'record_payment',
            'entities' => [
                'customer_name' => $fixture['expected_entities']['customer_name'],
                'amount' => $fixture['expected_entities']['amount'],
                'invoice_reference' => $fixture['expected_entities']['invoice_reference'],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.92,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('record_payment', $result['intent']);
        $draft = AiDraft::find($result['draft_id']);
        $this->assertEquals('payment', $draft->entity_type);
        $this->assertEquals('INV-2026-001', $draft->entity_data['invoice_reference']);
    }

    // ─── Question Intent ─────────────────────────────────────────

    /** @test */
    public function service_handles_question_from_fixture()
    {
        // Fixture #4: "Колку ни е прометот овој месец?"
        $fixture = $this->getFixtureByIntent('question');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'question',
            'entities' => [],
            'confidence' => 0.90,
            'clarification_needed' => null,
            'answer' => 'Вашиот промет за овој месец изнесува приближно 245,000 ден.',
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertEquals('question', $result['intent']);
        $this->assertNull($result['draft_id']);
        $this->assertNull($result['redirect_url']);
        $this->assertStringContainsString('245,000', $result['message']);
    }

    // ─── Customer Resolution with Fixtures ───────────────────────

    /** @test */
    public function service_resolves_cyrillic_customer_from_fixture_input()
    {
        // Create a customer that should be found by fuzzy match
        $customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Марков ДООЕЛ',
        ]);

        $fixture = $this->getFixtureByIntent('create_invoice');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => 'Марков',
                'items' => [['name' => 'консалтинг', 'quantity' => 10, 'unit_price' => 300000]],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.95,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        // Service may create entity directly or fall back to draft
        if ($result['draft_id']) {
            $draft = AiDraft::find($result['draft_id']);
            $this->assertEquals($customer->id, $draft->entity_data['customer_id']);
            $this->assertEquals('Марков ДООЕЛ', $draft->entity_data['customer_name']);
        } else {
            // Direct creation — check redirect URL points to view
            $this->assertNotNull($result['redirect_url']);
            $this->assertStringContainsString('/view', $result['redirect_url']);
        }
    }

    /** @test */
    public function service_resolves_supplier_by_name_from_fixture()
    {
        // Create a supplier
        DB::table('suppliers')->insert([
            'company_id' => $this->company->id,
            'name' => 'Македонски Телеком АД',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fixture = $this->getFixtureByIntent('create_bill');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_bill',
            'entities' => [
                'supplier_name' => 'Телеком',
                'items' => [['name' => 'интернет', 'quantity' => 1, 'unit_price' => 250000]],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.90,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        // Service may create bill directly or fall back to draft
        if ($result['draft_id']) {
            $draft = AiDraft::find($result['draft_id']);
            $this->assertNotNull($draft->entity_data['supplier_id']);
            $this->assertEquals('Македонски Телеком АД', $draft->entity_data['supplier_name']);
        } else {
            $this->assertNotNull($result['redirect_url']);
            $this->assertStringContainsString('/view', $result['redirect_url']);
        }
    }

    /** @test */
    public function service_handles_ambiguous_customer_with_clarification()
    {
        // Fixture #6: "Фактура за Марков" — ambiguous
        $fixture = $this->getFixtureByComment('Ambiguous');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [],
            'confidence' => 0.5,
            'clarification_needed' => 'Дали мислевте на Марков ДОО или Марков ДООЕЛ?',
            'answer' => null,
        ]));

        $service = app(AiNaturalLanguageService::class);
        $result = $service->process($fixture['input'], $this->company, $this->user);

        $this->assertNotNull($result['clarification_needed']);
        $this->assertNull($result['draft_id']);
        $this->assertStringContainsString('Марков', $result['clarification_needed']);
    }

    // ─── API Endpoint Tests with Fixture Data ────────────────────

    /** @test */
    public function assistant_api_creates_draft_from_mk_input()
    {
        $fixture = $this->getFixtureByIntent('create_invoice');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'create_invoice',
            'entities' => [
                'customer_name' => 'Марков ДООЕЛ',
                'items' => [['name' => 'консалтинг', 'quantity' => 10, 'unit_price' => 300000]],
                'date' => '2026-03-14',
            ],
            'confidence' => 0.95,
            'clarification_needed' => null,
            'answer' => null,
        ]));

        $response = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->postJson('/api/v1/ai/assistant', [
                'message' => $fixture['input'],
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['intent', 'draft_id', 'redirect_url', 'message']);
        $response->assertJson(['intent' => 'create_invoice']);
        $this->assertNotNull($response->json('draft_id'));
    }

    /** @test */
    public function assistant_api_returns_question_response()
    {
        $fixture = $this->getFixtureByIntent('question');

        $this->mockGeminiResponse(json_encode([
            'intent' => 'question',
            'entities' => [],
            'confidence' => 0.90,
            'clarification_needed' => null,
            'answer' => 'Прометот е 245,000 ден.',
        ]));

        $response = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->postJson('/api/v1/ai/assistant', [
                'message' => $fixture['input'],
            ]);

        $response->assertStatus(200);
        $response->assertJson(['intent' => 'question']);
        $this->assertNull($response->json('draft_id'));
    }

    // ─── Draft Pre-fill Flow ─────────────────────────────────────

    /** @test */
    public function draft_from_fixture_can_be_retrieved_and_used()
    {
        $fixture = $this->getFixtureByIntent('create_bill');

        // Create draft manually (simulating service output)
        $draft = AiDraft::create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
            'entity_type' => AiDraft::ENTITY_BILL,
            'entity_data' => [
                'supplier_name' => 'Телеком',
                'items' => [['name' => 'интернет', 'quantity' => 1, 'unit_price' => 250000]],
                'date' => '2026-03-14',
            ],
            'status' => AiDraft::STATUS_PENDING,
            'expires_at' => now()->addHour(),
        ]);

        // GET draft
        $response = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->getJson("/api/v1/ai/drafts/{$draft->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'entity_type' => 'bill',
            'entity_data' => [
                'supplier_name' => 'Телеком',
            ],
        ]);

        // Use draft
        $useResponse = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->postJson("/api/v1/ai/drafts/{$draft->id}/use");

        $useResponse->assertStatus(200);
        $this->assertEquals(AiDraft::STATUS_USED, $draft->fresh()->status);

        // Can't use again (already used → 404)
        $secondUse = $this->actingAs($this->user)
            ->withHeader('company', (string) $this->company->id)
            ->postJson("/api/v1/ai/drafts/{$draft->id}/use");

        $secondUse->assertStatus(404);
    }

    // ─── Helpers ─────────────────────────────────────────────────

    private function getFixtureByIntent(string $intent): array
    {
        foreach ($this->fixtures as $fixture) {
            if ($fixture['expected_intent'] === $intent) {
                return $fixture;
            }
        }
        $this->fail("No fixture found with expected_intent '{$intent}'");
    }

    private function getFixtureByComment(string $partialComment): array
    {
        foreach ($this->fixtures as $fixture) {
            if (str_contains($fixture['_comment'] ?? '', $partialComment)) {
                return $fixture;
            }
        }
        $this->fail("No fixture found with comment containing '{$partialComment}'");
    }

    private function mockGeminiResponse(string $responseBody): void
    {
        $mockProvider = \Mockery::mock(GeminiProvider::class);
        $mockProvider->shouldReceive('generate')->andReturn($responseBody);
        $this->app->instance(GeminiProvider::class, $mockProvider);
    }
}
// CLAUDE-CHECKPOINT
