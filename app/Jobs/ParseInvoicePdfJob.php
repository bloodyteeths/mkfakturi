<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\Supplier;
use App\Services\InvoiceParsing\Invoice2DataServiceException;
use App\Services\InvoiceParsing\InvoiceParserClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ParseInvoicePdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $companyId;

    public string $filePath;

    public string $originalName;

    public string $from;

    public ?string $subject;

    public function __construct(int $companyId, string $filePath, string $originalName, string $from, ?string $subject)
    {
        $this->companyId = $companyId;
        $this->filePath = $filePath;
        $this->originalName = $originalName;
        $this->from = $from;
        $this->subject = $subject;
    }

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before retrying after a failure.
     *
     * @var array<int,int>
     */
    public array $backoff = [30, 120, 300];

    public function handle(InvoiceParserClient $client, ParsedInvoiceMapper $mapper): void
    {
        $disk = env('FILESYSTEM_DISK', 'public');

        try {
            $parsed = $client->parse(
                $this->companyId,
                $this->filePath,
                $this->originalName,
                $this->from,
                $this->subject
            );
        } catch (Invoice2DataServiceException $e) {
            Log::warning('ParseInvoicePdfJob: invoice2data-service unavailable, will retry', [
                'company_id' => $this->companyId,
                'file' => $this->originalName,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Release back to queue with backoff so it retries automatically
            $this->release($this->backoff[$this->attempts() - 1] ?? 300);

            return;
        }

        $components = $mapper->mapToBillComponents($this->companyId, $parsed);

        $supplierData = $components['supplier'];
        $billData = $components['bill'];
        $items = $components['items'];

        $supplier = Supplier::updateOrCreate(
            [
                'company_id' => $this->companyId,
                'tax_id' => $supplierData['tax_id'] ?? null,
                'name' => $supplierData['name'] ?? null,
            ],
            [
                'company_id' => $this->companyId,
                'name' => $supplierData['name'] ?? null,
                'tax_id' => $supplierData['tax_id'] ?? null,
                'email' => $supplierData['email'] ?? null,
            ]
        );

        $billData['supplier_id'] = $supplier->id;

        // Ensure bill number uniqueness per company by suffixing on conflict
        $originalNumber = $billData['bill_number'] ?? null;
        $counter = 1;
        while ($billData['bill_number'] && Bill::where('company_id', $this->companyId)
            ->where('bill_number', $billData['bill_number'])
            ->exists()
        ) {
            $billData['bill_number'] = $originalNumber.'-'.$counter;
            $counter++;
        }

        $bill = Bill::create($billData);

        if (! empty($items)) {
            foreach ($items as $item) {
                $item['company_id'] = $this->companyId;
                $bill->items()->create($item);
            }
        }

        // Use addMediaFromDisk() to support S3/R2 storage (not just local filesystem)
        if (Storage::disk($disk)->exists($this->filePath)) {
            $bill->addMediaFromDisk($this->filePath, $disk)
                ->toMediaCollection('scanned_invoice');
        }

        Log::info('ParseInvoicePdfJob created bill from parsed invoice', [
            'company_id' => $this->companyId,
            'bill_id' => $bill->id,
            'supplier_id' => $supplier->id,
        ]);
    }
}
