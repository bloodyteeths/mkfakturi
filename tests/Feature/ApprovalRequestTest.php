<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ApprovalRequestTest
 *
 * Tests for document approval workflow
 */
class ApprovalRequestTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $approver;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->approver = User::factory()->create();
        $this->company = Company::factory()->create();

        $this->user->companies()->attach($this->company->id);
        $this->approver->companies()->attach($this->company->id);
    }

    /** @test */
    public function it_can_request_approval_for_invoice()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $approvalRequest = $invoice->requestApproval('Please approve this invoice');

        $this->assertInstanceOf(ApprovalRequest::class, $approvalRequest);
        $this->assertEquals(ApprovalRequest::STATUS_PENDING, $approvalRequest->status);
        $this->assertEquals($this->user->id, $approvalRequest->requested_by);
    }

    /** @test */
    public function it_prevents_duplicate_pending_approval_requests()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $firstRequest = $invoice->requestApproval('First request');
        $secondRequest = $invoice->requestApproval('Second request');

        $this->assertEquals($firstRequest->id, $secondRequest->id);
    }

    /** @test */
    public function it_can_approve_approval_request()
    {
        $approvalRequest = ApprovalRequest::factory()->create([
            'company_id' => $this->company->id,
            'requested_by' => $this->user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->approver)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/{$this->company->id}/approvals/{$approvalRequest->id}/approve", [
                'note' => 'Looks good',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('approval_requests', [
            'id' => $approvalRequest->id,
            'status' => ApprovalRequest::STATUS_APPROVED,
            'approved_by' => $this->approver->id,
        ]);
    }

    /** @test */
    public function it_can_reject_approval_request()
    {
        $approvalRequest = ApprovalRequest::factory()->create([
            'company_id' => $this->company->id,
            'requested_by' => $this->user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->approver)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/{$this->company->id}/approvals/{$approvalRequest->id}/reject", [
                'note' => 'Missing information',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('approval_requests', [
            'id' => $approvalRequest->id,
            'status' => ApprovalRequest::STATUS_REJECTED,
            'approved_by' => $this->approver->id,
        ]);
    }

    /** @test */
    public function it_prevents_user_from_approving_own_request()
    {
        $approvalRequest = ApprovalRequest::factory()->create([
            'company_id' => $this->company->id,
            'requested_by' => $this->user->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/{$this->company->id}/approvals/{$approvalRequest->id}/approve");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_cannot_approve_already_processed_request()
    {
        $approvalRequest = ApprovalRequest::factory()->create([
            'company_id' => $this->company->id,
            'status' => ApprovalRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->approver)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/{$this->company->id}/approvals/{$approvalRequest->id}/approve");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_can_list_pending_approvals()
    {
        ApprovalRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);

        ApprovalRequest::factory()->create([
            'company_id' => $this->company->id,
            'status' => ApprovalRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/{$this->company->id}/approvals/pending");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    /** @test */
    public function it_can_get_approval_history_for_document()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        ApprovalRequest::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'approvable_type' => Invoice::class,
            'approvable_id' => $invoice->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/{$this->company->id}/approvals/document/invoice/{$invoice->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function it_blocks_sending_unapproved_documents()
    {
        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Enable approvals for testing
        // Note: This would need company setting to be configured

        $approvalRequest = $invoice->requestApproval();

        $this->assertFalse($invoice->canBeSent());

        $approvalRequest->approve($this->approver->id);

        $this->assertTrue($invoice->fresh()->canBeSent());
    }

    /** @test */
    public function it_can_get_approval_statistics()
    {
        ApprovalRequest::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'status' => ApprovalRequest::STATUS_PENDING,
        ]);

        ApprovalRequest::factory()->create([
            'company_id' => $this->company->id,
            'status' => ApprovalRequest::STATUS_APPROVED,
        ]);

        ApprovalRequest::factory()->create([
            'company_id' => $this->company->id,
            'status' => ApprovalRequest::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/{$this->company->id}/approvals/stats");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'pending' => 2,
                    'approved' => 1,
                    'rejected' => 1,
                    'total' => 4,
                ],
            ]);
    }
}

// CLAUDE-CHECKPOINT
