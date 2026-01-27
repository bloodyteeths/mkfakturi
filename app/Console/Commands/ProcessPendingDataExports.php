<?php

namespace App\Console\Commands;

use App\Jobs\ExportUserDataJob;
use App\Models\UserDataExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPendingDataExports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exports:process-pending';

    /**
     * The console command description.
     */
    protected $description = 'Process pending GDPR data exports';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Find pending exports (not yet started)
        $pendingExports = UserDataExport::where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit(5) // Process max 5 at a time
            ->get();

        if ($pendingExports->isEmpty()) {
            $this->info('No pending exports to process.');

            return Command::SUCCESS;
        }

        $this->info("Found {$pendingExports->count()} pending export(s) to process.");

        foreach ($pendingExports as $export) {
            $this->info("Processing export #{$export->id} for user #{$export->user_id}...");

            try {
                Log::info('Processing pending data export via command', [
                    'export_id' => $export->id,
                    'user_id' => $export->user_id,
                ]);

                // Run the export job synchronously
                $job = new ExportUserDataJob($export);
                $job->handle();

                $this->info("Export #{$export->id} completed successfully.");
            } catch (\Exception $e) {
                $this->error("Export #{$export->id} failed: ".$e->getMessage());

                Log::error('Data export command failed', [
                    'export_id' => $export->id,
                    'error' => $e->getMessage(),
                ]);

                // Mark as failed if not already done by the job
                if ($export->status !== 'failed') {
                    $export->markAsFailed($e->getMessage());
                }
            }
        }

        // Also reset any stuck exports (processing for more than 30 minutes)
        $stuckExports = UserDataExport::stuck(30)->get();
        foreach ($stuckExports as $export) {
            $this->warn("Resetting stuck export #{$export->id}");
            $export->resetIfStuck(30);
        }

        return Command::SUCCESS;
    }
}
