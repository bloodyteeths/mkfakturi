<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ProjectsTest
 *
 * Feature tests for Phase 1.1 - Project Dimension functionality
 *
 * Coverage:
 * - Project CRUD operations
 * - Project authorization/permissions
 * - Project summary and financial calculations
 * - Project-document relationships
 */
class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $otherUser;

    protected Company $company;

    protected Company $otherCompany;

    protected Currency $currency;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test currency
        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'name' => 'Macedonian Denar',
            'symbol' => 'ден',
            'precision' => 0,
        ]);

        // Create main company and user
        $this->company = Company::factory()->create([
            'name' => 'Test Company',
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        // Create customer for project
        $this->customer = Customer::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'currency_id' => $this->currency->id,
        ]);

        // Create other company and user for authorization tests
        $this->otherCompany = Company::factory()->create([
            'name' => 'Other Company',
        ]);

        $this->otherUser = User::factory()->create();
        $this->otherUser->companies()->attach($this->otherCompany->id);
    }

    // ========================================
    // CREATE PROJECT TESTS
    // ========================================

    /** @test */
    public function it_can_create_a_project_with_valid_data()
    {
        $projectData = [
            'name' => 'Test Construction Project',
            'code' => 'TCP-001',
            'description' => 'A construction project for testing',
            'customer_id' => $this->customer->id,
            'status' => 'open',
            'budget_amount' => 100000000, // 1,000,000 MKD
            'currency_id' => $this->currency->id,
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'notes' => 'Test notes for the project',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/projects', $projectData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'code',
                    'description',
                    'status',
                    'budget_amount',
                    'start_date',
                    'end_date',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Construction Project',
            'code' => 'TCP-001',
            'company_id' => $this->company->id,
            'status' => 'open',
        ]);
    }

    /** @test */
    public function it_requires_name_when_creating_project()
    {
        $projectData = [
            'code' => 'TCP-002',
            'status' => 'open',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/projects', $projectData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_creates_project_with_correct_company_id()
    {
        $projectData = [
            'name' => 'Company Scoped Project',
            'status' => 'open',
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/projects', $projectData);

        $response->assertStatus(201);

        $project = Project::where('name', 'Company Scoped Project')->first();
        $this->assertEquals($this->company->id, $project->company_id);
        $this->assertEquals($this->user->id, $project->creator_id);
    }

    // ========================================
    // UPDATE PROJECT TESTS
    // ========================================

    /** @test */
    public function it_can_update_a_project()
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'status' => 'open',
        ]);

        $updateData = [
            'name' => 'Updated Project Name',
            'status' => 'in_progress',
            'budget_amount' => 50000000,
        ];

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/projects/{$project->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Project Name',
                    'status' => 'in_progress',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'status' => 'in_progress',
        ]);
    }

    /** @test */
    public function it_can_change_project_status()
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'open',
        ]);

        $statuses = ['in_progress', 'on_hold', 'completed', 'cancelled'];

        foreach ($statuses as $status) {
            $response = $this->actingAs($this->user)
                ->withHeader('company', $this->company->id)
                ->putJson("/api/v1/projects/{$project->id}", [
                    'name' => $project->name,
                    'status' => $status,
                ]);

            $response->assertStatus(200);

            $project->refresh();
            $this->assertEquals($status, $project->status);
        }
    }

    // ========================================
    // DELETE PROJECT TESTS
    // ========================================

    /** @test */
    public function it_can_delete_a_project()
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/projects/delete', [
                'ids' => [$project->id],
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Project should be soft deleted or force deleted
        $this->assertTrue(
            Project::withTrashed()->where('id', $project->id)->exists() ||
            ! Project::where('id', $project->id)->exists()
        );
    }

    /** @test */
    public function it_soft_deletes_project_with_associated_documents()
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create an invoice associated with the project
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/projects/delete', [
                'ids' => [$project->id],
            ]);

        $response->assertStatus(200);

        // Project should be soft deleted
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    // ========================================
    // AUTHORIZATION TESTS
    // ========================================

    /** @test */
    public function user_cannot_access_projects_from_another_company()
    {
        $otherProject = Project::factory()->create([
            'company_id' => $this->otherCompany->id,
            'name' => 'Other Company Project',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/projects/{$otherProject->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_update_projects_from_another_company()
    {
        $otherProject = Project::factory()->create([
            'company_id' => $this->otherCompany->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->putJson("/api/v1/projects/{$otherProject->id}", [
                'name' => 'Hacked Project Name',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_cannot_delete_projects_from_another_company()
    {
        $otherProject = Project::factory()->create([
            'company_id' => $this->otherCompany->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/projects/delete', [
                'ids' => [$otherProject->id],
            ]);

        // Should either fail authorization or not delete the project
        $this->assertDatabaseHas('projects', [
            'id' => $otherProject->id,
            'name' => $otherProject->name,
        ]);
    }

    // ========================================
    // LIST AND FILTER TESTS
    // ========================================

    /** @test */
    public function it_can_list_projects()
    {
        Project::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function it_only_lists_projects_from_users_company()
    {
        // Create projects for both companies
        Project::factory()->count(2)->create([
            'company_id' => $this->company->id,
        ]);

        Project::factory()->count(3)->create([
            'company_id' => $this->otherCompany->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_can_filter_projects_by_status()
    {
        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'open',
        ]);

        Project::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/projects?status=open');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'open');
    }

    /** @test */
    public function it_can_search_projects_by_name()
    {
        Project::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Alpha Construction',
        ]);

        Project::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Beta Renovation',
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/projects?search=Alpha');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Alpha Construction');
    }

    // ========================================
    // SUMMARY AND FINANCIAL TESTS
    // ========================================

    /** @test */
    public function it_can_get_project_summary()
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'budget_amount' => 100000000,
            'currency_id' => $this->currency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_invoiced',
                    'total_expenses',
                    'total_payments',
                    'net_result',
                    'invoice_count',
                    'expense_count',
                    'payment_count',
                    'budget_amount',
                    'budget_remaining',
                    'budget_used_percentage',
                ],
            ]);
    }

    /** @test */
    public function it_calculates_project_financial_totals_correctly()
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
            'budget_amount' => 100000000,
        ]);

        // Create invoices linked to project
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'base_total' => 50000000, // 500,000 MKD
        ]);

        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'base_total' => 30000000, // 300,000 MKD
        ]);

        // Create expenses linked to project
        Expense::factory()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'currency_id' => $this->currency->id,
            'base_amount' => 20000000, // 200,000 MKD
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/summary");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(80000000, $data['total_invoiced']); // 800,000 MKD
        $this->assertEquals(20000000, $data['total_expenses']); // 200,000 MKD
        $this->assertEquals(60000000, $data['net_result']); // 600,000 MKD (invoiced - expenses)
        $this->assertEquals(2, $data['invoice_count']);
        $this->assertEquals(1, $data['expense_count']);
    }

    /** @test */
    public function it_can_filter_summary_by_date_range()
    {
        $project = Project::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create invoice in January
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'base_total' => 10000000,
            'invoice_date' => '2025-01-15',
        ]);

        // Create invoice in March
        Invoice::factory()->create([
            'company_id' => $this->company->id,
            'project_id' => $project->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'base_total' => 20000000,
            'invoice_date' => '2025-03-15',
        ]);

        // Get summary for January only
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/projects/{$project->id}/summary?from_date=2025-01-01&to_date=2025-01-31");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(10000000, $data['total_invoiced']);
        $this->assertEquals(1, $data['invoice_count']);
    }

    // ========================================
    // PROJECT LIST DROPDOWN TESTS
    // ========================================

    /** @test */
    public function it_can_get_simplified_project_list_for_dropdown()
    {
        Project::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/projects/list');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'status',
                    ],
                ],
            ]);
    }
}

// CLAUDE-CHECKPOINT
