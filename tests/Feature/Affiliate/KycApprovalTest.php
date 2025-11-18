<?php

namespace Tests\Feature\Affiliate;

use App\Models\KycDocument;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\KycStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
    }

    /** @test */
    public function partner_can_upload_kyc_documents()
    {
        $user = User::factory()->create();
        $partner = Partner::factory()->create(['user_id' => $user->id]);

        $idCard = UploadedFile::fake()->create('id_card.pdf', 1000);
        $proofOfAddress = UploadedFile::fake()->create('utility_bill.pdf', 1000);

        $response = $this->actingAs($user)->postJson('/api/v1/partner/kyc/submit', [
            'documents' => [
                ['type' => 'id_card', 'file' => $idCard],
                ['type' => 'proof_of_address', 'file' => $proofOfAddress],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('kyc_documents', [
            'partner_id' => $partner->id,
            'document_type' => 'id_card',
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('kyc_documents', [
            'partner_id' => $partner->id,
            'document_type' => 'proof_of_address',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function admin_can_approve_kyc_document()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $partner = Partner::factory()->create(['kyc_status' => 'pending']);

        // Create required documents
        $idDoc = KycDocument::factory()->create([
            'partner_id' => $partner->id,
            'document_type' => 'id_card',
            'status' => 'pending',
        ]);

        $addressDoc = KycDocument::factory()->create([
            'partner_id' => $partner->id,
            'document_type' => 'proof_of_address',
            'status' => 'pending',
        ]);

        // Approve both documents
        $response = $this->actingAs($adminUser)->postJson("/api/v1/admin/kyc/{$idDoc->id}/approve");
        $response->assertStatus(200);

        $this->assertDatabaseHas('kyc_documents', [
            'id' => $idDoc->id,
            'status' => 'approved',
            'verified_by' => $adminUser->id,
        ]);

        // Approve second document - partner KYC should be verified
        $response = $this->actingAs($adminUser)->postJson("/api/v1/admin/kyc/{$addressDoc->id}/approve");
        $response->assertStatus(200);

        $partner->refresh();
        $this->assertEquals('verified', $partner->kyc_status);

        // Verify notification sent
        Notification::assertSentTo(
            [$partner->user],
            KycStatusChanged::class
        );
    }

    /** @test */
    public function admin_can_reject_kyc_document()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $partner = Partner::factory()->create(['kyc_status' => 'pending']);

        $document = KycDocument::factory()->create([
            'partner_id' => $partner->id,
            'document_type' => 'id_card',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($adminUser)->postJson("/api/v1/admin/kyc/{$document->id}/reject", [
            'reason' => 'Document is blurry and unreadable',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('kyc_documents', [
            'id' => $document->id,
            'status' => 'rejected',
            'verified_by' => $adminUser->id,
            'rejection_reason' => 'Document is blurry and unreadable',
        ]);

        $partner->refresh();
        $this->assertEquals('rejected', $partner->kyc_status);

        // Verify notification sent
        Notification::assertSentTo(
            [$partner->user],
            KycStatusChanged::class
        );
    }

    /** @test */
    public function partner_kyc_status_becomes_verified_only_after_all_required_docs_approved()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);
        $partner = Partner::factory()->create(['kyc_status' => 'pending']);

        $idDoc = KycDocument::factory()->create([
            'partner_id' => $partner->id,
            'document_type' => 'id_card',
            'status' => 'pending',
        ]);

        $addressDoc = KycDocument::factory()->create([
            'partner_id' => $partner->id,
            'document_type' => 'proof_of_address',
            'status' => 'pending',
        ]);

        // Approve only ID card
        $this->actingAs($adminUser)->postJson("/api/v1/admin/kyc/{$idDoc->id}/approve");

        $partner->refresh();
        $this->assertEquals('pending', $partner->kyc_status); // Still pending

        // Approve proof of address
        $this->actingAs($adminUser)->postJson("/api/v1/admin/kyc/{$addressDoc->id}/approve");

        $partner->refresh();
        $this->assertEquals('verified', $partner->kyc_status); // Now verified
    }

    /** @test */
    public function partner_cannot_delete_approved_kyc_document()
    {
        $user = User::factory()->create();
        $partner = Partner::factory()->create(['user_id' => $user->id]);

        $document = KycDocument::factory()->create([
            'partner_id' => $partner->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/partner/kyc/documents/{$document->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('kyc_documents', [
            'id' => $document->id,
        ]);
    }

    /** @test */
    public function partner_can_delete_pending_or_rejected_documents()
    {
        $user = User::factory()->create();
        $partner = Partner::factory()->create(['user_id' => $user->id]);

        $pendingDoc = KycDocument::factory()->create([
            'partner_id' => $partner->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->deleteJson("/api/v1/partner/kyc/documents/{$pendingDoc->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('kyc_documents', [
            'id' => $pendingDoc->id,
        ]);
    }

    /** @test */
    public function kyc_submission_requires_both_id_and_proof_of_address()
    {
        $user = User::factory()->create();
        $partner = Partner::factory()->create(['user_id' => $user->id]);

        $idCard = UploadedFile::fake()->create('id_card.pdf', 1000);

        // Only ID card, missing proof of address
        $response = $this->actingAs($user)->postJson('/api/v1/partner/kyc/submit', [
            'documents' => [
                ['type' => 'id_card', 'file' => $idCard],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Missing required document: proof_of_address',
        ]);
    }

    /** @test */
    public function kyc_document_file_size_limited_to_5mb()
    {
        $user = User::factory()->create();
        $partner = Partner::factory()->create(['user_id' => $user->id]);

        $largeFile = UploadedFile::fake()->create('large_id_card.pdf', 6000); // 6MB

        $response = $this->actingAs($user)->postJson('/api/v1/partner/kyc/submit', [
            'documents' => [
                ['type' => 'id_card', 'file' => $largeFile],
            ],
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function admin_can_view_pending_kyc_documents()
    {
        $adminUser = User::factory()->create(['role' => 'admin']);

        KycDocument::factory()->count(5)->create(['status' => 'pending']);
        KycDocument::factory()->count(3)->create(['status' => 'approved']);

        $response = $this->actingAs($adminUser)->getJson('/api/v1/admin/kyc/pending');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'documents');
    }
}

// CLAUDE-CHECKPOINT
