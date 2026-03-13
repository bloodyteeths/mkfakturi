<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BankTransaction;
use App\Models\ClientDocument;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\TaxType;
use App\Services\Banking\DeduplicationService;
use App\Services\Banking\TransactionFingerprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Vinkla\Hashids\Facades\Hashids;

class DocumentConfirmationService
{
    /**
     * Confirm as Bill (supplier invoice/receipt → Bill).
     */
    public function confirmAsBill(ClientDocument $doc, array $extractedData, int $companyId): array
    {
        $supplierData = $extractedData['supplier'] ?? [];
        $billData = $extractedData['bill'] ?? [];
        $items = $extractedData['items'] ?? [];

        $supplier = $this->findOrCreateSupplier($supplierData, $companyId);

        $billData['supplier_id'] = $supplier->id;
        $billData['company_id'] = $companyId;
        $billData['status'] = Bill::STATUS_DRAFT;
        $billData['paid_status'] = Bill::PAID_STATUS_UNPAID;

        if (empty($billData['bill_number'])) {
            $billData['bill_number'] = 'DOC-' . strtoupper(substr(md5($doc->file_path), 0, 8));
        }

        // Ensure bill number uniqueness
        $originalNumber = $billData['bill_number'];
        $counter = 1;
        while (Bill::where('company_id', $companyId)->where('bill_number', $billData['bill_number'])->exists()) {
            $billData['bill_number'] = $originalNumber . '-' . $counter;
            $counter++;
        }

        if (empty($billData['currency_id'])) {
            $billData['currency_id'] = CompanySetting::getSetting('currency', $companyId);
        }

        $bill = Bill::create($billData);

        if (! empty($items)) {
            $this->attachTaxTypes($companyId, $items);
            Bill::createItems($bill, $items);
        }

        $this->attachMediaToEntity($doc, $bill, 'scanned_invoice');

        $doc->update(['linked_bill_id' => $bill->id]);

        return [
            'bill_id' => $bill->id,
            'bill_number' => $bill->bill_number,
        ];
    }

    /**
     * Confirm as Expense (receipt → Expense).
     */
    public function confirmAsExpense(ClientDocument $doc, array $extractedData, int $companyId): array
    {
        $supplierData = $extractedData['supplier'] ?? [];
        $expenseData = $extractedData['expense'] ?? $extractedData['bill'] ?? [];

        $supplier = null;
        if (! empty($supplierData['name'])) {
            $supplier = $this->findOrCreateSupplier($supplierData, $companyId);
        }

        $categoryName = $expenseData['category'] ?? $extractedData['ai_classification']['summary'] ?? 'AI Import';
        $category = ExpenseCategory::firstOrCreate(
            ['name' => $categoryName, 'company_id' => $companyId],
            ['description' => 'Auto-created from AI document processing']
        );

        $currencyId = $expenseData['currency_id'] ?? CompanySetting::getSetting('currency', $companyId);

        $amount = $expenseData['total'] ?? $expenseData['amount'] ?? 0;

        $expense = Expense::create([
            'expense_date' => $expenseData['bill_date'] ?? $expenseData['expense_date'] ?? now()->format('Y-m-d'),
            'amount' => $amount,
            'base_amount' => $amount,
            'exchange_rate' => $expenseData['exchange_rate'] ?? 1,
            'notes' => $expenseData['notes'] ?? 'Created from AI Document Hub',
            'expense_category_id' => $category->id,
            'company_id' => $companyId,
            'supplier_id' => $supplier?->id,
            'invoice_number' => $expenseData['bill_number'] ?? $expenseData['invoice_number'] ?? null,
            'currency_id' => $currencyId,
            'creator_id' => Auth::id(),
        ]);

        $this->attachMediaToEntity($doc, $expense, 'receipts');

        $doc->update(['linked_expense_id' => $expense->id]);

        return [
            'expense_id' => $expense->id,
        ];
    }

    /**
     * Confirm as Invoice (outgoing customer invoice).
     */
    public function confirmAsInvoice(ClientDocument $doc, array $extractedData, int $companyId): array
    {
        $customerData = $extractedData['customer'] ?? $extractedData['supplier'] ?? [];
        $invoiceData = $extractedData['invoice'] ?? $extractedData['bill'] ?? [];
        $items = $extractedData['items'] ?? [];

        $customer = $this->findOrCreateCustomer($customerData, $companyId);

        $currencyId = $customer->currency_id ?? CompanySetting::getSetting('currency', $companyId);
        $exchangeRate = $invoiceData['exchange_rate'] ?? 1;
        $subTotal = $invoiceData['sub_total'] ?? 0;
        $tax = $invoiceData['tax'] ?? 0;
        $total = $invoiceData['total'] ?? 0;
        $discount = $invoiceData['discount'] ?? 0;
        $discountVal = $invoiceData['discount_val'] ?? 0;

        // Ensure invoice number uniqueness per company
        $invoiceNumber = $invoiceData['bill_number'] ?? $invoiceData['invoice_number'] ?? null;
        if ($invoiceNumber) {
            $originalNumber = $invoiceNumber;
            $counter = 1;
            while (Invoice::where('company_id', $companyId)->where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = $originalNumber . '-' . $counter;
                $counter++;
            }
        } else {
            // Temporary placeholder — will be replaced by SerialNumberFormatter below
            $invoiceNumber = 'DOC-' . strtoupper(substr(md5($doc->file_path . now()), 0, 8));
        }

        $invoice = Invoice::create([
            'invoice_date' => $invoiceData['bill_date'] ?? $invoiceData['invoice_date'] ?? now()->format('Y-m-d'),
            'due_date' => $invoiceData['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customer->id,
            'company_id' => $companyId,
            'status' => Invoice::STATUS_DRAFT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'sub_total' => $subTotal,
            'tax' => $tax,
            'total' => $total,
            'discount' => $discount,
            'discount_val' => $discountVal,
            'discount_type' => 'fixed',
            'due_amount' => $total,
            'base_total' => $total * $exchangeRate,
            'base_sub_total' => $subTotal * $exchangeRate,
            'base_tax' => $tax * $exchangeRate,
            'base_discount_val' => $discountVal * $exchangeRate,
            'base_due_amount' => $total * $exchangeRate,
            'tax_per_item' => CompanySetting::getSetting('tax_per_item', $companyId) ?? 'NO',
            'discount_per_item' => CompanySetting::getSetting('discount_per_item', $companyId) ?? 'NO',
            'template_name' => 'invoice1',
            'creator_id' => Auth::id(),
        ]);

        // Set serial numbers
        $serial = (new \App\Services\SerialNumberFormatter)
            ->setModel($invoice)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $invoice->sequence_number = $serial->nextSequenceNumber;
        $invoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);

        if (empty($invoice->invoice_number)) {
            $invoice->invoice_number = $serial->getFormattedNumber($invoice);
        }
        $invoice->save();

        if (! empty($items)) {
            // Prepare items for Invoice::createItems format
            foreach ($items as &$item) {
                $item['exchange_rate'] = $exchangeRate;
                $item['base_price'] = ($item['price'] ?? 0) * $exchangeRate;
                $item['base_discount_val'] = ($item['discount_val'] ?? 0) * $exchangeRate;
                $item['base_tax'] = ($item['tax'] ?? 0) * $exchangeRate;
                $item['base_total'] = ($item['total'] ?? 0) * $exchangeRate;
                $item['company_id'] = $companyId;
                $item['discount'] = $item['discount'] ?? 0;
                $item['discount_val'] = $item['discount_val'] ?? 0;
                $item['discount_type'] = 'fixed';
            }
            unset($item);

            $this->attachTaxTypes($companyId, $items);

            foreach ($items as $itemData) {
                $invoiceItem = $invoice->items()->create($itemData);

                if (! empty($itemData['taxes'])) {
                    foreach ($itemData['taxes'] as $taxData) {
                        $taxData['company_id'] = $companyId;
                        $taxData['exchange_rate'] = $exchangeRate;
                        $taxData['base_amount'] = ($taxData['amount'] ?? 0) * $exchangeRate;
                        $taxData['currency_id'] = $currencyId;
                        $invoiceItem->taxes()->create($taxData);
                    }
                }
            }
        }

        $this->attachMediaToEntity($doc, $invoice, 'source_document');

        $doc->update(['linked_invoice_id' => $invoice->id]);

        return [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ];
    }

    /**
     * Confirm as Bank Transactions (bulk import).
     */
    public function confirmAsBankTransactions(ClientDocument $doc, array $extractedData, int $companyId): array
    {
        $bankAccountId = $extractedData['bank_account_id'] ?? null;
        $transactions = $extractedData['transactions'] ?? [];

        if (empty($bankAccountId)) {
            throw new \InvalidArgumentException('bank_account_id is required for bank transaction import.');
        }

        if (empty($transactions)) {
            return ['created' => 0, 'duplicates' => 0, 'failed' => 0];
        }

        // Prepare transactions for import
        $prepared = [];
        foreach ($transactions as $tx) {
            $amount = 0;
            if (! empty($tx['credit']) && $tx['credit'] > 0) {
                $amount = (float) $tx['credit'];
                $txType = 'credit';
            } elseif (! empty($tx['debit']) && $tx['debit'] > 0) {
                $amount = -1 * (float) $tx['debit'];
                $txType = 'debit';
            } else {
                $amount = (float) ($tx['amount'] ?? 0);
                $txType = $amount >= 0 ? 'credit' : 'debit';
            }

            $prepared[] = [
                'bank_account_id' => $bankAccountId,
                'company_id' => $companyId,
                'transaction_date' => $tx['date'] ?? $tx['transaction_date'] ?? now()->format('Y-m-d'),
                'amount' => $amount,
                'currency' => $tx['currency'] ?? 'MKD',
                'transaction_type' => $txType,
                'booking_status' => 'booked',
                'description' => $tx['description'] ?? $tx['purpose'] ?? '',
                'creditor_name' => $txType === 'debit' ? ($tx['counterparty_name'] ?? '') : '',
                'debtor_name' => $txType === 'credit' ? ($tx['counterparty_name'] ?? '') : '',
                'creditor_account' => $txType === 'debit' ? ($tx['counterparty_account'] ?? '') : '',
                'debtor_account' => $txType === 'credit' ? ($tx['counterparty_account'] ?? '') : '',
                'payment_reference' => $tx['reference'] ?? $tx['payment_code'] ?? '',
                'source' => 'ocr_import',
                'processing_status' => 'unprocessed',
                'raw_data' => json_encode(array_merge($tx, ['source_document_id' => $doc->id])),
            ];
        }

        $fingerprinter = new TransactionFingerprint();
        $dedupeService = new DeduplicationService($fingerprinter);
        $result = $dedupeService->importWithDedupe($prepared, $companyId, 'ocr_import');

        // Store imported IDs in document
        $doc->update([
            'extracted_data' => array_merge($doc->extracted_data ?? [], [
                'imported_transaction_ids' => $result->createdIds ?? [],
                'import_summary' => $result->summary(),
            ]),
        ]);

        return [
            'created' => $result->created ?? 0,
            'duplicates' => $result->duplicates ?? 0,
            'failed' => $result->failed ?? 0,
            'created_ids' => $result->createdIds ?? [],
        ];
    }

    /**
     * Confirm as Items (product catalog bulk import).
     */
    public function confirmAsItems(ClientDocument $doc, array $extractedData, int $companyId): array
    {
        $products = $extractedData['products'] ?? [];
        $currency = $extractedData['currency'] ?? null;

        if (empty($products)) {
            return ['created' => 0, 'skipped' => 0, 'item_ids' => []];
        }

        $currencyId = null;
        if ($currency) {
            $currencyModel = \App\Models\Currency::where('code', strtoupper($currency))->first();
            $currencyId = $currencyModel?->id;
        }
        if (! $currencyId) {
            $currencyId = CompanySetting::getSetting('currency', $companyId);
        }

        $createdIds = [];
        $skipped = 0;

        foreach ($products as $product) {
            $name = $product['name'] ?? null;
            if (empty($name)) {
                $skipped++;
                continue;
            }

            // Dedup by SKU or name
            $existing = Item::where('company_id', $companyId)
                ->where(function ($q) use ($product, $name) {
                    if (! empty($product['code'])) {
                        $q->where('sku', $product['code']);
                    } elseif (! empty($product['barcode'])) {
                        $q->where('barcode', $product['barcode']);
                    } else {
                        $q->where('name', $name);
                    }
                })
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            // Price arrives in cents from FastAPI (unit_price * 100) or Vue parseCents()
            $price = (int) ($product['unit_price'] ?? $product['price'] ?? 0);

            $item = Item::create([
                'name' => $name,
                'description' => $product['description'] ?? null,
                'price' => (int) $price,
                'cost' => isset($product['cost']) ? (int) ($product['cost'] * 100) : null,
                'sku' => $product['code'] ?? null,
                'barcode' => $product['barcode'] ?? null,
                'company_id' => $companyId,
                'currency_id' => $currencyId,
                'creator_id' => Auth::id(),
            ]);

            $createdIds[] = $item->id;
        }

        // Store imported IDs in document
        $doc->update([
            'extracted_data' => array_merge($doc->extracted_data ?? [], [
                'imported_item_ids' => $createdIds,
            ]),
        ]);

        return [
            'created' => count($createdIds),
            'skipped' => $skipped,
            'item_ids' => $createdIds,
        ];
    }

    /**
     * Confirm as Document (tax_form/contract — save edited data, stay in hub).
     */
    public function confirmAsDocument(ClientDocument $doc, array $extractedData): array
    {
        $doc->update([
            'extracted_data' => array_merge(['type' => $doc->category], $extractedData),
        ]);

        return [
            'document_id' => $doc->id,
            'saved' => true,
        ];
    }

    /**
     * Find or create a Supplier.
     */
    private function findOrCreateSupplier(array $supplierData, int $companyId): Supplier
    {
        $name = $supplierData['name'] ?? 'Unknown Supplier';

        return Supplier::updateOrCreate(
            ['company_id' => $companyId, 'name' => $name],
            [
                'company_id' => $companyId,
                'name' => $name,
                'tax_id' => $supplierData['tax_id'] ?? null,
                'email' => $supplierData['email'] ?? null,
            ]
        );
    }

    /**
     * Find or create a Customer.
     */
    private function findOrCreateCustomer(array $customerData, int $companyId): Customer
    {
        $name = $customerData['name'] ?? 'Unknown Customer';
        $email = $customerData['email'] ?? null;

        // Try to find by tax_id first, then by name
        $customer = null;
        if (! empty($customerData['tax_id'])) {
            $customer = Customer::where('company_id', $companyId)
                ->where('website', $customerData['tax_id']) // tax_id stored in website field sometimes
                ->first();
        }

        if (! $customer) {
            $customer = Customer::where('company_id', $companyId)
                ->where('name', $name)
                ->first();
        }

        if (! $customer) {
            $customer = Customer::create([
                'name' => $name,
                'email' => $email,
                'phone' => $customerData['phone'] ?? null,
                'company_id' => $companyId,
                'currency_id' => CompanySetting::getSetting('currency', $companyId),
                'creator_id' => Auth::id(),
            ]);
        }

        return $customer;
    }

    /**
     * Attach MK VAT tax types to items based on extracted tax amounts.
     */
    private function attachTaxTypes(int $companyId, array &$items): void
    {
        $standardRates = [18, 10, 5];
        $taxTypes = TaxType::where('company_id', $companyId)
            ->orWhereNull('company_id')
            ->get();

        foreach ($items as &$item) {
            $taxAmount = (int) ($item['tax'] ?? 0);
            $price = (int) ($item['price'] ?? 0);

            if ($taxAmount <= 0 || $price <= 0) {
                continue;
            }

            $effectiveRate = ($taxAmount / $price) * 100;
            $snappedRate = null;

            foreach ($standardRates as $rate) {
                if (abs($effectiveRate - $rate) <= 2) {
                    $snappedRate = $rate;
                    break;
                }
            }

            if ($snappedRate === null) {
                continue;
            }

            $taxType = $taxTypes->first(fn ($t) => abs((float) $t->percent - $snappedRate) < 0.01);

            if (! $taxType) {
                continue;
            }

            $item['taxes'] = [
                [
                    'tax_type_id' => $taxType->id,
                    'name' => $taxType->name,
                    'percent' => (float) $taxType->percent,
                    'amount' => $taxAmount,
                    'compound_tax' => $taxType->compound_tax ?? 0,
                ],
            ];
        }
        unset($item);
    }

    /**
     * Attach the document's file as media on the target entity.
     */
    private function attachMediaToEntity(ClientDocument $doc, $entity, string $collection): void
    {
        $disk = config('filesystems.media_disk');

        try {
            if (Storage::disk($disk)->exists($doc->file_path)) {
                $entity->addMediaFromDisk($doc->file_path, $disk)
                    ->toMediaCollection($collection);
            }
        } catch (\Throwable $e) {
            Log::warning('DocumentConfirmationService: failed to attach media', [
                'document_id' => $doc->id,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'collection' => $collection,
                'error' => $e->getMessage(),
            ]);
        }
    }
} // CLAUDE-CHECKPOINT
