<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * List all available backups
 *
 * This command displays all backup files with their size and creation date.
 * Useful for checking backup status and determining which backup to restore.
 */
class BackupList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available backups';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $disk = config('backup.backup.destination.disks')[0] ?? 'local';
        $backupName = config('backup.backup.name');

        $backupDirectory = $backupName;

        if (! Storage::disk($disk)->exists($backupDirectory)) {
            $this->info('No backups found.');
            return 0;
        }

        $backups = Storage::disk($disk)->files($backupDirectory);

        if (empty($backups)) {
            $this->info('No backups found.');
            return 0;
        }

        $this->info("Found " . count($backups) . " backup(s) on disk: {$disk}");
        $this->newLine();

        $backupData = collect($backups)->map(function ($backup) use ($disk) {
            return [
                basename($backup),
                $this->formatBytes(Storage::disk($disk)->size($backup)),
                date('Y-m-d H:i:s', Storage::disk($disk)->lastModified($backup)),
            ];
        })->sortByDesc(function ($backup) {
            return $backup[2]; // Sort by date, newest first
        })->toArray();

        $this->table(
            ['Filename', 'Size', 'Created'],
            $backupData
        );

        // Show storage usage
        $totalSize = collect($backups)->sum(fn($backup) => Storage::disk($disk)->size($backup));
        $this->newLine();
        $this->info("Total backup storage: " . $this->formatBytes($totalSize));

        return 0;
    }

    /**
     * Format bytes to human-readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
// CLAUDE-CHECKPOINT
