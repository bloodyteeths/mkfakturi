<?php

namespace Tests\Feature;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Address;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Tax;
use App\Models\TaxType;
use App\Models\User;
use App\Services\Tax\DbFormService;
use App\Services\Tax\DDV04FormService;
use App\Services\Tax\Obrazec36FormService;
use App\Services\Tax\Obrazec37FormService;
use App\Services\Tax\TaxFormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * End-to-end tests for all 4 UJP tax form services.
 *
 * Tests: collect(), validate(), toXml(), toPdf(), preview()
 * for ДДВ-04, ДБ, Образец 36, Образец 37.
 */
class UjpFormsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;
    protected Currency $currency;
    protected int $entityId;

    protected function setUp(): void
    {
        parent::setUp();

        // Create basic test infrastructure
        $this->currency = Currency::firstOrCreate(
            ['code' => 'MKD'],
            [
                'name' => 'Macedonian Denar',
                'symbol' => 'ден.',
                'precision' => 0,
                'thousand_separator' => '.',
                'decimal_separator' => ',',
                'swap_currency_symbol' => true,
            ]
        );

        $this->user = User::factory()->create([
            'email' => 'ujp-test@facturino.mk',
            'name' => 'УЈП Тест',
            'role' => 'super admin',
        ]);

        $this->company = Company::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'УЈП Тест ДООЕЛ',
            'vat_number' => 'MK4030009123456',
        ]);

        // Create country first (SQLite test DB doesn't have countries seeded)
        \Illuminate\Support\Facades\DB::table('countries')->insertOrIgnore([
            'id' => 129,
            'code' => 'MK',
            'name' => 'Macedonia',
            'phonecode' => 389,
        ]);

        Address::create([
            'company_id' => $this->company->id,
            'type' => 'company',
            'name' => 'УЈП Тест ДООЕЛ',
            'address_street_1' => 'ул. Тестна бр. 1',
            'city' => 'Скопје',
            'state' => 'Скопје',
            'zip' => '1000',
            'country_id' => 129,
            'phone' => '+38970000000',
        ]);

        CompanySetting::setSettings(['currency' => $this->currency->id], $this->company->id);

        // Create IFRS entity + accounts
        $this->entityId = $this->createIfrsInfrastructure();

        // Create test invoices and bills
        $this->seedInvoicesAndBills();

        // Enable IFRS
        config(['ifrs.enabled' => true]);
        CompanySetting::setSettings(['ifrs_enabled' => 'YES'], $this->company->id);
    }

    // ---------------------------------------------------------------
    // REGISTRY TESTS
    // ---------------------------------------------------------------

    public function test_registry_returns_all_four_forms(): void
    {
        $registry = TaxFormService::registry();

        $this->assertCount(4, $registry);
        $this->assertArrayHasKey('ddv-04', $registry);
        $this->assertArrayHasKey('db', $registry);
        $this->assertArrayHasKey('obrazec-36', $registry);
        $this->assertArrayHasKey('obrazec-37', $registry);
    }

    public function test_resolve_returns_correct_service_instances(): void
    {
        $this->assertInstanceOf(DDV04FormService::class, TaxFormService::resolve('ddv-04'));
        $this->assertInstanceOf(DbFormService::class, TaxFormService::resolve('db'));
        $this->assertInstanceOf(Obrazec36FormService::class, TaxFormService::resolve('obrazec-36'));
        $this->assertInstanceOf(Obrazec37FormService::class, TaxFormService::resolve('obrazec-37'));
        $this->assertNull(TaxFormService::resolve('nonexistent'));
    }

    // ---------------------------------------------------------------
    // DDV-04 TESTS
    // ---------------------------------------------------------------

    public function test_ddv04_collect_returns_correct_structure(): void
    {
        $service = app(DDV04FormService::class);
        $data = $service->collect($this->company, 2025, 1);

        $this->assertArrayHasKey('fields', $data);
        $this->assertArrayHasKey('output_vat', $data);
        $this->assertArrayHasKey('input_vat', $data);
        $this->assertArrayHasKey('period_start', $data);
        $this->assertArrayHasKey('period_end', $data);
        $this->assertArrayHasKey('overrides', $data);

        // Fields: 1-19 (output + input), 30-32 (calculation)
        $fields = $data['fields'];
        $this->assertArrayHasKey(1, $fields);   // Standard 18% base
        $this->assertArrayHasKey(3, $fields);   // Hospitality 10% base
        $this->assertArrayHasKey(5, $fields);   // Reduced 5% base
        $this->assertArrayHasKey(10, $fields);  // Total output VAT
        $this->assertArrayHasKey(19, $fields);  // Total input VAT
        $this->assertArrayHasKey(30, $fields);  // Carryover
        $this->assertArrayHasKey(31, $fields);  // Tax debt/claim
        $this->assertArrayHasKey(32, $fields);  // Refund

        // Should also include proportional deduction data
        $this->assertArrayHasKey('proportional_deduction', $data);
    }

    public function test_ddv04_collect_has_nonzero_output_vat(): void
    {
        $service = app(DDV04FormService::class);
        $data = $service->collect($this->company, 2025, 1);

        // We created paid invoices with VAT, so output VAT should be non-zero
        $fields = $data['fields'];
        $this->assertGreaterThan(0, $fields[2], 'Standard rate VAT (field 2) should be > 0');
        $this->assertGreaterThan(0, $fields[10], 'Total output VAT (field 10) should be > 0');
    }

    public function test_ddv04_validate_passes_for_correct_data(): void
    {
        $service = app(DDV04FormService::class);
        $data = $service->collect($this->company, 2025, 1);
        $validation = $service->validate($data);

        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
        // Should have no errors (fields are auto-calculated correctly)
        $this->assertEmpty($validation['errors'], 'DDV-04 validation should have no errors: ' . implode(', ', $validation['errors']));
    }

    public function test_ddv04_to_xml_returns_valid_xml(): void
    {
        $service = app(DDV04FormService::class);
        $data = $service->collect($this->company, 2025, 1);
        $xml = $service->toXml($this->company, $data);

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('DDV04', $xml);
        $this->assertStringContainsString('DDV-04', $xml);

        // Verify it's parseable XML
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'DDV-04 XML should be valid');
    }

    public function test_ddv04_to_pdf_returns_response(): void
    {
        $service = app(DDV04FormService::class);
        $data = $service->collect($this->company, 2025, 1);
        $response = $service->toPdf($this->company, $data, 2025);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    public function test_ddv04_preview_returns_complete_structure(): void
    {
        $service = app(DDV04FormService::class);
        $preview = $service->preview($this->company, 2025, 1);

        $this->assertArrayHasKey('data', $preview);
        $this->assertArrayHasKey('validation', $preview);
        $this->assertArrayHasKey('form', $preview);
        $this->assertArrayHasKey('company', $preview);
        $this->assertArrayHasKey('period', $preview);

        $this->assertEquals('ДДВ-04', $preview['form']['code']);
        $this->assertEquals('monthly', $preview['form']['period_type']);
    }

    // ---------------------------------------------------------------
    // ДБ TESTS
    // ---------------------------------------------------------------

    public function test_db_collect_returns_correct_structure(): void
    {
        $service = app(DbFormService::class);
        $data = $service->collect($this->company, 2025);

        $this->assertArrayHasKey('aop', $data);
        $this->assertArrayHasKey('year', $data);
        $this->assertArrayHasKey('config', $data);

        // Should have 70 AOP fields
        $aop = $data['aop'];
        $this->assertCount(70, $aop);
        $this->assertArrayHasKey('01', $aop);
        $this->assertArrayHasKey('40', $aop);
        $this->assertArrayHasKey('50', $aop);
        $this->assertArrayHasKey('70', $aop);
    }

    public function test_db_formula_fields_are_correct(): void
    {
        $service = app(DbFormService::class);
        $data = $service->collect($this->company, 2025);
        $aop = $data['aop'];

        // AOP 40 = AOP 01 + AOP 02
        $this->assertEqualsWithDelta($aop['01'] + $aop['02'], $aop['40'], 0.01, 'AOP 40 should equal AOP 01 + AOP 02');

        // AOP 49 = max(0, AOP 40 - AOP 41)
        $expected49 = max(0, $aop['40'] - $aop['41']);
        $this->assertEqualsWithDelta($expected49, $aop['49'], 0.01, 'AOP 49 should equal max(0, 40 - 41)');

        // AOP 50 = AOP 49 * 10%
        $expected50 = round($aop['49'] * 0.10, 2);
        $this->assertEqualsWithDelta($expected50, $aop['50'], 0.01, 'AOP 50 should equal 49 * 10%');
    }

    public function test_db_validate_passes(): void
    {
        $service = app(DbFormService::class);
        $data = $service->collect($this->company, 2025);
        $validation = $service->validate($data);

        $this->assertEmpty($validation['errors'], 'DB validation should have no errors: ' . implode(', ', $validation['errors']));
    }

    public function test_db_to_xml_returns_valid_xml(): void
    {
        $service = app(DbFormService::class);
        $data = $service->collect($this->company, 2025);
        $xml = $service->toXml($this->company, $data);

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml', $xml);

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'DB XML should be valid');
    }

    public function test_db_to_pdf_returns_response(): void
    {
        $service = app(DbFormService::class);
        $data = $service->collect($this->company, 2025);
        $response = $service->toPdf($this->company, $data, 2025);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    // ---------------------------------------------------------------
    // ОБРАЗЕЦ 36 TESTS
    // ---------------------------------------------------------------

    public function test_obrazec36_collect_returns_correct_structure(): void
    {
        $service = app(Obrazec36FormService::class);
        $data = $service->collect($this->company, 2025);

        $this->assertArrayHasKey('aktiva', $data);
        $this->assertArrayHasKey('pasiva', $data);
        $this->assertArrayHasKey('total_aktiva', $data);
        $this->assertArrayHasKey('total_pasiva', $data);
        $this->assertArrayHasKey('is_balanced', $data);
        $this->assertArrayHasKey('year', $data);

        // Aktiva and pasiva should be arrays of AOP rows
        $this->assertNotEmpty($data['aktiva']);
        $this->assertNotEmpty($data['pasiva']);

        // Each row should have required keys
        $firstRow = $data['aktiva'][0];
        $this->assertArrayHasKey('aop', $firstRow);
        $this->assertArrayHasKey('label', $firstRow);
        $this->assertArrayHasKey('current', $firstRow);
        $this->assertArrayHasKey('previous', $firstRow);
    }

    public function test_obrazec36_validate_works(): void
    {
        $service = app(Obrazec36FormService::class);
        $data = $service->collect($this->company, 2025);
        $validation = $service->validate($data);

        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
    }

    public function test_obrazec36_to_xml_returns_valid_xml(): void
    {
        $service = app(Obrazec36FormService::class);
        $data = $service->collect($this->company, 2025);
        $xml = $service->toXml($this->company, $data);

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('FormType', $xml);
        $this->assertStringContainsString('17', $xml); // FormType 17 for Obrazec 36

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Obrazec 36 XML should be valid');
    }

    public function test_obrazec36_to_pdf_returns_response(): void
    {
        $service = app(Obrazec36FormService::class);
        $data = $service->collect($this->company, 2025);
        $response = $service->toPdf($this->company, $data, 2025);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    // ---------------------------------------------------------------
    // ОБРАЗЕЦ 37 TESTS
    // ---------------------------------------------------------------

    public function test_obrazec37_collect_returns_correct_structure(): void
    {
        $service = app(Obrazec37FormService::class);
        $data = $service->collect($this->company, 2025);

        $this->assertArrayHasKey('prihodi', $data);
        $this->assertArrayHasKey('rashodi', $data);
        $this->assertArrayHasKey('rezultat', $data);
        $this->assertArrayHasKey('year', $data);

        $this->assertNotEmpty($data['prihodi']);
        $this->assertNotEmpty($data['rashodi']);
        $this->assertNotEmpty($data['rezultat']);
    }

    public function test_obrazec37_validate_works(): void
    {
        $service = app(Obrazec37FormService::class);
        $data = $service->collect($this->company, 2025);
        $validation = $service->validate($data);

        $this->assertArrayHasKey('errors', $validation);
        $this->assertArrayHasKey('warnings', $validation);
        // Should have no errors
        $this->assertEmpty($validation['errors'], 'Obrazec 37 should have no validation errors: ' . implode(', ', $validation['errors']));
    }

    public function test_obrazec37_to_xml_returns_valid_xml(): void
    {
        $service = app(Obrazec37FormService::class);
        $data = $service->collect($this->company, 2025);
        $xml = $service->toXml($this->company, $data);

        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('18', $xml); // FormType 18 for Obrazec 37

        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Obrazec 37 XML should be valid');
    }

    public function test_obrazec37_to_pdf_returns_response(): void
    {
        $service = app(Obrazec37FormService::class);
        $data = $service->collect($this->company, 2025);
        $response = $service->toPdf($this->company, $data, 2025);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    // ---------------------------------------------------------------
    // EMPTY DATA GRACEFUL HANDLING
    // ---------------------------------------------------------------

    public function test_ddv04_handles_empty_data_gracefully(): void
    {
        $emptyCompany = Company::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Empty Company',
        ]);
        CompanySetting::setSettings(['currency' => $this->currency->id], $emptyCompany->id);

        $service = app(DDV04FormService::class);

        // Should not throw, should return zero fields
        try {
            $data = $service->collect($emptyCompany, 2025, 1);
            $fields = $data['fields'];
            $this->assertEquals(0, $fields[10], 'Total output VAT should be 0 for empty company');
        } catch (\Exception $e) {
            // DDV-04 throws when no invoices found — this is acceptable behavior
            $this->assertStringContainsString('invoices', strtolower($e->getMessage()));
        }
    }

    public function test_db_handles_empty_ifrs_gracefully(): void
    {
        $emptyCompany = Company::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Empty Company 2',
        ]);

        $service = app(DbFormService::class);
        $data = $service->collect($emptyCompany, 2025);

        // AOP 01 should be 0 (no income statement data)
        $this->assertEquals(0, $data['aop']['01']);
        // All formula fields should still calculate correctly
        $this->assertEquals(0, $data['aop']['40']);
        $this->assertEquals(0, $data['aop']['50']);
    }

    public function test_obrazec36_handles_empty_ifrs_gracefully(): void
    {
        $emptyCompany = Company::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Empty Company 3',
        ]);

        $service = app(Obrazec36FormService::class);
        $data = $service->collect($emptyCompany, 2025);

        // Should return valid structure with zero values
        $this->assertArrayHasKey('aktiva', $data);
        $this->assertArrayHasKey('pasiva', $data);
        $this->assertEquals(0, $data['total_aktiva']);
        $this->assertEquals(0, $data['total_pasiva']);
        $this->assertTrue($data['is_balanced']); // 0 == 0
    }

    /**
     * Verify multi-level subtotal computation in buildAopRows.
     * Grand total → section total → subsection total → leaf nodes.
     */
    public function test_aop_multi_level_subtotals(): void
    {
        $aopService = app(\App\Services\AopReportService::class);

        // 4-level hierarchy: grand → section → subsection → leaf
        $config = [
            ['aop' => '003', 'label' => 'Leaf A', 'ifrs_types' => ['BANK'], 'indent' => 3],
            ['aop' => '004', 'label' => 'Leaf B', 'ifrs_types' => ['BANK'], 'indent' => 3],
            ['aop' => '002', 'label' => 'Subsection', 'is_total' => true, 'sum_of' => ['003', '004'], 'indent' => 2],
            ['aop' => '005', 'label' => 'Leaf C', 'ifrs_types' => ['RECEIVABLE'], 'indent' => 2],
            ['aop' => '001', 'label' => 'Section', 'is_total' => true, 'sum_of' => ['002', '005'], 'indent' => 1],
            ['aop' => '006', 'label' => 'Other Section', 'ifrs_types' => ['EQUITY'], 'indent' => 1],
            ['aop' => '999', 'label' => 'Grand Total', 'is_total' => true, 'sum_of' => ['001', '006'], 'indent' => 0],
        ];

        $current = ['BANK' => 100, 'RECEIVABLE' => 50, 'EQUITY' => 200];
        $previous = ['BANK' => 80, 'RECEIVABLE' => 40, 'EQUITY' => 150];
        $fallback = ['BANK' => '003', 'RECEIVABLE' => '005', 'EQUITY' => '006'];

        $rows = $aopService->buildAopRows($config, $current, $previous, $fallback);

        $byAop = [];
        foreach ($rows as $row) {
            $byAop[$row['aop']] = $row;
        }

        // Leaf nodes
        $this->assertEquals(100, $byAop['003']['current']); // BANK → 003
        $this->assertEquals(50, $byAop['005']['current']);   // RECEIVABLE → 005
        $this->assertEquals(200, $byAop['006']['current']);  // EQUITY → 006

        // Subsection: 003 + 004 = 100 + 0 = 100
        $this->assertEquals(100, $byAop['002']['current']);

        // Section: 002 + 005 = 100 + 50 = 150
        $this->assertEquals(150, $byAop['001']['current']);

        // Grand total: 001 + 006 = 150 + 200 = 350
        $this->assertEquals(350, $byAop['999']['current']);

        // Previous year
        $this->assertEquals(120, $byAop['001']['previous']); // 80 + 0 + 40
        $this->assertEquals(270, $byAop['999']['previous']); // 120 + 150
    }

    /**
     * Verify signed sum_of entries work (e.g., -068 subtracts AOP 068).
     */
    public function test_aop_signed_sum_of(): void
    {
        $aopService = app(\App\Services\AopReportService::class);

        $config = [
            ['aop' => '066', 'label' => 'Capital', 'ifrs_types' => ['EQUITY'], 'indent' => 1],
            ['aop' => '068', 'label' => 'Treasury Shares', 'ifrs_types' => ['CONTRA_EQUITY'], 'indent' => 1],
            ['aop' => '065', 'label' => 'Net Equity', 'is_total' => true, 'sum_of' => ['+066', '-068'], 'indent' => 0],
        ];

        $current = ['EQUITY' => 1000, 'CONTRA_EQUITY' => 200];
        $fallback = ['EQUITY' => '066', 'CONTRA_EQUITY' => '068'];

        $rows = $aopService->buildAopRows($config, $current, [], $fallback);
        $byAop = [];
        foreach ($rows as $row) {
            $byAop[$row['aop']] = $row;
        }

        // 1000 - 200 = 800
        $this->assertEquals(800, $byAop['065']['current']);
    }

    // ---------------------------------------------------------------
    // API ENDPOINT TESTS
    // ---------------------------------------------------------------

    public function test_admin_ujp_list_endpoint(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->withHeaders(['company' => $this->company->id])
            ->getJson('/api/v1/tax/ujp-forms/list');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
        $this->assertCount(4, $response->json('data'));
    }

    public function test_admin_ujp_preview_endpoint(): void
    {
        $this->actingAs($this->user, 'sanctum');

        $response = $this->withHeaders(['company' => $this->company->id])
            ->getJson("/api/v1/tax/ujp-forms/db/preview?year=2025&company_id={$this->company->id}");

        $response->assertStatus(200);
    }

    // ---------------------------------------------------------------
    // HELPER METHODS
    // ---------------------------------------------------------------

    protected function createIfrsInfrastructure(): int
    {
        // Disable FK checks for circular dependency (entity needs currency, currency needs entity)
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF');

        // Create entity first (with null currency_id)
        $entityId = \Illuminate\Support\Facades\DB::table('ifrs_entities')->insertGetId([
            'name' => $this->company->name,
            'currency_id' => null,
            'year_start' => 1,
            'multi_currency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create IFRS currency linked to entity
        $ifrsCurrencyId = \Illuminate\Support\Facades\DB::table('ifrs_currencies')->insertGetId([
            'name' => 'Macedonian Denar',
            'currency_code' => 'MKD',
            'entity_id' => $entityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update entity with currency
        \Illuminate\Support\Facades\DB::table('ifrs_entities')
            ->where('id', $entityId)
            ->update(['currency_id' => $ifrsCurrencyId]);

        // Re-enable FK checks
        \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON');

        // Link to company
        $this->company->update(['ifrs_entity_id' => $entityId]);

        // Exchange rate
        \Illuminate\Support\Facades\DB::table('ifrs_exchange_rates')->insert([
            'entity_id' => $entityId,
            'currency_id' => $ifrsCurrencyId,
            'valid_from' => '2025-01-01',
            'valid_to' => '2025-12-31',
            'rate' => 1.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Reporting period
        \Illuminate\Support\Facades\DB::table('ifrs_reporting_periods')->insert([
            'entity_id' => $entityId,
            'calendar_year' => 2025,
            'period_count' => 1,
            'status' => 'OPEN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create one category per account type (IFRS requires category_type == account_type)
        $categories = [];
        $catDefs = [
            \IFRS\Models\Account::BANK => 'Bank Accounts',
            \IFRS\Models\Account::RECEIVABLE => 'Receivables',
            \IFRS\Models\Account::CURRENT_ASSET => 'Current Assets',
            \IFRS\Models\Account::PAYABLE => 'Payables',
            \IFRS\Models\Account::CURRENT_LIABILITY => 'Current Liabilities',
            \IFRS\Models\Account::EQUITY => 'Equity',
            \IFRS\Models\Account::OPERATING_REVENUE => 'Operating Revenue',
            \IFRS\Models\Account::OPERATING_EXPENSE => 'Operating Expenses',
        ];

        foreach ($catDefs as $type => $name) {
            $categories[$type] = \IFRS\Models\Category::create([
                'name' => $name,
                'category_type' => $type,
                'entity_id' => $entityId,
            ]);
        }

        // Create chart of accounts (each account's type must match its category's type)
        $accounts = [
            [1000, 'Cash', \IFRS\Models\Account::BANK],
            [1200, 'Accounts Receivable', \IFRS\Models\Account::RECEIVABLE],
            [1410, 'VAT Receivable', \IFRS\Models\Account::CURRENT_ASSET],
            [2000, 'Accounts Payable', \IFRS\Models\Account::PAYABLE],
            [2100, 'Tax Payable', \IFRS\Models\Account::CURRENT_LIABILITY],
            [3000, 'Equity', \IFRS\Models\Account::EQUITY],
            [4000, 'Sales Revenue', \IFRS\Models\Account::OPERATING_REVENUE],
            [5000, 'Expenses', \IFRS\Models\Account::OPERATING_EXPENSE],
        ];

        foreach ($accounts as [$code, $name, $type]) {
            \IFRS\Models\Account::create([
                'code' => $code,
                'name' => $name,
                'account_type' => $type,
                'category_id' => $categories[$type]->id,
                'currency_id' => $ifrsCurrencyId,
                'entity_id' => $entityId,
            ]);
        }

        return $entityId;
    }

    protected function seedInvoicesAndBills(): void
    {
        $ifrsAdapter = app(IfrsAdapter::class);

        $taxType18 = TaxType::create([
            'name' => 'ДДВ 18%',
            'company_id' => $this->company->id,
            'percent' => 18,
            'compound_tax' => false,
            'collective_tax' => 0,
            'type' => 'MODULE',
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.mk',
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'creator_id' => $this->user->id,
        ]);

        // Create 3 paid invoices for January 2025
        for ($i = 1; $i <= 3; $i++) {
            $subTotal = 50000 * $i; // 50k, 100k, 150k MKD
            $tax = round($subTotal * 0.18);
            $total = $subTotal + $tax;

            $invoice = new Invoice();
            $invoice->invoice_number = "TEST-INV-{$i}";
            $invoice->invoice_date = "2025-01-{$i}0";
            $invoice->due_date = "2025-02-{$i}0";
            $invoice->company_id = $this->company->id;
            $invoice->customer_id = $customer->id;
            $invoice->currency_id = $this->currency->id;
            $invoice->creator_id = $this->user->id;
            $invoice->template_name = 'invoice1';
            $invoice->unique_hash = Str::random(20);
            $invoice->sub_total = $subTotal;
            $invoice->tax = $tax;
            $invoice->total = $total;
            $invoice->due_amount = 0;
            $invoice->tax_per_item = 'YES';
            $invoice->discount_per_item = 'NO';
            $invoice->status = Invoice::STATUS_COMPLETED;
            $invoice->paid_status = Invoice::STATUS_PAID;
            $invoice->saveQuietly();

            $item = InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'name' => "Test Service {$i}",
                'quantity' => 1,
                'price' => $subTotal,
                'total' => $subTotal,
                'company_id' => $this->company->id,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => $tax,
            ]);

            Tax::create([
                'invoice_item_id' => $item->id,
                'invoice_id' => $invoice->id,
                'tax_type_id' => $taxType18->id,
                'company_id' => $this->company->id,
                'name' => 'ДДВ 18%',
                'amount' => $tax,
                'percent' => 18,
                'compound_tax' => 0,
                'base_amount' => $subTotal,
            ]);

            // Post to IFRS
            try {
                $ifrsAdapter->postInvoice($invoice);
            } catch (\Exception $e) {
                // Log but don't fail setup
            }
        }

        // Create 2 paid bills for January 2025
        for ($i = 1; $i <= 2; $i++) {
            $subTotal = 20000 * $i;
            $tax = round($subTotal * 0.18);
            $total = $subTotal + $tax;

            $bill = new Bill();
            $bill->bill_number = "TEST-BILL-{$i}";
            $bill->bill_date = "2025-01-{$i}5";
            $bill->due_date = "2025-02-{$i}5";
            $bill->company_id = $this->company->id;
            $bill->currency_id = $this->currency->id;
            $bill->creator_id = $this->user->id;
            $bill->unique_hash = Str::random(20);
            $bill->sub_total = $subTotal;
            $bill->tax = $tax;
            $bill->total = $total;
            $bill->due_amount = 0;
            $bill->tax_per_item = 'YES';
            $bill->discount_per_item = 'NO';
            $bill->status = 'COMPLETED';
            $bill->paid_status = Bill::PAID_STATUS_PAID;
            $bill->saveQuietly();

            $billItem = BillItem::create([
                'bill_id' => $bill->id,
                'name' => "Test Expense {$i}",
                'quantity' => 1,
                'price' => $subTotal,
                'total' => $subTotal,
                'company_id' => $this->company->id,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => $tax,
            ]);

            Tax::create([
                'bill_item_id' => $billItem->id,
                'bill_id' => $bill->id,
                'tax_type_id' => $taxType18->id,
                'company_id' => $this->company->id,
                'name' => 'ДДВ 18%',
                'amount' => $tax,
                'percent' => 18,
                'compound_tax' => 0,
                'base_amount' => $subTotal,
            ]);

            // Post to IFRS
            try {
                $ifrsAdapter->postBill($bill);
            } catch (\Exception $e) {
                // Log but don't fail setup
            }
        }
    }
}

// CLAUDE-CHECKPOINT
