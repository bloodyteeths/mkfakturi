<?php

use App\Models\ClientDocument;
use App\Models\Company;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->company = $user->companies()->first();
    $this->user = $user;
    $this->withHeaders([
        'company' => $this->company->id,
    ]);
    Sanctum::actingAs($user, ['*']);

    Storage::fake('local');
});

// Helper: create a partner with access to the test company
function createPartnerWithAccess($company): array
{
    $partnerUser = User::factory()->create([
        'role' => 'partner',
    ]);

    $partner = Partner::create([
        'name' => 'Test Partner',
        'email' => $partnerUser->email,
        'user_id' => $partnerUser->id,
        'is_active' => true,
        'commission_rate' => 20.00,
        'kyc_status' => 'verified',
    ]);

    // Create partner-company link
    \DB::table('partner_company_links')->insert([
        'partner_id' => $partner->id,
        'company_id' => $company->id,
        'is_primary' => true,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return ['user' => $partnerUser, 'partner' => $partner];
}

// Helper: create a client document
function createDocument($companyId, $userId, $status = 'pending_review', $partnerId = null): ClientDocument
{
    return ClientDocument::create([
        'company_id' => $companyId,
        'uploaded_by' => $userId,
        'partner_id' => $partnerId,
        'category' => 'invoice',
        'original_filename' => 'test-invoice.pdf',
        'file_path' => "client-documents/{$companyId}/2026-02/test.pdf",
        'file_size' => 1024,
        'mime_type' => 'application/pdf',
        'status' => $status,
        'notes' => 'Test document',
    ]);
}

describe('Client Document Upload', function () {
    test('client can upload document', function () {
        $file = UploadedFile::fake()->create('invoice.pdf', 1024, 'application/pdf');

        $response = postJson('api/v1/client-documents/upload', [
            'file' => $file,
            'category' => 'invoice',
            'notes' => 'Monthly invoice',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'company_id',
                'uploaded_by',
                'category',
                'original_filename',
                'file_size',
                'mime_type',
                'status',
            ],
        ]);

        expect($response->json('data.status'))->toBe('pending_review');
        expect($response->json('data.category'))->toBe('invoice');
        expect($response->json('data.company_id'))->toBe($this->company->id);

        // Verify record was created in DB
        $this->assertDatabaseHas('client_documents', [
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'invoice',
            'status' => 'pending_review',
        ]);
    });

    test('upload rejects oversized file', function () {
        // Create a file slightly over 10MB
        $file = UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf');

        $response = postJson('api/v1/client-documents/upload', [
            'file' => $file,
            'category' => 'invoice',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    });

    test('upload rejects invalid mimetype', function () {
        $file = UploadedFile::fake()->create('script.php', 100, 'application/x-httpd-php');

        $response = postJson('api/v1/client-documents/upload', [
            'file' => $file,
            'category' => 'other',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    });
});

describe('Client Document Listing', function () {
    test('client can list own documents', function () {
        // Create some documents
        createDocument($this->company->id, $this->user->id);
        createDocument($this->company->id, $this->user->id, 'reviewed');
        createDocument($this->company->id, $this->user->id, 'rejected');

        $response = getJson('api/v1/client-documents');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data',
            'current_page',
            'last_page',
            'total',
        ]);

        expect($response->json('total'))->toBe(3);
    });
});

describe('Client Document Deletion', function () {
    test('client can delete pending document', function () {
        $doc = createDocument($this->company->id, $this->user->id, 'pending_review');

        // Create the file in storage
        Storage::put($doc->file_path, 'fake content');

        $response = deleteJson("api/v1/client-documents/{$doc->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);

        // Verify soft delete
        $this->assertSoftDeleted('client_documents', ['id' => $doc->id]);
    });

    test('client cannot delete reviewed document', function () {
        $doc = createDocument($this->company->id, $this->user->id, 'reviewed');

        $response = deleteJson("api/v1/client-documents/{$doc->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Only pending documents can be deleted.',
        ]);

        // Verify not deleted
        $this->assertDatabaseHas('client_documents', [
            'id' => $doc->id,
            'deleted_at' => null,
        ]);
    });
});

describe('Partner Document Review', function () {
    test('partner can review document', function () {
        $partnerData = createPartnerWithAccess($this->company);
        $doc = createDocument($this->company->id, $this->user->id, 'pending_review', $partnerData['partner']->id);

        // Act as partner user
        Sanctum::actingAs($partnerData['user'], ['*']);

        $response = postJson("api/v1/partner/companies/{$this->company->id}/documents/{$doc->id}/review", [
            'notes' => 'Looks good',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Document marked as reviewed.',
        ]);

        // Verify status change
        $doc->refresh();
        expect($doc->status)->toBe('reviewed');
        expect($doc->reviewer_id)->toBe($partnerData['user']->id);
        expect($doc->reviewed_at)->not->toBeNull();
    });

    test('partner can reject document with reason', function () {
        $partnerData = createPartnerWithAccess($this->company);
        $doc = createDocument($this->company->id, $this->user->id, 'pending_review', $partnerData['partner']->id);

        Sanctum::actingAs($partnerData['user'], ['*']);

        $response = postJson("api/v1/partner/companies/{$this->company->id}/documents/{$doc->id}/reject", [
            'reason' => 'Image is blurry, please re-upload',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Document has been rejected.',
        ]);

        $doc->refresh();
        expect($doc->status)->toBe('rejected');
        expect($doc->rejection_reason)->toBe('Image is blurry, please re-upload');
        expect($doc->reviewer_id)->toBe($partnerData['user']->id);
    });

    test('partner cannot access unmanaged company docs', function () {
        $partnerData = createPartnerWithAccess($this->company);

        // Create a second company that this partner does NOT manage
        $otherCompany = Company::factory()->create();

        $doc = createDocument($otherCompany->id, $this->user->id, 'pending_review');

        Sanctum::actingAs($partnerData['user'], ['*']);

        $response = getJson("api/v1/partner/companies/{$otherCompany->id}/documents");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'You do not have access to this company.',
        ]);
    });
});

// CLAUDE-CHECKPOINT
