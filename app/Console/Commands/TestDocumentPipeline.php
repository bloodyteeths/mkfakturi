<?php

namespace App\Console\Commands;

use App\Services\InvoiceParsing\Invoice2DataClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestDocumentPipeline extends Command
{
    protected $signature = 'documents:test-pipeline {path? : Directory with test PDFs/images}';

    protected $description = 'Test the AI document classification + extraction pipeline against a directory of files';

    public function handle(): int
    {
        $path = $this->argument('path') ?? base_path('tests/fixtures/mk-documents');

        if (! is_dir($path)) {
            $this->error("Directory not found: {$path}");

            return 1;
        }

        $files = $this->collectFiles($path);

        if (empty($files)) {
            $this->warn('No PDF/image files found.');

            return 0;
        }

        $this->info("Found ".count($files)." files in {$path}");
        $this->newLine();

        /** @var Invoice2DataClient $client */
        $client = app(Invoice2DataClient::class);
        $mapper = app(ParsedInvoiceMapper::class);

        $headers = ['File', 'Type', 'Conf', 'Supplier', 'Total', 'Items', 'Method'];
        $rows = [];

        foreach ($files as $file) {
            $basename = basename($file);
            $this->info("Processing: {$basename}");

            // Step 1: Store temporarily
            $disk = env('FILESYSTEM_DISK', 'public');
            $tempPath = 'test-pipeline/'.uniqid().'_'.$basename;

            try {
                Storage::disk($disk)->put($tempPath, file_get_contents($file));
            } catch (\Throwable $e) {
                // Fallback to local disk
                $disk = 'local';
                Storage::disk($disk)->put($tempPath, file_get_contents($file));
            }

            // Step 2: Classify
            $classification = null;

            try {
                $classification = $client->classify(0, $tempPath, $basename);
                $this->comment("  Classified: {$classification['type']} ({$classification['confidence']})");
            } catch (\Throwable $e) {
                $this->warn("  Classification failed: {$e->getMessage()}");
            }

            // Step 3: Extract (for invoices/receipts)
            $supplier = '-';
            $total = '-';
            $itemCount = '-';
            $method = $classification ? 'classification' : 'none';

            $type = $classification['type'] ?? 'other';

            if (in_array($type, ['invoice', 'receipt'])) {
                try {
                    $parsed = $client->parse(0, $tempPath, $basename, '', null);
                    $components = $mapper->mapToBillComponents(0, $parsed);

                    $supplier = $components['supplier']['name'] ?? '-';
                    $rawTotal = $components['bill']['total'] ?? 0;
                    $total = number_format($rawTotal / 100, 2);
                    $itemCount = count($components['items'] ?? []);
                    $method = 'gemini_vision';

                    $this->comment("  Extracted: {$supplier} | {$total} | {$itemCount} items");
                } catch (\Throwable $e) {
                    $this->warn("  Extraction failed: {$e->getMessage()}");
                    $method = 'failed';
                }
            }

            $rows[] = [
                $basename,
                $type,
                $classification ? number_format($classification['confidence'], 2) : '-',
                mb_substr($supplier, 0, 20),
                $total,
                $itemCount,
                $method,
            ];

            // Cleanup
            try {
                Storage::disk($disk)->delete($tempPath);
            } catch (\Throwable) {
                // ignore
            }
        }

        $this->newLine();
        $this->table($headers, $rows);

        return 0;
    }

    /**
     * Collect all PDF/image files recursively from a directory.
     */
    private function collectFiles(string $path): array
    {
        $files = [];
        $extensions = ['pdf', 'png', 'jpg', 'jpeg'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && in_array(strtolower($file->getExtension()), $extensions)) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
} // CLAUDE-CHECKPOINT
