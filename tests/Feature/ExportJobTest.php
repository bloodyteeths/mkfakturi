<?php

namespace Tests\Feature;

use App\Jobs\ProcessExportJob;
use App\Models\Company;
use App\Models\ExportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_export_job(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson("/api/v1/admin/{$company->id}/exports", [
                'type' => 'invoices',
                'format' => 'csv',
                'params' => [
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                ],
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['export_job', 'message']);

        $this->assertDatabaseHas('export_jobs', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => 'invoices',
            'format' => 'csv',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessExportJob::class);
    }

    public function test_list_export_jobs(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        ExportJob::factory()->count(3)->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/admin/{$company->id}/exports");

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_cannot_download_other_users_export(): void
    {
        $company = Company::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $exportJob = ExportJob::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user1->id,
            'status' => 'completed',
            'file_path' => 'exports/test.csv',
        ]);

        $response = $this->actingAs($user2)
            ->getJson("/api/v1/admin/{$company->id}/exports/{$exportJob->id}/download");

        $response->assertStatus(403);
    }
}
// CLAUDE-CHECKPOINT
