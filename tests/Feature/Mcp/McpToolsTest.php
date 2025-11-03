<?php

namespace Tests\Feature\Mcp;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MCP Tools Integration Tests
 *
 * Tests the internal MCP API endpoints that are called by the MCP server.
 * These tests verify authentication, authorization, and functionality.
 */
class McpToolsTest extends TestCase
{
    use RefreshDatabase;

    protected string $validToken;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test configuration
        config(['features.mcp_ai_tools.enabled' => true]);
        $this->validToken = 'test-mcp-token-'.bin2hex(random_bytes(16));
        config(['services.mcp.token' => $this->validToken]);

        // Create test company
        $this->company = Company::factory()->create();
    }

    /**
     * Test that MCP endpoints require valid Bearer token
     */
    public function test_mcp_endpoints_require_valid_token(): void
    {
        $response = $this->postJson('/internal/mcp/company-stats', [
            'company_id' => $this->company->id,
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid MCP token']);
    }

    /**
     * Test that invalid token is rejected
     */
    public function test_invalid_token_rejected(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->postJson('/internal/mcp/company-stats', [
            'company_id' => $this->company->id,
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Invalid MCP token']);
    }

    /**
     * Test that MCP endpoints are disabled when feature flag is off
     */
    public function test_feature_flag_guards_mcp_endpoints(): void
    {
        config(['features.mcp_ai_tools.enabled' => false]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/company-stats', [
            'company_id' => $this->company->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'MCP tools disabled']);
    }

    /**
     * Test company stats endpoint with valid authentication
     */
    public function test_company_stats_returns_valid_data(): void
    {
        // Create test data
        Customer::factory()->count(5)->create(['company_id' => $this->company->id]);
        Invoice::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'status' => 'PAID',
        ]);
        Invoice::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => 'SENT',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/company-stats', [
            'company_id' => $this->company->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'company_id',
                'company_name',
                'total_revenue',
                'invoices_count',
                'customers_count',
                'pending_invoices',
                'overdue_invoices',
                'draft_invoices',
            ])
            ->assertJson([
                'company_id' => $this->company->id,
                'invoices_count' => 13,
                'customers_count' => 5,
            ]);
    }

    /**
     * Test customer search endpoint
     */
    public function test_search_customers_returns_matching_results(): void
    {
        // Create test customers
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
        ]);
        Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Globex Industries',
            'email' => 'info@globex.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/search-customers', [
            'company_id' => $this->company->id,
            'query' => 'acme',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'customers' => [
                    '*' => ['id', 'name', 'email', 'phone', 'total_invoices'],
                ],
            ])
            ->assertJsonCount(1, 'customers')
            ->assertJsonPath('customers.0.name', 'Acme Corporation');
    }

    /**
     * Test tax explanation endpoint
     */
    public function test_explain_tax_returns_breakdown(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'sub_total' => 10000,
            'tax' => 1800, // 18% DDV
            'total' => 11800,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/explain-tax', [
            'invoice_id' => $invoice->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'invoice_id',
                'invoice_number',
                'subtotal',
                'tax_amount',
                'total',
                'items',
                'explanation',
            ])
            ->assertJson([
                'invoice_id' => $invoice->id,
                'subtotal' => 10000,
                'tax_amount' => 1800,
                'total' => 11800,
            ]);
    }

    /**
     * Test UBL validation endpoint
     */
    public function test_ubl_validate_returns_validation_result(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/validate-ubl', [
            'invoice_id' => $invoice->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'invoice_id',
                'invoice_number',
                'valid',
                'signature_valid',
                'errors',
                'warnings',
            ]);
    }

    /**
     * Test anomaly scan endpoint
     */
    public function test_anomaly_scan_detects_issues(): void
    {
        $customer = Customer::factory()->create(['company_id' => $this->company->id]);

        // Create invoice with negative total (anomaly)
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'total' => -1000,
            'invoice_date' => now()->subDays(5),
        ]);

        // Create duplicate invoices (anomaly)
        for ($i = 0; $i < 2; $i++) {
            Invoice::factory()->create([
                'company_id' => $this->company->id,
                'customer_id' => $customer->id,
                'total' => 5000,
                'invoice_date' => now()->subDays(3),
            ]);
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/scan-anomalies', [
            'company_id' => $this->company->id,
            'start' => now()->subDays(10)->toDateString(),
            'end' => now()->toDateString(),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'company_id',
                'date_range',
                'total_scanned',
                'issues_found',
                'severity_breakdown',
                'anomalies',
            ])
            ->assertJsonPath('issues_found', 2); // 1 negative total + 1 duplicate
    }

    /**
     * Test health check endpoint (should not require token)
     */
    public function test_health_check_returns_healthy_status(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->getJson('/internal/mcp/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
                'service' => 'Fakturino MCP API',
            ])
            ->assertJsonStructure(['status', 'service', 'timestamp']);
    }

    /**
     * Test trial balance requires accounting backbone feature
     */
    public function test_trial_balance_requires_accounting_feature(): void
    {
        config(['features.accounting_backbone.enabled' => false]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/trial-balance', [
            'company_id' => $this->company->id,
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'Accounting backbone disabled']);
    }

    /**
     * Test bank transaction categorization requires PSD2 feature
     */
    public function test_bank_categorize_requires_psd2_feature(): void
    {
        config(['features.psd2_banking.enabled' => false]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->validToken}",
        ])->postJson('/internal/mcp/categorize-transaction', [
            'transaction_id' => 1,
        ]);

        $response->assertStatus(403)
            ->assertJson(['error' => 'PSD2 banking disabled']);
    }
}

// CLAUDE-CHECKPOINT
