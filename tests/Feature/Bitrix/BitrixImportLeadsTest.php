<?php

namespace Tests\Feature\Bitrix;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\Suppression;
use Tests\TestCase;

/**
 * BitrixImportLeads Feature Tests
 *
 * Tests the Bitrix24 lead import command functionality including:
 * - CSV import creates leads
 * - Deduplication by email
 * - Invalid CSV handling
 * - Dry-run flag
 *
 * @ticket BITRIX-04 - Lead Import Command
 */
class BitrixImportLeadsTest extends TestCase
{
    use RefreshDatabase;

    protected string $testCsvPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the tables needed for tests
        $this->createOutreachTables();

        // Configure Bitrix24 webhook URL
        Config::set('bitrix.webhook_base_url', 'https://test.bitrix24.com/rest/1/testtoken');

        // Create a temporary CSV file path
        $this->testCsvPath = storage_path('app/test-leads.csv');
    }

    protected function tearDown(): void
    {
        // Clean up test CSV file
        if (file_exists($this->testCsvPath)) {
            unlink($this->testCsvPath);
        }

        parent::tearDown();
    }

    /**
     * Create the necessary database tables for import tests.
     */
    protected function createOutreachTables(): void
    {
        if (!\Schema::hasTable('outreach_leads')) {
            \Schema::create('outreach_leads', function ($table) {
                $table->id();
                $table->string('email')->index();
                $table->string('company_name')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('city')->nullable();
                $table->string('source')->nullable();
                $table->string('source_url')->nullable();
                $table->json('tags')->nullable();
                $table->string('status')->default('new');
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->timestamp('last_contacted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!\Schema::hasTable('suppressions')) {
            \Schema::create('suppressions', function ($table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('reason');
                $table->string('source')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!\Schema::hasTable('bitrix_lead_maps')) {
            \Schema::create('bitrix_lead_maps', function ($table) {
                $table->id();
                $table->unsignedBigInteger('outreach_lead_id');
                $table->string('bitrix_lead_id');
                $table->string('status')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Create a test CSV file with the given data.
     */
    protected function createTestCsv(array $rows, ?array $headers = null): void
    {
        $headers = $headers ?? ['company_name', 'email', 'phone', 'city', 'website', 'source', 'tags', 'contact_name'];

        $content = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $content .= implode(',', $row) . "\n";
        }

        file_put_contents($this->testCsvPath, $content);
    }

    /** @test */
    public function test_csv_import_creates_leads()
    {
        // Mock Bitrix API
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/*' => Http::response([
                'result' => 12345,
            ], 200),
        ]);

        $this->createTestCsv([
            ['Test Company 1', 'test1@example.com', '+389701234567', 'Skopje', 'https://test1.com', 'linkedin', 'accounting,software', 'John Doe'],
            ['Test Company 2', 'test2@example.com', '+389702234567', 'Bitola', 'https://test2.com', 'manual', 'finance', 'Jane Smith'],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->expectsOutputToContain('Found 2 rows')
            ->expectsOutputToContain('Created: 2')
            ->expectsOutputToContain('Import completed')
            ->assertSuccessful();

        // Verify leads were created
        $this->assertDatabaseHas('outreach_leads', [
            'email' => 'test1@example.com',
            'company_name' => 'Test Company 1',
            'city' => 'Skopje',
            'contact_name' => 'John Doe',
        ]);

        $this->assertDatabaseHas('outreach_leads', [
            'email' => 'test2@example.com',
            'company_name' => 'Test Company 2',
            'city' => 'Bitola',
        ]);

        $this->assertEquals(2, OutreachLead::count());
    }

    /** @test */
    public function test_deduplication_by_email_importing_same_email_twice_creates_one_lead()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/*' => Http::response([
                'result' => 12345,
            ], 200),
        ]);

        // First import
        $this->createTestCsv([
            ['Company A', 'duplicate@example.com', '', 'Skopje', '', 'manual', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->expectsOutputToContain('Created: 1')
            ->assertSuccessful();

        $this->assertEquals(1, OutreachLead::count());

        // Second import with same email
        $this->createTestCsv([
            ['Company B Updated', 'duplicate@example.com', '+389701111111', 'Bitola', '', 'linkedin', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->expectsOutputToContain('Updated: 1')
            ->expectsOutputToContain('Created: 0')
            ->assertSuccessful();

        // Should still have only 1 lead
        $this->assertEquals(1, OutreachLead::count());

        // But it should be updated with new data
        $lead = OutreachLead::where('email', 'duplicate@example.com')->first();
        $this->assertEquals('Company B Updated', $lead->company_name);
        $this->assertEquals('Bitola', $lead->city);
    }

    /** @test */
    public function test_invalid_csv_handling_missing_required_columns()
    {
        // Create CSV without required 'email' column
        $this->createTestCsv(
            [['Test Company', '', 'Skopje']],
            ['company_name', 'phone', 'city']  // Missing 'email'
        );

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath])
            ->expectsOutputToContain('Missing required CSV columns: email')
            ->assertFailed();

        $this->assertEquals(0, OutreachLead::count());
    }

    /** @test */
    public function test_invalid_csv_handling_invalid_email()
    {
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/*' => Http::response([
                'result' => 12345,
            ], 200),
        ]);

        $this->createTestCsv([
            ['Valid Company', 'valid@example.com', '', '', '', '', '', ''],
            ['Invalid Company', 'not-an-email', '', '', '', '', '', ''],
            ['Another Valid', 'another@example.com', '', '', '', '', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->expectsOutputToContain('Created: 2')
            ->expectsOutputToContain('Invalid email')
            ->assertSuccessful();

        $this->assertEquals(2, OutreachLead::count());
        $this->assertDatabaseMissing('outreach_leads', ['company_name' => 'Invalid Company']);
    }

    /** @test */
    public function test_invalid_csv_handling_missing_csv_file()
    {
        $this->artisan('bitrix:import-leads', ['--csv' => '/nonexistent/path/leads.csv'])
            ->expectsOutputToContain('CSV file not found')
            ->assertFailed();
    }

    /** @test */
    public function test_invalid_csv_handling_no_csv_option()
    {
        $this->artisan('bitrix:import-leads')
            ->expectsOutputToContain('CSV file path is required')
            ->assertFailed();
    }

    /** @test */
    public function test_dry_run_flag_does_not_create_leads()
    {
        $this->createTestCsv([
            ['Test Company', 'dryrun@example.com', '', 'Skopje', '', 'manual', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('[DRY-RUN CREATE]')
            ->assertSuccessful();

        // No leads should be created
        $this->assertEquals(0, OutreachLead::count());
    }

    /** @test */
    public function test_dry_run_shows_what_would_be_updated()
    {
        // Create existing lead
        OutreachLead::create([
            'email' => 'existing@example.com',
            'company_name' => 'Existing Company',
            'status' => 'new',
        ]);

        $this->createTestCsv([
            ['Updated Company', 'existing@example.com', '', '', '', '', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN MODE')
            ->expectsOutputToContain('[DRY-RUN UPDATE]')
            ->assertSuccessful();

        // Lead should not be updated
        $lead = OutreachLead::where('email', 'existing@example.com')->first();
        $this->assertEquals('Existing Company', $lead->company_name);
    }

    /** @test */
    public function test_suppressed_emails_are_skipped()
    {
        // Add email to suppression list
        Suppression::suppress('suppressed@example.com', 'unsubscribe');

        $this->createTestCsv([
            ['Active Company', 'active@example.com', '', '', '', '', '', ''],
            ['Suppressed Company', 'suppressed@example.com', '', '', '', '', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->expectsOutputToContain('Created: 1')
            ->expectsOutputToContain('Suppressed: 1')
            ->expectsOutputToContain('[SUPPRESSED]')
            ->assertSuccessful();

        // Only active lead should be created
        $this->assertEquals(1, OutreachLead::count());
        $this->assertDatabaseHas('outreach_leads', ['email' => 'active@example.com']);
        $this->assertDatabaseMissing('outreach_leads', ['email' => 'suppressed@example.com']);
    }

    /** @test */
    public function test_import_with_bitrix_sync()
    {
        // Mock Bitrix API for lead creation
        Http::fake([
            'https://test.bitrix24.com/rest/1/testtoken/crm.lead.add.json' => Http::response([
                'result' => 54321,
            ], 200),
        ]);

        $this->createTestCsv([
            ['Bitrix Company', 'bitrix@example.com', '+389701234567', 'Skopje', 'https://bitrix.com', 'api', '', 'Contact Name'],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath])
            ->expectsOutputToContain('Created: 1')
            ->assertSuccessful();

        // Verify lead was created
        $this->assertDatabaseHas('outreach_leads', [
            'email' => 'bitrix@example.com',
        ]);

        // Verify Bitrix API was called
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'crm.lead.add') &&
                   $request['fields']['TITLE'] === 'Bitrix Company';
        });
    }

    /** @test */
    public function test_skip_bitrix_flag_only_imports_locally()
    {
        Http::fake();

        $this->createTestCsv([
            ['Local Company', 'local@example.com', '', 'Skopje', '', '', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->expectsOutputToContain('SKIP BITRIX MODE')
            ->expectsOutputToContain('Created: 1')
            ->assertSuccessful();

        // Lead should be created locally
        $this->assertDatabaseHas('outreach_leads', ['email' => 'local@example.com']);

        // Bitrix API should not be called
        Http::assertNothingSent();
    }

    /** @test */
    public function test_import_handles_tags_correctly()
    {
        $this->createTestCsv([
            ['Tagged Company', 'tagged@example.com', '', '', '', '', 'accounting,software,saas', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->assertSuccessful();

        $lead = OutreachLead::where('email', 'tagged@example.com')->first();
        $this->assertIsArray($lead->tags);
        $this->assertCount(3, $lead->tags);
        $this->assertContains('accounting', $lead->tags);
        $this->assertContains('software', $lead->tags);
        $this->assertContains('saas', $lead->tags);
    }

    /** @test */
    public function test_import_normalizes_email_to_lowercase()
    {
        $this->createTestCsv([
            ['Mixed Case', 'MixedCase@Example.COM', '', '', '', '', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('outreach_leads', [
            'email' => 'mixedcase@example.com',
        ]);
    }

    /** @test */
    public function test_import_sets_default_source()
    {
        $this->createTestCsv([
            ['No Source Company', 'nosource@example.com', '', '', '', '', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->assertSuccessful();

        $lead = OutreachLead::where('email', 'nosource@example.com')->first();
        $this->assertEquals('csv_import', $lead->source);
    }

    /** @test */
    public function test_import_displays_summary()
    {
        $this->createTestCsv([
            ['Company 1', 'test1@example.com', '', '', '', '', '', ''],
            ['Company 2', 'test2@example.com', '', '', '', '', '', ''],
            ['Company 3', 'test3@example.com', '', '', '', '', '', ''],
        ]);

        $this->artisan('bitrix:import-leads', ['--csv' => $this->testCsvPath, '--skip-bitrix' => true])
            ->expectsOutputToContain('Found 3 rows')
            ->expectsOutputToContain('Import Results')
            ->expectsOutputToContain('Total rows processed: 3')
            ->expectsOutputToContain('Created: 3')
            ->assertSuccessful();
    }
}

// CLAUDE-CHECKPOINT
