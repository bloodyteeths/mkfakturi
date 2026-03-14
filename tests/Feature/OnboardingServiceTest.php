<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\User;
use App\Services\Onboarding\BankDataAnalyzer;
use App\Services\Onboarding\OnboardingService;
use Database\Seeders\IfrsAuditSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnboardingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OnboardingService $service;
    protected BankDataAnalyzer $analyzer;
    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IfrsAuditSeeder::class);

        $this->company = Company::find(IfrsAuditSeeder::$companyId);
        $this->user = User::where('email', 'ifrs-audit@facturino.mk')->first();
        Auth::login($this->user);

        $this->service = new OnboardingService();
        $this->analyzer = new BankDataAnalyzer();
    }

    // ─── OnboardingService Tests ────────────────────────────────────

    public function test_get_progress_returns_all_steps(): void
    {
        $progress = $this->service->getProgress($this->company);

        $this->assertArrayHasKey('steps', $progress);
        $this->assertArrayHasKey('completed_count', $progress);
        $this->assertArrayHasKey('total_count', $progress);
        $this->assertArrayHasKey('all_completed', $progress);
        $this->assertArrayHasKey('dismissed', $progress);

        $this->assertCount(5, $progress['steps']);
        $this->assertEquals(5, $progress['total_count']);

        $stepKeys = array_column($progress['steps'], 'key');
        $this->assertContains('company_details', $stepKeys);
        $this->assertContains('upload_logo', $stepKeys);
        $this->assertContains('import_data', $stepKeys);
        $this->assertContains('first_invoice', $stepKeys);
        $this->assertContains('bank_account', $stepKeys);
    }

    public function test_should_show_onboarding_for_fresh_company(): void
    {
        $this->assertTrue($this->service->shouldShowOnboarding($this->company));
    }

    public function test_dismiss_hides_onboarding(): void
    {
        $this->service->dismiss($this->company);

        $this->assertFalse($this->service->shouldShowOnboarding($this->company));
        $this->assertTrue($this->service->getProgress($this->company)['dismissed']);
    }

    public function test_mark_completed_sets_timestamp(): void
    {
        $this->service->markCompleted($this->company);

        $this->assertFalse($this->service->shouldShowOnboarding($this->company));
        $this->assertNotEmpty($this->service->getProgress($this->company)['completed_at']);
    }

    public function test_save_source_stores_valid_source(): void
    {
        $this->service->saveSource($this->company, 'pantheon');

        $progress = $this->service->getProgress($this->company);
        $this->assertEquals('pantheon', $progress['source']);
    }

    public function test_save_source_rejects_invalid_source(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->saveSource($this->company, 'invalid_source');
    }

    public function test_get_migration_progress_returns_all_step_keys(): void
    {
        $progress = $this->service->getMigrationProgress($this->company);

        $this->assertArrayHasKey('customers_suppliers', $progress);
        $this->assertArrayHasKey('products_services', $progress);
        $this->assertArrayHasKey('invoices_payments', $progress);
        $this->assertArrayHasKey('chart_of_accounts', $progress);
        $this->assertArrayHasKey('journal_entries', $progress);
        $this->assertArrayHasKey('opening_balances', $progress);
        $this->assertArrayHasKey('fixed_assets', $progress);
    }

    public function test_migration_progress_detects_completed_import(): void
    {
        // Insert a completed import_job for customers
        DB::table('import_jobs')->insert([
            'company_id' => $this->company->id,
            'type' => 'customers',
            'status' => 'completed',
            'creator_id' => $this->user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $progress = $this->service->getMigrationProgress($this->company);
        $this->assertEquals('completed', $progress['customers_suppliers']);
        $this->assertEquals('not_started', $progress['products_services']);
    }

    public function test_migration_progress_detects_chart_of_accounts(): void
    {
        // The IfrsAuditSeeder seeds accounts, so chart_of_accounts should be completed
        $progress = $this->service->getMigrationProgress($this->company);
        $this->assertEquals('completed', $progress['chart_of_accounts']);
    }

    public function test_migration_progress_detects_journal_entries(): void
    {
        // The IfrsAuditSeeder seeds journal entries
        $progress = $this->service->getMigrationProgress($this->company);
        $this->assertEquals('completed', $progress['journal_entries']);
    }

    // ─── BankDataAnalyzer Tests ─────────────────────────────────────

    public function test_analyze_transactions_groups_by_counterparty(): void
    {
        $transactions = [
            ['counterparty_name' => 'ФИРМА АБВ ДООЕЛ', 'amount' => -5000, 'description' => ''],
            ['counterparty_name' => 'ФИРМА АБВ ДООЕЛ', 'amount' => -3000, 'description' => ''],
            ['counterparty_name' => 'КЛИЕНТ ГДЕ ДОО', 'amount' => 10000, 'description' => ''],
        ];

        $result = $this->analyzer->analyzeTransactions($transactions);

        $this->assertArrayHasKey('suggested_suppliers', $result);
        $this->assertArrayHasKey('suggested_customers', $result);

        // ФИРМА АБВ mostly debits → supplier
        $supplierNames = array_column($result['suggested_suppliers'], 'name');
        $this->assertContains('ФИРМА АБВ ДООЕЛ', $supplierNames);

        // КЛИЕНТ ГДЕ mostly credits → customer
        $customerNames = array_column($result['suggested_customers'], 'name');
        $this->assertContains('КЛИЕНТ ГДЕ ДОО', $customerNames);
    }

    public function test_analyze_transactions_excludes_government_entities(): void
    {
        $transactions = [
            ['counterparty_name' => 'УЈП СКОПЈЕ', 'amount' => -15000, 'description' => ''],
            ['counterparty_name' => 'ФОНД ЗА ПЕНЗИСКО ОСИГУРУВАЊЕ', 'amount' => -8000, 'description' => ''],
            ['counterparty_name' => 'НАРОДНА БАНКА', 'amount' => -500, 'description' => ''],
            ['counterparty_name' => 'ВИСТИНСКА ФИРМА', 'amount' => -7000, 'description' => ''],
        ];

        $result = $this->analyzer->analyzeTransactions($transactions);

        $allNames = array_merge(
            array_column($result['suggested_suppliers'], 'name'),
            array_column($result['suggested_customers'], 'name')
        );

        // Government entities should be excluded
        $this->assertNotContains('УЈП СКОПЈЕ', $allNames);
        $this->assertNotContains('ФОНД ЗА ПЕНЗИСКО ОСИГУРУВАЊЕ', $allNames);
        $this->assertNotContains('НАРОДНА БАНКА', $allNames);

        // Real company should be included
        $this->assertContains('ВИСТИНСКА ФИРМА', $allNames);
    }

    public function test_analyze_transactions_handles_empty_input(): void
    {
        $result = $this->analyzer->analyzeTransactions([]);

        $this->assertEmpty($result['suggested_suppliers']);
        $this->assertEmpty($result['suggested_customers']);
    }

    public function test_analyze_transactions_extracts_from_description(): void
    {
        $transactions = [
            ['counterparty_name' => '', 'amount' => 5000, 'description' => 'уплата од КОМПАНИЈА ЦДЕ за фактура 123'],
        ];

        $result = $this->analyzer->analyzeTransactions($transactions);

        $allNames = array_merge(
            array_column($result['suggested_suppliers'], 'name'),
            array_column($result['suggested_customers'], 'name')
        );

        // Should extract КОМПАНИЈА ЦДЕ from description
        $found = false;
        foreach ($allNames as $name) {
            if (str_contains($name, 'КОМПАНИЈА ЦДЕ')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Should extract counterparty name from description');
    }

    // ─── API Endpoint Tests ─────────────────────────────────────────

    public function test_progress_endpoint_returns_steps(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/onboarding/progress', [
                'company' => $this->company->id,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'steps',
                'completed_count',
                'total_count',
            ]);
    }

    public function test_dismiss_endpoint_works(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/dismiss', [], [
                'company' => $this->company->id,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_source_endpoint_validates_input(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/source', [
                'source' => 'invalid',
            ], [
                'company' => $this->company->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_source_endpoint_saves_valid_source(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/source', [
                'source' => 'zonel',
            ], [
                'company' => $this->company->id,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true, 'source' => 'zonel']);
    }

    public function test_complete_endpoint_marks_done(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/complete', [], [
                'company' => $this->company->id,
            ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse($this->service->shouldShowOnboarding($this->company));
    }

    public function test_migration_progress_endpoint(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/onboarding/migration-progress', [
                'company' => $this->company->id,
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'steps' => [
                    'customers_suppliers',
                    'products_services',
                    'invoices_payments',
                    'chart_of_accounts',
                    'journal_entries',
                ],
            ]);
    }

    public function test_confirm_entities_creates_customers_and_suppliers(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/confirm-entities', [
                'entities' => [
                    ['name' => 'Тест Клиент 1', 'type' => 'customer'],
                    ['name' => 'Тест Добавувач 1', 'type' => 'supplier'],
                ],
            ], [
                'company' => $this->company->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'created' => [
                    'customers' => 1,
                    'suppliers' => 1,
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'company_id' => $this->company->id,
            'name' => 'Тест Клиент 1',
        ]);

        $this->assertDatabaseHas('suppliers', [
            'company_id' => $this->company->id,
            'name' => 'Тест Добавувач 1',
        ]);
    }

    public function test_confirm_entities_skips_duplicates(): void
    {
        // Create existing customer
        Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Постоечки Клиент',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/confirm-entities', [
                'entities' => [
                    ['name' => 'Постоечки Клиент', 'type' => 'customer'],
                    ['name' => 'Нов Клиент', 'type' => 'customer'],
                ],
            ], [
                'company' => $this->company->id,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'created' => [
                    'customers' => 1,
                    'suppliers' => 0,
                ],
            ]);
    }
    // ─── Real Fixture File Tests ──────────────────────────────────

    public function test_journal_preview_with_real_pantheon_file(): void
    {
        $filePath = base_path('tests/fixtures/onboarding/pantheon_nalozi.txt');
        $firmsPath = base_path('tests/fixtures/onboarding/pantheon_firmi.txt');

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/journal/preview', [
                'file' => new \Illuminate\Http\UploadedFile($filePath, 'nalozi.txt', 'text/plain', null, true),
                'firms_file' => new \Illuminate\Http\UploadedFile($firmsPath, 'firmi.txt', 'text/plain', null, true),
            ], [
                'company' => $this->company->id,
            ]);

        $response->assertOk();
        $data = $response->json('data');

        // Should parse 8 nalozi
        $this->assertEquals(8, $data['summary']['total_nalozi']);
        $this->assertEquals('pantheon_txt', $data['format']);

        // All should be balanced
        $this->assertEquals(8, $data['summary']['balanced']);
        $this->assertEquals(0, $data['summary']['unbalanced']);

        // Should have recognized accounts
        $this->assertGreaterThan(0, $data['summary']['total_accounts']);

        // Should have firm names resolved
        $this->assertNotEmpty($data['firms']);
    }

    public function test_journal_import_with_real_pantheon_file(): void
    {
        // First preview to get nalozi
        $filePath = base_path('tests/fixtures/onboarding/pantheon_nalozi.txt');
        $firmsPath = base_path('tests/fixtures/onboarding/pantheon_firmi.txt');

        $previewResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/journal/preview', [
                'file' => new \Illuminate\Http\UploadedFile($filePath, 'nalozi.txt', 'text/plain', null, true),
                'firms_file' => new \Illuminate\Http\UploadedFile($firmsPath, 'firmi.txt', 'text/plain', null, true),
            ], [
                'company' => $this->company->id,
            ]);

        $previewResponse->assertOk();
        $nalozi = $previewResponse->json('data.nalozi');

        // Now import the parsed nalozi
        $importResponse = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/journal/import', [
                'nalozi' => $nalozi,
                'auto_create_accounts' => true,
            ], [
                'company' => $this->company->id,
            ]);

        $importResponse->assertOk();
        $result = $importResponse->json();

        // Debug: check full response
        $this->assertNotEmpty($result, 'Import response should not be empty: ' . json_encode($result));

        // Check imported count — may differ if duplicates exist
        $data = $result['data'] ?? $result;
        $this->assertGreaterThan(0, $data['imported'] ?? 0, 'Should import at least 1 nalog. Response: ' . json_encode($result));
    }

    public function test_bank_analysis_with_real_csv(): void
    {
        $filePath = base_path('tests/fixtures/onboarding/bank_statement_komercijalna.csv');

        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/analyze-bank', [
                'file' => new \Illuminate\Http\UploadedFile($filePath, 'izvod.csv', 'text/csv', null, true),
            ], [
                'company' => $this->company->id,
            ]);

        $response->assertOk();

        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertGreaterThan(0, $data['transaction_count']);

        // Should find customers (credits) and suppliers (debits)
        $this->assertNotEmpty($data['suggested_customers'] + $data['suggested_suppliers']);

        // Should exclude government entities
        $allNames = array_merge(
            array_column($data['suggested_suppliers'], 'name'),
            array_column($data['suggested_customers'], 'name')
        );
        foreach ($allNames as $name) {
            $this->assertStringNotContainsStringIgnoringCase('УЈП', $name);
            $this->assertStringNotContainsStringIgnoringCase('ФОНД ЗА ПЕНЗИСКО', $name);
            $this->assertStringNotContainsStringIgnoringCase('ФОНД ЗА ЗДРАВСТВЕНО', $name);
        }
    }

    public function test_full_onboarding_flow_with_real_files(): void
    {
        // Step 1: Save source
        $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/source', ['source' => 'pantheon'], ['company' => $this->company->id])
            ->assertOk();

        // Step 2: Preview journal
        $filePath = base_path('tests/fixtures/onboarding/pantheon_nalozi.txt');
        $firmsPath = base_path('tests/fixtures/onboarding/pantheon_firmi.txt');

        $preview = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/journal/preview', [
                'file' => new \Illuminate\Http\UploadedFile($filePath, 'nalozi.txt', 'text/plain', null, true),
                'firms_file' => new \Illuminate\Http\UploadedFile($firmsPath, 'firmi.txt', 'text/plain', null, true),
            ], ['company' => $this->company->id]);
        $preview->assertOk();

        // Step 3: Import journal
        $import = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/journal/import', [
                'nalozi' => $preview->json('data.nalozi'),
                'auto_create_accounts' => true,
            ], ['company' => $this->company->id]);
        $import->assertOk();
        $this->assertEquals(8, $import->json('data.imported'));

        // Step 4: Analyze bank statement
        $bankPath = base_path('tests/fixtures/onboarding/bank_statement_komercijalna.csv');
        $bankAnalysis = $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/analyze-bank', [
                'file' => new \Illuminate\Http\UploadedFile($bankPath, 'izvod.csv', 'text/csv', null, true),
            ], ['company' => $this->company->id]);
        $bankAnalysis->assertOk();

        // Step 5: Confirm entities from bank analysis
        $entities = [];
        foreach ($bankAnalysis->json('suggested_customers') as $c) {
            $entities[] = ['name' => $c['name'], 'type' => 'customer'];
        }
        foreach ($bankAnalysis->json('suggested_suppliers') as $s) {
            $entities[] = ['name' => $s['name'], 'type' => 'supplier'];
        }

        if (!empty($entities)) {
            $confirm = $this->actingAs($this->user)
                ->postJson('/api/v1/onboarding/confirm-entities', [
                    'entities' => $entities,
                ], ['company' => $this->company->id]);
            $confirm->assertOk();
        }

        // Step 6: Complete onboarding
        $this->actingAs($this->user)
            ->postJson('/api/v1/onboarding/complete', [], ['company' => $this->company->id])
            ->assertOk();

        // Verify final state
        $progress = $this->service->getProgress($this->company);
        $this->assertNotEmpty($progress['completed_at']);
        $this->assertEquals('pantheon', $progress['source']);

        // Migration progress should show journal entries completed
        $migration = $this->service->getMigrationProgress($this->company);
        $this->assertEquals('completed', $migration['journal_entries']);
    }
}
// CLAUDE-CHECKPOINT
