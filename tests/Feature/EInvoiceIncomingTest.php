<?php

namespace Tests\Feature;

use App\Jobs\PollEInvoiceInboxJob;
use App\Models\Company;
use App\Models\EInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * EInvoiceIncomingTest
 *
 * P7-02: Feature tests for the incoming e-invoice acceptance workflow.
 * Tests listing, accepting, rejecting, and polling for inbound e-invoices.
 */
class EInvoiceIncomingTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id);

        // Make user the owner so they pass authorization checks
        $this->user->role = 'super admin';
        $this->user->save();
    }

    /**
     * Create an inbound e-invoice for testing.
     */
    protected function createInboundEInvoice(array $overrides = []): EInvoice
    {
        return EInvoice::create(array_merge([
            'company_id' => $this->company->id,
            'direction' => 'inbound',
            'status' => EInvoice::STATUS_RECEIVED,
            'sender_vat_id' => 'MK4030006616729',
            'sender_name' => 'Test Supplier DOOEL',
            'portal_inbox_id' => 'INB-'.uniqid(),
            'received_at' => now(),
            'ubl_xml' => '<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2"><ID>TEST-001</ID></Invoice>',
        ], $overrides));
    }

    /** @test */
    public function test_can_list_incoming_einvoices()
    {
        // Create some inbound e-invoices
        $this->createInboundEInvoice(['sender_name' => 'Supplier A']);
        $this->createInboundEInvoice(['sender_name' => 'Supplier B']);

        // Create an outbound e-invoice (should NOT appear)
        EInvoice::create([
            'company_id' => $this->company->id,
            'direction' => 'outbound',
            'status' => EInvoice::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/e-invoices/incoming');

        $response->assertStatus(200);

        // Should only return inbound invoices
        $data = $response->json('data');
        $this->assertCount(2, $data);

        // All returned should be inbound
        foreach ($data as $item) {
            $this->assertEquals('inbound', $item['direction']);
        }
    }

    /** @test */
    public function test_can_accept_incoming_einvoice()
    {
        $eInvoice = $this->createInboundEInvoice();

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/e-invoices/incoming/{$eInvoice->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $eInvoice->refresh();
        $this->assertEquals(EInvoice::STATUS_ACCEPTED_INCOMING, $eInvoice->status);
        $this->assertEquals($this->user->id, $eInvoice->reviewed_by);
        $this->assertNotNull($eInvoice->reviewed_at);
    }

    /** @test */
    public function test_can_reject_incoming_einvoice_with_reason()
    {
        $eInvoice = $this->createInboundEInvoice();

        $reason = 'Invoice does not match our purchase order.';

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/e-invoices/incoming/{$eInvoice->id}/reject", [
                'rejection_reason' => $reason,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $eInvoice->refresh();
        $this->assertEquals(EInvoice::STATUS_REJECTED_INCOMING, $eInvoice->status);
        $this->assertEquals($reason, $eInvoice->rejection_reason);
        $this->assertEquals($this->user->id, $eInvoice->reviewed_by);
        $this->assertNotNull($eInvoice->reviewed_at);
    }

    /** @test */
    public function test_cannot_accept_already_accepted_einvoice()
    {
        $eInvoice = $this->createInboundEInvoice([
            'status' => EInvoice::STATUS_ACCEPTED_INCOMING,
            'reviewed_at' => now(),
            'reviewed_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/e-invoices/incoming/{$eInvoice->id}/accept");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Status should remain unchanged
        $eInvoice->refresh();
        $this->assertEquals(EInvoice::STATUS_ACCEPTED_INCOMING, $eInvoice->status);
    }

    /** @test */
    public function test_direction_filter_returns_only_inbound()
    {
        // Create one inbound
        $this->createInboundEInvoice(['sender_name' => 'Inbound Supplier']);

        // Create one outbound
        EInvoice::create([
            'company_id' => $this->company->id,
            'direction' => 'outbound',
            'status' => EInvoice::STATUS_DRAFT,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/e-invoices/incoming');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('inbound', $data[0]['direction']);
        $this->assertEquals('Inbound Supplier', $data[0]['sender_name']);
    }

    /** @test */
    public function test_poll_dispatches_job()
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson('/api/v1/e-invoices/incoming/poll');

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
            ]);

        Queue::assertPushedOn('einvoice', PollEInvoiceInboxJob::class);
    }

    /** @test */
    public function test_cannot_reject_without_reason()
    {
        $eInvoice = $this->createInboundEInvoice();

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/e-invoices/incoming/{$eInvoice->id}/reject", [
                'rejection_reason' => '',
            ]);

        $response->assertStatus(422);

        // Status should remain RECEIVED
        $eInvoice->refresh();
        $this->assertEquals(EInvoice::STATUS_RECEIVED, $eInvoice->status);
    }

    /** @test */
    public function test_can_show_incoming_einvoice_with_preview()
    {
        $eInvoice = $this->createInboundEInvoice();

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/e-invoices/incoming/{$eInvoice->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'ubl_preview',
                'submissions',
            ]);
    }

    /** @test */
    public function test_model_scopes_work_correctly()
    {
        $inbound = $this->createInboundEInvoice();

        $outbound = EInvoice::create([
            'company_id' => $this->company->id,
            'direction' => 'outbound',
            'status' => EInvoice::STATUS_DRAFT,
        ]);

        // Test inbound scope
        $inboundResults = EInvoice::inbound()->get();
        $this->assertTrue($inboundResults->contains($inbound));
        $this->assertFalse($inboundResults->contains($outbound));

        // Test outbound scope
        $outboundResults = EInvoice::outbound()->get();
        $this->assertFalse($outboundResults->contains($inbound));
        $this->assertTrue($outboundResults->contains($outbound));

        // Test pendingReview scope
        $pendingResults = EInvoice::pendingReview()->get();
        $this->assertTrue($pendingResults->contains($inbound));
        $this->assertFalse($pendingResults->contains($outbound));
    }

    /** @test */
    public function test_model_state_transitions()
    {
        $eInvoice = $this->createInboundEInvoice();

        // Test markUnderReview
        $result = $eInvoice->markUnderReview($this->user->id);
        $this->assertTrue($result);
        $this->assertEquals(EInvoice::STATUS_UNDER_REVIEW, $eInvoice->status);
        $this->assertEquals($this->user->id, $eInvoice->reviewed_by);

        // Test acceptIncoming from UNDER_REVIEW
        $result = $eInvoice->acceptIncoming();
        $this->assertTrue($result);
        $this->assertEquals(EInvoice::STATUS_ACCEPTED_INCOMING, $eInvoice->status);
        $this->assertNotNull($eInvoice->reviewed_at);

        // Test that accept fails on already accepted
        $result = $eInvoice->acceptIncoming();
        $this->assertFalse($result);
    }

    /** @test */
    public function test_model_reject_incoming_transition()
    {
        $eInvoice = $this->createInboundEInvoice();

        $reason = 'Duplicate invoice';
        $result = $eInvoice->rejectIncoming($reason);

        $this->assertTrue($result);
        $this->assertEquals(EInvoice::STATUS_REJECTED_INCOMING, $eInvoice->status);
        $this->assertEquals($reason, $eInvoice->rejection_reason);
        $this->assertNotNull($eInvoice->reviewed_at);

        // Test that reject fails on already rejected
        $result = $eInvoice->rejectIncoming('Another reason');
        $this->assertFalse($result);
    }
}

