<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Reconciliation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ReconciliationTest
 *
 * Tests for bank transaction reconciliation with confidence scoring
 */
class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::factory()->create();
        $this->user->companies()->attach($this->company->id);
    }

    /** @test */
    public function it_can_list_auto_matched_reconciliations()
    {
        // Create auto-matched reconciliation
        $reconciliation = Reconciliation::factory()->create([
            'company_id' => $this->company->id,
            'confidence_score' => 0.95,
            'status' => Reconciliation::STATUS_AUTO_MATCHED,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/{$this->company->id}/reconciliation/auto-matched");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [],
                ],
                'threshold',
            ]);
    }

    /** @test */
    public function it_can_list_suggested_reconciliations()
    {
        // Create suggested reconciliation
        $reconciliation = Reconciliation::factory()->create([
            'company_id' => $this->company->id,
            'confidence_score' => 0.75,
            'status' => Reconciliation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/{$this->company->id}/reconciliation/suggested");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [],
                ],
            ]);
    }

    /** @test */
    public function it_can_approve_suggested_reconciliation()
    {
        $reconciliation = Reconciliation::factory()->create([
            'company_id' => $this->company->id,
            'confidence_score' => 0.75,
            'status' => Reconciliation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/{$this->company->id}/reconciliation/approve", [
                'reconciliation_id' => $reconciliation->id,
                'notes' => 'Approved after manual review',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('reconciliations', [
            'id' => $reconciliation->id,
            'status' => Reconciliation::STATUS_APPROVED,
        ]);
    }

    /** @test */
    public function it_can_reject_suggested_reconciliation()
    {
        $reconciliation = Reconciliation::factory()->create([
            'company_id' => $this->company->id,
            'confidence_score' => 0.75,
            'status' => Reconciliation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/{$this->company->id}/reconciliation/reject", [
                'reconciliation_id' => $reconciliation->id,
                'reason' => 'Incorrect match',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('reconciliations', [
            'id' => $reconciliation->id,
            'status' => Reconciliation::STATUS_REJECTED,
        ]);
    }

    /** @test */
    public function it_cannot_approve_already_processed_reconciliation()
    {
        $reconciliation = Reconciliation::factory()->create([
            'company_id' => $this->company->id,
            'status' => Reconciliation::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->postJson("/api/v1/{$this->company->id}/reconciliation/approve", [
                'reconciliation_id' => $reconciliation->id,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_can_get_reconciliation_statistics()
    {
        // Create various reconciliations
        Reconciliation::factory()->create([
            'company_id' => $this->company->id,
            'confidence_score' => 0.95,
            'status' => Reconciliation::STATUS_AUTO_MATCHED,
        ]);

        Reconciliation::factory()->create([
            'company_id' => $this->company->id,
            'confidence_score' => 0.75,
            'status' => Reconciliation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson("/api/v1/{$this->company->id}/reconciliation/stats");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'auto_matched',
                    'suggested',
                    'manual',
                    'approved',
                    'rejected',
                    'total',
                ],
            ]);
    }
}

// CLAUDE-CHECKPOINT
