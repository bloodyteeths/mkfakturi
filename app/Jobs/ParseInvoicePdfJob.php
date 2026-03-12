<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Supplier;
use App\Notifications\InboundInvoiceNotification;
use App\Services\InvoiceParsing\Invoice2DataServiceException;
use App\Services\InvoiceParsing\InvoiceParserClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\RequestException;
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

    public string $contentType;

    public function __construct(int $companyId, string $filePath, string $originalName, string $from, ?string $subject, string $contentType = 'application/pdf')
    {
        $this->companyId = $companyId;
        $this->filePath = $filePath;
        $this->originalName = $originalName;
        $this->from = $from;
        $this->subject = $subject;
        $this->contentType = $contentType;
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
        Log::info('ParseInvoicePdfJob: starting', [
            'company_id' => $this->companyId,
            'file' => $this->originalName,
            'path' => $this->filePath,
            'attempt' => $this->attempts(),
        ]);

        $disk = env('FILESYSTEM_DISK', 'public');

        $parsed = null;

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

            $this->release($this->backoff[$this->attempts() - 1] ?? 300);

            return;
        } catch (RequestException $e) {
            $status = $e->response?->status();

            // Non-retryable errors (4xx/5xx that won't resolve with retries)
            if ($status && ($status === 422 || $status === 501 || $status >= 400 && $status < 500)) {
                Log::warning('ParseInvoicePdfJob: AI parsing failed, creating draft bill without parsed data', [
                    'company_id' => $this->companyId,
                    'file' => $this->originalName,
                    'status' => $status,
                    'error' => $e->getMessage(),
                ]);
                // Fall through with $parsed = null to create a draft bill with just the attachment
            } else {
                // Server error — retry
                Log::warning('ParseInvoicePdfJob: server error, will retry', [
                    'company_id' => $this->companyId,
                    'file' => $this->originalName,
                    'status' => $status,
                    'attempt' => $this->attempts(),
                ]);

                $this->release($this->backoff[$this->attempts() - 1] ?? 300);

                return;
            }
        } catch (\Throwable $e) {
            // Catch-all for unexpected exceptions (S3/R2 errors, filesystem issues, etc.)
            // Don't retry — create a draft bill with just the attachment
            Log::warning('ParseInvoicePdfJob: unexpected error during parsing, creating draft bill', [
                'company_id' => $this->companyId,
                'file' => $this->originalName,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            // Fall through with $parsed = null
        }

        $this->createBill($parsed, $mapper, $disk);
    }

    /**
     * Create the bill from parsed data (or fallback draft).
     *
     * Separated so the entire bill creation is wrapped in error handling.
     */
    protected function createBill(?array $parsed, ParsedInvoiceMapper $mapper, string $disk): void
    {
        if ($parsed) {
            try {
                $components = $mapper->mapToBillComponents($this->companyId, $parsed);
                $supplierData = $components['supplier'];
                $billData = $components['bill'];
                $items = $components['items'];
            } catch (\Throwable $e) {
                Log::warning('ParseInvoicePdfJob: mapper failed, falling back to draft bill', [
                    'company_id' => $this->companyId,
                    'file' => $this->originalName,
                    'error' => $e->getMessage(),
                ]);
                $parsed = null; // Fall through to draft creation below
            }
        }

        if (! $parsed) {
            // Fallback: create a minimal draft bill when AI parsing fails
            $supplierData = [
                'name' => $this->from,
                'email' => $this->from,
                'tax_id' => null,
            ];
            $currencyId = CompanySetting::getSetting('currency', $this->companyId);
            $billData = [
                'company_id' => $this->companyId,
                'bill_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays(30)->format('Y-m-d'),
                'bill_number' => 'INBOUND-'.strtoupper(substr(md5($this->filePath), 0, 8)),
                'status' => 'DRAFT',
                'paid_status' => 'UNPAID',
                'currency_id' => $currencyId,
                'exchange_rate' => 1,
                'sub_total' => 0,
                'total' => 0,
                'tax' => 0,
                'due_amount' => 0,
                'base_total' => 0,
                'base_sub_total' => 0,
                'base_tax' => 0,
                'base_due_amount' => 0,
                'base_discount_val' => 0,
                'discount' => 0,
                'discount_val' => 0,
                'notes' => "Auto-created from email: {$this->subject}\nFrom: {$this->from}\nFile: {$this->originalName}",
            ];
            $items = [];
        }

        // Ensure supplier name is never null (DB constraint: NOT NULL)
        $supplierName = $supplierData['name'] ?? $this->from ?? 'Unknown Supplier';

        $supplier = Supplier::updateOrCreate(
            [
                'company_id' => $this->companyId,
                'tax_id' => $supplierData['tax_id'] ?? null,
                'name' => $supplierName,
            ],
            [
                'company_id' => $this->companyId,
                'name' => $supplierName,
                'tax_id' => $supplierData['tax_id'] ?? null,
                'email' => $supplierData['email'] ?? null,
            ]
        );

        $billData['supplier_id'] = $supplier->id;

        // Generate fallback bill number when AI didn't extract one
        if (empty($billData['bill_number'])) {
            $billData['bill_number'] = 'INBOUND-'.strtoupper(substr(md5($this->filePath), 0, 8));
        }

        // Ensure bill number uniqueness per company by suffixing on conflict
        $originalNumber = $billData['bill_number'];
        $counter = 1;
        while (Bill::where('company_id', $this->companyId)
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

        // Attach the original file as media
        try {
            if (Storage::disk($disk)->exists($this->filePath)) {
                $bill->addMediaFromDisk($this->filePath, $disk)
                    ->toMediaCollection('scanned_invoice');
            }
        } catch (\Throwable $e) {
            Log::warning('ParseInvoicePdfJob: failed to attach media', [
                'bill_id' => $bill->id,
                'disk' => $disk,
                'path' => $this->filePath,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('ParseInvoicePdfJob: bill created successfully', [
            'company_id' => $this->companyId,
            'bill_id' => $bill->id,
            'bill_number' => $bill->bill_number,
            'status' => $bill->status,
            'total' => $bill->total,
            'supplier_id' => $supplier->id,
            'parsed' => $parsed !== null,
        ]);

        // Notify company owner about the new inbound invoice
        try {
            $company = Company::find($this->companyId);
            if ($company?->owner) {
                $bill->load('supplier');
                $company->owner->notify(new InboundInvoiceNotification($bill));
            }
        } catch (\Throwable $e) {
            Log::warning('ParseInvoicePdfJob: failed to send notification', [
                'bill_id' => $bill->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
