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
use App\Services\FiscalReceiptQrService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReceiptScannerController extends Controller
{
    public function scan(ReceiptScanRequest $request, FiscalReceiptQrService $service): JsonResponse
    {
        \Log::info('ReceiptScannerController::scan - Starting', [
            'user_id' => auth()->id(),
            'company_id' => $request->header('company'),
            'has_file' => $request->hasFile('receipt'),
            'file_size' => $request->hasFile('receipt') ? $request->file('receipt')->getSize() : null,
            'file_mime' => $request->hasFile('receipt') ? $request->file('receipt')->getMimeType() : null,
        ]);

        $companyId = (int) $request->header('company');

        $file = $request->file('receipt');

        if (!$file) {
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

        $this->authorize('create', Expense::class);

        $disk = config('filesystems.default', 'local');
        $storedPath = $file->store('scanned-receipts/'.$companyId, ['disk' => $disk]);

        try {
            $normalized = $service->decodeAndNormalize($file);
        } catch (\Throwable $e) {
            // Clean up stored file if QR decoding failed
            Storage::disk($disk)->delete($storedPath);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        $type = $normalized['type'] ?? 'cash';

        if ($type === 'invoice') {
            \Log::info('ReceiptScannerController::scan - Creating bill from receipt');
            $bill = $this->createBillFromReceipt($normalized, $companyId, $storedPath, $request);
            \Log::info('ReceiptScannerController::scan - Bill created successfully', ['bill_id' => $bill->id]);

            return (new BillResource($bill))
                ->additional(['document_type' => 'bill'])
                ->response()
                ->setStatusCode(201);
        }

        \Log::info('ReceiptScannerController::scan - Creating expense from receipt');
        $expense = $this->createExpenseFromReceipt($normalized, $companyId, $storedPath, $request);
        \Log::info('ReceiptScannerController::scan - Expense created successfully', ['expense_id' => $expense->id]);

        return (new ExpenseResource($expense))
            ->additional(['document_type' => 'expense'])
            ->response()
            ->setStatusCode(201);
    }

    protected function createExpenseFromReceipt(array $data, int $companyId, string $storedPath, Request $request): Expense
    {
        $companyCurrency = CompanySetting::getSetting('currency', $companyId);
        $amount = (int) ($data['total'] ?? 0);

        $category = ExpenseCategory::whereCompany($companyId)->first()
            ?? ExpenseCategory::first();

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

