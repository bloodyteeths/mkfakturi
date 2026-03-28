<?php

namespace Modules\Mk\Services;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Models\Compensation;
use Modules\Mk\Models\CompensationItem;

class CompensationService
{
    /**
     * Get eligible (unpaid/partially paid) documents for a counterparty.
     *
     * Returns receivables (invoices where customer owes us) and
     * payables (bills where we owe the supplier).
     */
    public function getEligibleDocuments(int $companyId, ?int $customerId, ?int $supplierId): array
    {
        $receivables = [];
        $payables = [];

        // Receivables: invoices where customer still owes us money
        if ($customerId) {
            $invoices = Invoice::where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->where('due_amount', '>', 0)
                ->whereNotIn('status', ['DRAFT'])
                ->orderBy('invoice_date', 'asc')
                ->get(['id', 'invoice_number', 'invoice_date', 'total', 'due_amount', 'customer_id']);

            foreach ($invoices as $invoice) {
                $receivables[] = [
                    'id' => $invoice->id,
                    'document_type' => 'invoice',
                    'document_number' => $invoice->invoice_number,
                    'document_date' => $invoice->invoice_date instanceof \DateTimeInterface
                        ? $invoice->invoice_date->format('Y-m-d')
                        : (string) $invoice->invoice_date,
                    'total' => (int) $invoice->total,
                    'due_amount' => (int) $invoice->due_amount,
                ];
            }
        }

        // Payables: bills where we still owe the supplier
        if ($supplierId) {
            $bills = Bill::where('company_id', $companyId)
                ->where('supplier_id', $supplierId)
                ->whereNotIn('status', ['DRAFT'])
                ->whereNotIn('paid_status', ['PAID'])
                ->orderBy('bill_date', 'asc')
                ->get(['id', 'bill_number', 'bill_date', 'total', 'due_amount', 'supplier_id']);

            foreach ($bills as $bill) {
                // Bill.due_amount accessor computes total - payments;
                // we also read the raw column. Use the accessor value for accuracy.
                $dueAmount = $bill->total - BillPayment::where('bill_id', $bill->id)->sum('amount');
                if ($dueAmount <= 0) {
                    continue;
                }

                $payables[] = [
                    'id' => $bill->id,
                    'document_type' => 'bill',
                    'document_number' => $bill->bill_number,
                    'document_date' => $bill->bill_date instanceof \DateTimeInterface
                        ? $bill->bill_date->format('Y-m-d')
                        : (string) $bill->bill_date,
                    'total' => (int) $bill->total,
                    'due_amount' => (int) $dueAmount,
                ];
            }
        }

        return [
            'receivables' => $receivables,
            'payables' => $payables,
        ];
    }

    /**
     * Create a draft compensation with items.
     *
     * @param int   $companyId
     * @param array $data {
     *   counterparty_type: string,
     *   customer_id: ?int,
     *   supplier_id: ?int,
     *   compensation_date: string,
     *   type: string (bilateral|unilateral),
     *   notes: ?string,
     *   currency_id: ?int,
     *   items: array[] {
     *     side: string (receivable|payable),
     *     document_type: string (invoice|bill|credit_note),
     *     document_id: int,
     *     amount_offset: int (in cents),
     *   }
     * }
     * @param int|null $userId
     *
     * @return Compensation
     *
     * @throws \InvalidArgumentException
     */
    public function create(int $companyId, array $data, ?int $userId = null): Compensation
    {
        return DB::transaction(function () use ($companyId, $data, $userId) {
            $receivablesTotal = 0;
            $payablesTotal = 0;

            // Calculate totals per side
            foreach ($data['items'] as $item) {
                if ($item['side'] === 'receivable') {
                    $receivablesTotal += (int) $item['amount_offset'];
                } else {
                    $payablesTotal += (int) $item['amount_offset'];
                }
            }

            $offsetAmount = min($receivablesTotal, $payablesTotal);

            $compensation = Compensation::create([
                'company_id' => $companyId,
                'compensation_date' => $data['compensation_date'] ?? now()->toDateString(),
                'counterparty_type' => $data['counterparty_type'] ?? 'both',
                'customer_id' => $data['customer_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'type' => $data['type'] ?? 'bilateral',
                'status' => 'draft',
                'total_amount' => $offsetAmount,
                'currency_id' => $data['currency_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'receivables_total' => $receivablesTotal,
                'payables_total' => $payablesTotal,
                'receivables_remaining' => $receivablesTotal - $offsetAmount,
                'payables_remaining' => $payablesTotal - $offsetAmount,
                'created_by' => $userId,
            ]);

            // Create items
            foreach ($data['items'] as $item) {
                $docInfo = $this->resolveDocumentInfo(
                    $item['document_type'],
                    $item['document_id']
                );

                $amountOffset = (int) $item['amount_offset'];

                // remaining_after = document due_amount - offset
                $remainingAfter = max(0, ($docInfo['due_amount'] ?? 0) - $amountOffset);

                CompensationItem::create([
                    'compensation_id' => $compensation->id,
                    'side' => $item['side'],
                    'document_type' => $item['document_type'],
                    'document_id' => $item['document_id'],
                    'document_number' => $docInfo['document_number'],
                    'document_date' => $docInfo['document_date'],
                    'document_total' => $docInfo['total'],
                    'amount_offset' => $amountOffset,
                    'remaining_after' => $remainingAfter,
                ]);
            }

            return $compensation->load('items', 'customer', 'supplier');
        });
    }

    /**
     * Update a draft compensation.
     */
    public function update(Compensation $compensation, array $data): Compensation
    {
        if ($compensation->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft compensations can be updated.');
        }

        return DB::transaction(function () use ($compensation, $data) {
            // Update header fields
            $headerFields = [];
            if (isset($data['compensation_date'])) $headerFields['compensation_date'] = $data['compensation_date'];
            if (isset($data['type'])) $headerFields['type'] = $data['type'];
            if (array_key_exists('notes', $data)) $headerFields['notes'] = $data['notes'];

            // If items are provided, recalculate totals
            if (!empty($data['items'])) {
                // Delete old items
                CompensationItem::where('compensation_id', $compensation->id)->delete();

                $receivablesTotal = 0;
                $payablesTotal = 0;

                foreach ($data['items'] as $item) {
                    if ($item['side'] === 'receivable') {
                        $receivablesTotal += (int) $item['amount_offset'];
                    } else {
                        $payablesTotal += (int) $item['amount_offset'];
                    }
                }

                $offsetAmount = min($receivablesTotal, $payablesTotal);

                $headerFields['total_amount'] = $offsetAmount;
                $headerFields['receivables_total'] = $receivablesTotal;
                $headerFields['payables_total'] = $payablesTotal;
                $headerFields['receivables_remaining'] = $receivablesTotal - $offsetAmount;
                $headerFields['payables_remaining'] = $payablesTotal - $offsetAmount;

                // Create new items
                foreach ($data['items'] as $item) {
                    $docInfo = $this->resolveDocumentInfo(
                        $item['document_type'],
                        $item['document_id']
                    );

                    $amountOffset = (int) $item['amount_offset'];
                    $remainingAfter = max(0, ($docInfo['due_amount'] ?? 0) - $amountOffset);

                    CompensationItem::create([
                        'compensation_id' => $compensation->id,
                        'side' => $item['side'],
                        'document_type' => $item['document_type'],
                        'document_id' => $item['document_id'],
                        'document_number' => $docInfo['document_number'],
                        'document_date' => $docInfo['document_date'],
                        'document_total' => $docInfo['total'],
                        'amount_offset' => $amountOffset,
                        'remaining_after' => $remainingAfter,
                    ]);
                }
            }

            if (!empty($headerFields)) {
                $compensation->update($headerFields);
            }

            return $compensation->fresh(['items', 'customer', 'supplier']);
        });
    }

    /**
     * Confirm a compensation: mark as confirmed and apply offsets to invoices/bills.
     */
    public function confirm(Compensation $compensation, ?int $userId = null): Compensation
    {
        if ($compensation->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft compensations can be confirmed.');
        }

        return DB::transaction(function () use ($compensation, $userId) {
            $compensation->load('items');

            foreach ($compensation->items as $item) {
                $this->applyOffset($item);
            }

            $compensation->update([
                'status' => 'confirmed',
                'confirmed_by' => $userId,
                'confirmed_at' => now(),
            ]);

            return $compensation->fresh(['items', 'customer', 'supplier']);
        });
    }

    /**
     * Cancel a compensation (only if draft).
     */
    public function cancel(Compensation $compensation): Compensation
    {
        if ($compensation->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft compensations can be cancelled.');
        }

        $compensation->update(['status' => 'cancelled']);

        return $compensation->fresh(['items', 'customer', 'supplier']);
    }

    /**
     * Find compensation opportunities: counterparties with both
     * open receivables and open payables.
     */
    public function getOpportunities(int $companyId): array
    {
        $opportunities = [];

        // Find customers that have a linked supplier (or vice versa)
        $customers = Customer::where('company_id', $companyId)
            ->whereNotNull('linked_supplier_id')
            ->with('linkedSupplier')
            ->get();

        foreach ($customers as $customer) {
            $supplier = $customer->linkedSupplier;
            if (!$supplier) {
                continue;
            }

            // Calculate open receivables (unpaid invoices from this customer)
            $openReceivables = Invoice::where('company_id', $companyId)
                ->where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->whereNotIn('status', ['DRAFT'])
                ->sum('due_amount');

            if ($openReceivables <= 0) {
                continue;
            }

            // Calculate open payables (unpaid bills to linked supplier)
            $bills = Bill::where('company_id', $companyId)
                ->where('supplier_id', $supplier->id)
                ->whereNotIn('status', ['DRAFT'])
                ->whereNotIn('paid_status', ['PAID'])
                ->get(['id', 'total']);

            $openPayables = 0;
            foreach ($bills as $bill) {
                $paid = BillPayment::where('bill_id', $bill->id)->sum('amount');
                $remaining = $bill->total - $paid;
                if ($remaining > 0) {
                    $openPayables += $remaining;
                }
            }

            if ($openPayables <= 0) {
                continue;
            }

            $suggestedAmount = min((int) $openReceivables, (int) $openPayables);

            $opportunities[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'open_receivables' => (int) $openReceivables,
                'open_payables' => (int) $openPayables,
                'suggested_offset' => $suggestedAmount,
            ];
        }

        // Also check suppliers that have a linked customer
        $suppliers = Supplier::where('company_id', $companyId)
            ->has('linkedCustomer')
            ->with('linkedCustomer')
            ->get();

        $existingPairs = collect($opportunities)->map(function ($opp) {
            return $opp['customer_id'] . '-' . $opp['supplier_id'];
        })->toArray();

        foreach ($suppliers as $supplier) {
            $customer = $supplier->linkedCustomer;
            if (!$customer) {
                continue;
            }

            // Skip if already found via customer->linkedSupplier direction
            $pairKey = $customer->id . '-' . $supplier->id;
            if (in_array($pairKey, $existingPairs)) {
                continue;
            }

            $openReceivables = Invoice::where('company_id', $companyId)
                ->where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->whereNotIn('status', ['DRAFT'])
                ->sum('due_amount');

            if ($openReceivables <= 0) {
                continue;
            }

            $bills = Bill::where('company_id', $companyId)
                ->where('supplier_id', $supplier->id)
                ->whereNotIn('status', ['DRAFT'])
                ->whereNotIn('paid_status', ['PAID'])
                ->get(['id', 'total']);

            $openPayables = 0;
            foreach ($bills as $bill) {
                $paid = BillPayment::where('bill_id', $bill->id)->sum('amount');
                $remaining = $bill->total - $paid;
                if ($remaining > 0) {
                    $openPayables += $remaining;
                }
            }

            if ($openPayables <= 0) {
                continue;
            }

            $suggestedAmount = min((int) $openReceivables, (int) $openPayables);

            $opportunities[] = [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'open_receivables' => (int) $openReceivables,
                'open_payables' => (int) $openPayables,
                'suggested_offset' => $suggestedAmount,
            ];
        }

        // Sort by suggested_offset descending (biggest opportunities first)
        usort($opportunities, function ($a, $b) {
            return $b['suggested_offset'] <=> $a['suggested_offset'];
        });

        return $opportunities;
    }

    /**
     * Generate a PDF document for a confirmed compensation.
     *
     * @return \Barryvdh\DomPdf\PDF
     */
    public function generatePdf(Compensation $compensation)
    {
        $compensation->load([
            'company.address',
            'customer',
            'supplier',
            'items',
            'createdBy',
            'confirmedBy',
        ]);

        $receivableItems = $compensation->items->where('side', 'receivable')->values();
        $payableItems = $compensation->items->where('side', 'payable')->values();

        $company = $compensation->company;

        view()->share([
            'compensation' => $compensation,
            'company' => $company,
            'customer' => $compensation->customer,
            'supplier' => $compensation->supplier,
            'receivableItems' => $receivableItems,
            'payableItems' => $payableItems,
            'report_period' => $compensation->compensation_date->format('d.m.Y'),
        ]);

        return \PDF::loadView('app.pdf.reports.compensation');
    }

    // ---- Private helpers ----

    /**
     * Resolve document info (number, date, total, due_amount) for an item.
     */
    private function resolveDocumentInfo(string $documentType, int $documentId): array
    {
        if ($documentType === 'invoice' || $documentType === 'credit_note') {
            $invoice = Invoice::find($documentId);
            if (!$invoice) {
                return ['document_number' => '', 'document_date' => null, 'total' => 0, 'due_amount' => 0];
            }

            return [
                'document_number' => $invoice->invoice_number,
                'document_date' => $invoice->invoice_date instanceof \DateTimeInterface
                    ? $invoice->invoice_date->format('Y-m-d')
                    : $invoice->invoice_date,
                'total' => (int) $invoice->total,
                'due_amount' => (int) $invoice->due_amount,
            ];
        }

        if ($documentType === 'bill') {
            $bill = Bill::find($documentId);
            if (!$bill) {
                return ['document_number' => '', 'document_date' => null, 'total' => 0, 'due_amount' => 0];
            }

            // Calculate real due amount from column, not accessor
            $paidAmount = BillPayment::where('bill_id', $bill->id)->sum('amount');
            $dueAmount = $bill->total - $paidAmount;

            return [
                'document_number' => $bill->bill_number,
                'document_date' => $bill->bill_date instanceof \DateTimeInterface
                    ? $bill->bill_date->format('Y-m-d')
                    : $bill->bill_date,
                'total' => (int) $bill->total,
                'due_amount' => (int) max(0, $dueAmount),
            ];
        }

        return ['document_number' => '', 'document_date' => null, 'total' => 0, 'due_amount' => 0];
    }

    /**
     * Apply the offset amount to the underlying invoice or bill.
     *
     * For invoices: reduce due_amount and update paid_status.
     * For bills: create a BillPayment to reduce the outstanding amount.
     */
    private function applyOffset(CompensationItem $item): void
    {
        if ($item->document_type === 'invoice' || $item->document_type === 'credit_note') {
            $invoice = Invoice::find($item->document_id);
            if (!$invoice) {
                return;
            }

            // Use the Invoice model's built-in method that also updates status
            $invoice->subtractInvoicePayment($item->amount_offset);
        }

        if ($item->document_type === 'bill') {
            $bill = Bill::find($item->document_id);
            if (!$bill) {
                return;
            }

            // For bills, reduce the stored due_amount column and update paid_status
            $newDueAmount = max(0, $bill->getAttributes()['due_amount'] - $item->amount_offset);

            $paidStatus = Bill::PAID_STATUS_UNPAID;
            if ($newDueAmount <= 0) {
                $paidStatus = Bill::PAID_STATUS_PAID;
            } elseif ($newDueAmount < $bill->total) {
                $paidStatus = Bill::PAID_STATUS_PARTIALLY_PAID;
            }

            $bill->update([
                'due_amount' => $newDueAmount,
                'paid_status' => $paidStatus,
                'status' => $newDueAmount <= 0 ? Bill::STATUS_COMPLETED : $bill->status,
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
