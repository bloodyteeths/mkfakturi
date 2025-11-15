<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReceiptScanRequest;
use App\Http\Resources\BillResource;
use App\Http\Resources\ExpenseResource;
use App\Models\Bill;
use App\Models\CompanySetting;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use App\Services\InvoiceParsing\InvoiceParserClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceiptScannerController extends Controller
{
    public function scan(
        ReceiptScanRequest $request,
        InvoiceParserClient $parserClient,
        ParsedInvoiceMapper $mapper,
    ): JsonResponse
    {
        try {
            \Log::info('ReceiptScannerController::scan - Starting', [
                'user_id' => auth()->id(),
                'company_id' => $request->header('company'),
                'has_file' => $request->hasFile('receipt'),
                'file_size' => $request->hasFile('receipt') ? $request->file('receipt')->getSize() : null,
                'file_mime' => $request->hasFile('receipt') ? $request->file('receipt')->getMimeType() : null,
            ]);

            $companyId = (int) $request->header('company');

            $file = $request->file('receipt');

            if (! $file) {
                \Log::error('ReceiptScannerController::scan - No file uploaded');

                return response()->json(['message' => 'No file uploaded'], 400);
            }

            \Log::info('ReceiptScannerController::scan - File details', [
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'size_kb' => round($file->getSize() / 1024, 2),
                'size_mb' => round($file->getSize() / 1024 / 1024, 2),
                'mime_type' => $file->getMimeType(),
            ]);

            // Authorize based on Bill creation, since we now always
            // create Bills from parsed images/PDFs.
            $this->authorize('create', Bill::class);

            $disk = config('filesystems.default', 'local');
            $storedPath = $file->store('scanned-receipts/'.$companyId, ['disk' => $disk]);

            // Directly use invoice parser microservice (PDF or image with OCR)
            $parsed = $parserClient->parse(
                $companyId,
                $storedPath,
                $file->getClientOriginalName(),
                'receipt-scan',
                null
            );

            $components = $mapper->mapToBillComponents($companyId, $parsed);

            $supplierData = $components['supplier'] ?? [];
            $billData = $components['bill'] ?? [];
            $items = $components['items'] ?? [];

            $supplier = Supplier::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'tax_id' => $supplierData['tax_id'] ?? null,
                    'name' => $supplierData['name'] ?? null,
                ],
                [
                    'company_id' => $companyId,
                    'name' => $supplierData['name'] ?? null,
                    'tax_id' => $supplierData['tax_id'] ?? null,
                    'email' => $supplierData['email'] ?? null,
                ]
            );

            $billData['supplier_id'] = $supplier->id;
            $billData['company_id'] = $companyId;

            // Ensure bill number uniqueness per company by suffixing on conflict
            $originalNumber = $billData['bill_number'] ?? null;
            $counter = 1;
            while ($billData['bill_number'] && Bill::where('company_id', $companyId)
                ->where('bill_number', $billData['bill_number'])
                ->exists()
            ) {
                $billData['bill_number'] = $originalNumber.'-'.$counter;
                $counter++;
            }

            $bill = Bill::create($billData);

            if (! empty($items)) {
                Bill::createItems($bill, $items);
            }

            $absolutePath = Storage::disk($disk)->path($storedPath);
            if (file_exists($absolutePath)) {
                $bill->addMedia($absolutePath)->preservingOriginal()->toMediaCollection('bills');
            }

            \Log::info('ReceiptScannerController::scan - Bill created via parser', [
                'bill_id' => $bill->id,
                'company_id' => $companyId,
                'supplier_id' => $supplier->id,
            ]);

            return (new BillResource($bill))
                ->additional(['document_type' => 'bill'])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            \Log::error('ReceiptScannerController::scan - Unhandled exception', [
                'user_id' => auth()->id(),
                'company_id' => $request->header('company'),
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            // Mirror into PHP error log so it appears in container logs
            error_log(sprintf(
                '[ReceiptScanner] Unhandled exception for company %s: %s (%s:%d)',
                $request->header('company'),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine()
            ));

            return response()->json([
                'message' => 'receipt_scan_failed',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function createExpenseFromReceipt(array $data, int $companyId, string $storedPath, Request $request): Expense
    {
        $companyCurrency = CompanySetting::getSetting('currency', $companyId);
        $amount = (int) ($data['total'] ?? 0);

        // Prefer an existing category for this company; if none exists,
        // create a lightweight default so the flow never fails.
        $category = ExpenseCategory::whereCompany($companyId)->first()
            ?? ExpenseCategory::where('company_id', $companyId)->first();

        if (! $category) {
            $category = ExpenseCategory::create([
                'name' => 'Scanned receipts',
                'company_id' => $companyId,
                'description' => 'Auto-created category for scanned fiscal receipts',
            ]);
        }

        $expense = Expense::create([
            'expense_date' => Carbon::parse($data['date_time'] ?? now())->toDateString(),
            'amount' => $amount,
            'notes' => 'Scanned receipt '.$data['fiscal_id'],
            'expense_category_id' => $category?->id,
            'company_id' => $companyId,
            'creator_id' => $request->user()->id,
            'currency_id' => $companyCurrency,
            'exchange_rate' => 1,
            'base_amount' => $amount,
        ]);

        $absolutePath = Storage::disk(config('filesystems.default', 'local'))->path($storedPath);
        if (file_exists($absolutePath)) {
            $expense->addMedia($absolutePath)->preservingOriginal()->toMediaCollection('receipts');
        }

        return $expense;
    }

    protected function createBillFromReceipt(array $data, int $companyId, string $storedPath, Request $request): Bill
    {
        $companyCurrency = CompanySetting::getSetting('currency', $companyId);
        $total = (int) ($data['total'] ?? 0);
        $vat = (int) ($data['vat_total'] ?? 0);
        $subTotal = $total - $vat;

        $supplier = Supplier::updateOrCreate(
            [
                'company_id' => $companyId,
                'tax_id' => $data['issuer_tax_id'] ?? null,
            ],
            [
                'company_id' => $companyId,
                'tax_id' => $data['issuer_tax_id'] ?? null,
                'name' => $data['issuer_tax_id'] ?? 'Supplier',
            ]
        );

        $billNumber = $data['fiscal_id'] ?? null;
        $originalNumber = $billNumber;
        $counter = 1;
        while ($billNumber && Bill::where('company_id', $companyId)
            ->where('bill_number', $billNumber)
            ->exists()
        ) {
            $billNumber = $originalNumber.'-'.$counter;
            $counter++;
        }

        $bill = Bill::create([
            'bill_date' => Carbon::parse($data['date_time'] ?? now())->toDateString(),
            'due_date' => null,
            'bill_number' => $billNumber,
            'status' => Bill::STATUS_DRAFT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
            'sub_total' => $subTotal,
            'discount' => 0,
            'discount_val' => 0,
            'total' => $total,
            'tax' => $vat,
            'due_amount' => $total,
            'company_id' => $companyId,
            'supplier_id' => $supplier->id,
            'creator_id' => $request->user()->id,
            'currency_id' => $companyCurrency,
            'exchange_rate' => 1,
            'base_total' => $total,
            'base_sub_total' => $subTotal,
            'base_tax' => $vat,
            'base_discount_val' => 0,
            'base_due_amount' => $total,
        ]);

        $absolutePath = Storage::disk(config('filesystems.default', 'local'))->path($storedPath);
        if (file_exists($absolutePath)) {
            $bill->addMedia($absolutePath)->preservingOriginal()->toMediaCollection('bills');
        }

        return $bill;
    }
}
