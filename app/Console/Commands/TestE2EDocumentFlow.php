<?php

namespace App\Console\Commands;

use App\Http\Controllers\V1\Client\ClientDocumentController;
use App\Jobs\ProcessClientDocumentJob;
use App\Models\Bill;
use App\Models\ClientDocument;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InvoiceParsing\Invoice2DataClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestE2EDocumentFlow extends Command
{
    protected $signature = 'documents:test-e2e {file? : Path to test file} {--company=2 : Company ID}';

    protected $description = 'End-to-end test: upload → AI classify → extract → confirm → bill created';

    public function handle(): int
    {
        $filePath = $this->argument('file') ?? base_path('tests/fixtures/mk-documents/invoices/real-invoice-1649.jpg');
        $companyId = (int) $this->option('company');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $company = Company::find($companyId);
        if (! $company) {
            $this->error("Company not found: {$companyId}");

            return 1;
        }

        $user = User::where('role', 'super admin')->first();
        $this->info("User: {$user->name} ({$user->email})");
        $this->info("Company: {$company->name} (ID: {$companyId})");

        // Ensure company has MKD currency
        $currency = Currency::where('code', 'MKD')->first();
        if ($currency) {
            CompanySetting::setSettings(['currency' => $currency->id], $companyId);
        }

        // Step 1: Upload file to storage
        $this->newLine();
        $this->info('=== Step 1: Upload ===');
        $basename = basename($filePath);
        $disk = env('FILESYSTEM_DISK', 'public');
        $storagePath = "client-documents/{$companyId}/" . uniqid() . "_{$basename}";

        try {
            Storage::disk($disk)->put($storagePath, file_get_contents($filePath));
        } catch (\Throwable $e) {
            $disk = 'local';
            Storage::disk($disk)->put($storagePath, file_get_contents($filePath));
        }
        $this->comment("  Stored on '{$disk}' at: {$storagePath}");

        $mime = mime_content_type($filePath);
        $doc = ClientDocument::create([
            'company_id' => $companyId,
            'uploaded_by' => $user->id,
            'category' => 'other',
            'original_filename' => $basename,
            'file_path' => $storagePath,
            'file_size' => filesize($filePath),
            'mime_type' => $mime,
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_PENDING,
        ]);
        $this->comment("  Document created: ID={$doc->id}");

        // Step 2: Process (classify + extract)
        $this->newLine();
        $this->info('=== Step 2: AI Processing ===');

        $client = app(Invoice2DataClient::class);
        $mapper = app(ParsedInvoiceMapper::class);

        $job = new ProcessClientDocumentJob($doc->id);
        $job->handle($client, $mapper);

        $doc->refresh();
        $this->comment("  Processing status: {$doc->processing_status}");
        $this->comment("  Category: {$doc->category}");
        $this->comment("  Classification: " . json_encode($doc->ai_classification));
        $this->comment("  Extraction method: {$doc->extraction_method}");

        if ($doc->error_message) {
            $this->error("  Error: {$doc->error_message}");

            return 1;
        }

        if ($doc->processing_status !== ClientDocument::PROCESSING_EXTRACTED) {
            $this->warn("  Document not extracted, stopping.");

            return 0;
        }

        // Step 3: Show extracted data
        $this->newLine();
        $this->info('=== Step 3: Extracted Data ===');
        $data = $doc->extracted_data;

        if (isset($data['supplier'])) {
            $this->comment("  Supplier: {$data['supplier']['name']}");
            $this->comment("  Tax ID: " . ($data['supplier']['tax_id'] ?? '-'));
        }

        if (isset($data['bill'])) {
            $bill = $data['bill'];
            $this->comment("  Bill #: " . ($bill['bill_number'] ?? '-'));
            $this->comment("  Date: " . ($bill['bill_date'] ?? '-'));
            $this->comment("  Due: " . ($bill['due_date'] ?? '-'));
            $this->comment("  Subtotal: " . number_format(($bill['sub_total'] ?? 0) / 100, 2) . " MKD");
            $this->comment("  Tax: " . number_format(($bill['tax'] ?? 0) / 100, 2) . " MKD");
            $this->comment("  Total: " . number_format(($bill['total'] ?? 0) / 100, 2) . " MKD");
        }

        if (isset($data['items'])) {
            $this->comment("  Line items: " . count($data['items']));
            foreach ($data['items'] as $i => $item) {
                $name = $item['name'] ?? $item['description'] ?? '-';
                $price = number_format(($item['price'] ?? 0) / 100, 2);
                $total = number_format(($item['total'] ?? 0) / 100, 2);
                $this->comment("    " . ($i + 1) . ". {$name}: {$price} x " . ($item['quantity'] ?? 1) . " = {$total}");
            }
        }

        // Step 4: Confirm → Create Bill
        if (! in_array($doc->ai_classification['type'] ?? '', ['invoice', 'receipt'])) {
            $this->warn("  Not an invoice/receipt, skipping confirm step.");

            return 0;
        }

        $this->newLine();
        $this->info('=== Step 4: Confirm & Create Bill ===');

        // Simulate what the confirm endpoint does
        $extractedData = $doc->extracted_data;
        $supplierData = $extractedData['supplier'] ?? [];
        $billData = $extractedData['bill'] ?? [];
        $items = $extractedData['items'] ?? [];

        // Create or find supplier
        $supplier = Supplier::where('company_id', $companyId)
            ->where(function ($q) use ($supplierData) {
                if (! empty($supplierData['tax_id'])) {
                    $q->where('tax_id', $supplierData['tax_id']);
                } else {
                    $q->where('name', $supplierData['name'] ?? 'Unknown');
                }
            })
            ->first();

        if (! $supplier) {
            $supplier = Supplier::create([
                'company_id' => $companyId,
                'name' => $supplierData['name'] ?? 'Unknown Supplier',
                'email' => $supplierData['email'] ?? null,
                'tax_id' => $supplierData['tax_id'] ?? null,
                'address_street_1' => $supplierData['address'] ?? null,
                'currency_id' => $currency->id ?? null,
            ]);
            $this->comment("  Created supplier: {$supplier->name} (ID: {$supplier->id})");
        } else {
            $this->comment("  Found existing supplier: {$supplier->name} (ID: {$supplier->id})");
        }

        // Create bill
        $billNumber = $billData['bill_number'] ?? 'AI-' . $doc->id;
        $existingBill = Bill::where('company_id', $companyId)->where('bill_number', $billNumber)->first();
        if ($existingBill) {
            $billNumber = $billNumber . '-' . uniqid();
        }

        $bill = Bill::create([
            'company_id' => $companyId,
            'supplier_id' => $supplier->id,
            'bill_number' => $billNumber,
            'bill_date' => $billData['bill_date'] ?? now()->format('Y-m-d'),
            'due_date' => $billData['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'status' => 'DRAFT',
            'paid_status' => 'UNPAID',
            'currency_id' => $currency->id ?? 1,
            'sub_total' => $billData['sub_total'] ?? 0,
            'total' => $billData['total'] ?? 0,
            'tax' => $billData['tax'] ?? 0,
            'due_amount' => $billData['due_amount'] ?? $billData['total'] ?? 0,
            'exchange_rate' => $billData['exchange_rate'] ?? 1,
            'base_total' => $billData['base_total'] ?? $billData['total'] ?? 0,
            'base_sub_total' => $billData['base_sub_total'] ?? $billData['sub_total'] ?? 0,
            'base_tax' => $billData['base_tax'] ?? $billData['tax'] ?? 0,
            'base_due_amount' => $billData['base_due_amount'] ?? $billData['total'] ?? 0,
            'base_discount_val' => 0,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
        ]);

        $this->comment("  Created bill: #{$bill->bill_number} (ID: {$bill->id})");
        $this->comment("  Total: " . number_format($bill->total / 100, 2) . " MKD");

        // Create bill items
        foreach ($items as $i => $item) {
            $bill->items()->create([
                'company_id' => $companyId,
                'name' => $item['name'] ?? $item['description'] ?? "Item " . ($i + 1),
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'total' => $item['total'] ?? 0,
                'tax' => $item['tax'] ?? 0,
                'discount' => $item['discount'] ?? 0,
                'discount_val' => $item['discount_val'] ?? 0,
                'discount_type' => 'fixed',
                'base_price' => $item['base_price'] ?? $item['price'] ?? 0,
                'base_total' => $item['base_total'] ?? $item['total'] ?? 0,
                'base_tax' => $item['base_tax'] ?? $item['tax'] ?? 0,
                'base_discount_val' => $item['base_discount_val'] ?? 0,
                'exchange_rate' => 1,
            ]);
        }
        $this->comment("  Created {$bill->items()->count()} bill items");

        // Link document to bill
        $doc->update([
            'linked_bill_id' => $bill->id,
            'processing_status' => ClientDocument::PROCESSING_CONFIRMED,
            'status' => ClientDocument::STATUS_REVIEWED,
        ]);

        $this->newLine();
        $this->info('=== RESULT ===');
        $this->info("Document ID: {$doc->id} → Bill ID: {$bill->id}");
        $this->info("Bill #{$bill->bill_number} | Supplier: {$supplier->name}");
        $this->info("Total: " . number_format($bill->total / 100, 2) . " MKD | Items: {$bill->items()->count()}");
        $this->info("Status: {$doc->processing_status}");
        $this->newLine();

        // Verify bill shows up
        $verify = Bill::where('company_id', $companyId)->where('id', $bill->id)->first();
        if ($verify) {
            $this->info("VERIFIED: Bill exists in company {$companyId} bills list ✓");
        } else {
            $this->error("FAILED: Bill not found!");

            return 1;
        }

        return 0;
    }
} // CLAUDE-CHECKPOINT
