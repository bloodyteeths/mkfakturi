<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\Supplier;
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

    public function handle(InvoiceParserClient $client, ParsedInvoiceMapper $mapper): void
    {
        $disk = config('filesystems.default', 'local');

        $parsed = $client->parse(
            $this->companyId,
            $this->filePath,
            $this->originalName,
            $this->from,
            $this->subject
        );

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

        $storedPath = Storage::disk($disk)->path($this->filePath);
        if (file_exists($storedPath)) {
            $bill->addMedia($storedPath)->preservingOriginal()->toMediaCollection('bills');
        }

        Log::info('ParseInvoicePdfJob created bill from parsed invoice', [
            'company_id' => $this->companyId,
            'bill_id' => $bill->id,
            'supplier_id' => $supplier->id,
        ]);
    }
}
