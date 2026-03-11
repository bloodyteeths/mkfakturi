<?php

namespace Modules\Mk\Services;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\PaymentBatch;
use Modules\Mk\Models\PaymentBatchItem;

/**
 * Payment Order Service
 *
 * Handles business logic for Payment Orders (Налози за плаќање):
 * - Querying payable bills
 * - Creating payment batches from bill selections
 * - Approval, export, confirmation, and cancellation workflows
 * - Overdue bill summaries
 */
class PaymentOrderService
{
    /**
     * Get unpaid bills for a company, optionally filtered.
     *
     * @param int $companyId
     * @param array $filters Keys: supplier_id, due_before, due_after, min_amount, max_amount
     * @return Collection
     */
    public function getPayableBills(int $companyId, array $filters = []): Collection
    {
        // Get bill IDs already in active (non-cancelled) batches
        $billsInActiveBatches = PaymentBatchItem::whereHas('paymentBatch', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
                ->whereNotIn('status', [PaymentBatch::STATUS_CANCELLED, PaymentBatch::STATUS_CONFIRMED]);
        })->whereNotNull('bill_id')->pluck('bill_id')->toArray();

        $query = Bill::where('company_id', $companyId)
            ->whereIn('paid_status', [
                Bill::PAID_STATUS_UNPAID,
                Bill::PAID_STATUS_PARTIALLY_PAID,
            ])
            ->whereNotIn('status', [Bill::STATUS_DRAFT])
            ->when(! empty($billsInActiveBatches), function ($q) use ($billsInActiveBatches) {
                $q->whereNotIn('id', $billsInActiveBatches);
            })
            ->with(['supplier:id,name,email,iban,bic,bank_name', 'currency:id,code,symbol']);

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (! empty($filters['due_before'])) {
            $query->where('due_date', '<=', $filters['due_before']);
        }

        if (! empty($filters['due_after'])) {
            $query->where('due_date', '>=', $filters['due_after']);
        }

        if (! empty($filters['min_amount'])) {
            // min_amount comes in as whole currency units, bills store total in cents
            $query->where('total', '>=', (int) ($filters['min_amount'] * 100));
        }

        if (! empty($filters['max_amount'])) {
            $query->where('total', '<=', (int) ($filters['max_amount'] * 100));
        }

        return $query->orderBy('due_date', 'asc')->get()->map(function (Bill $bill) {
            $paidAmount = $bill->payments()->sum('amount');
            $dueAmount = $bill->total - $paidAmount;

            $isOverdue = $bill->due_date && Carbon::parse($bill->due_date)->lt(Carbon::today());
            $isDueSoon = $bill->due_date && ! $isOverdue
                && Carbon::parse($bill->due_date)->lte(Carbon::today()->addDays(7));

            return [
                'id' => $bill->id,
                'bill_number' => $bill->bill_number,
                'bill_date' => $bill->bill_date instanceof \DateTimeInterface
                    ? $bill->bill_date->format('Y-m-d')
                    : ($bill->bill_date ?? ''),
                'due_date' => $bill->due_date instanceof \DateTimeInterface
                    ? $bill->due_date->format('Y-m-d')
                    : ($bill->due_date ?? ''),
                'total' => (int) $bill->total,
                'due_amount' => (int) $dueAmount,
                'paid_status' => $bill->paid_status,
                'is_overdue' => $isOverdue,
                'is_due_soon' => $isDueSoon,
                'supplier' => [
                    'id' => $bill->supplier?->id,
                    'name' => $bill->supplier?->name ?? 'Unknown',
                    'iban' => $bill->supplier?->iban ?? null,
                    'bic' => $bill->supplier?->bic ?? null,
                    'bank_name' => $bill->supplier?->bank_name ?? null,
                ],
                'currency_code' => $bill->currency?->code ?? 'MKD',
            ];
        });
    }

    /**
     * Create a payment batch from selected bills.
     *
     * @param int $companyId
     * @param array $data Keys: batch_date, format, bank_account_id, notes, bill_ids
     * @return PaymentBatch
     */
    public function createBatch(int $companyId, array $data, bool $autoApprove = false): array
    {
        return DB::transaction(function () use ($companyId, $data, $autoApprove) {
            $billIds = $data['bill_ids'] ?? [];

            // Reject bills already in active (non-cancelled, non-confirmed) batches
            $alreadyInBatch = PaymentBatchItem::whereHas('paymentBatch', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->whereNotIn('status', [PaymentBatch::STATUS_CANCELLED, PaymentBatch::STATUS_CONFIRMED]);
            })->whereIn('bill_id', $billIds)->pluck('bill_id')->toArray();

            $billIds = array_values(array_diff($billIds, $alreadyInBatch));

            if (empty($billIds)) {
                throw new \Exception('All selected bills are already in active payment batches.');
            }

            $batch = PaymentBatch::create([
                'company_id' => $companyId,
                'batch_date' => $data['batch_date'] ?? now()->toDateString(),
                'format' => $data['format'] ?? PaymentBatch::FORMAT_PP30,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => PaymentBatch::STATUS_DRAFT,
                'created_by' => $data['created_by'] ?? null,
            ]);

            $totalAmount = 0;
            $itemCount = 0;
            $skippedBills = [];

            $bills = Bill::where('company_id', $companyId)
                ->whereIn('id', $billIds)
                ->with(['supplier:id,name,iban,bic,bank_name', 'currency:id,code'])
                ->lockForUpdate()
                ->get();

            foreach ($bills as $bill) {
                $paidAmount = $bill->payments()->sum('amount');
                $dueAmount = $bill->total - $paidAmount;

                if ($dueAmount <= 0) {
                    $skippedBills[] = $bill->bill_number ?? "Bill #{$bill->id}";

                    continue;
                }

                $supplier = $bill->supplier;

                PaymentBatchItem::create([
                    'payment_batch_id' => $batch->id,
                    'bill_id' => $bill->id,
                    'creditor_name' => $supplier?->name ?? 'Unknown',
                    'creditor_iban' => $supplier?->iban ?? null,
                    'creditor_bic' => $supplier?->bic ?? null,
                    'creditor_bank_name' => $supplier?->bank_name ?? null,
                    'amount' => (int) $dueAmount,
                    'currency_code' => $bill->currency?->code ?? 'MKD',
                    'description' => $bill->bill_number
                        ? "Payment for bill {$bill->bill_number}"
                        : 'Bill payment',
                    'status' => PaymentBatchItem::STATUS_PENDING,
                ]);

                $totalAmount += (int) $dueAmount;
                $itemCount++;
            }

            if ($itemCount === 0) {
                throw new \Exception('No payable bills — all selected bills are already fully paid.');
            }

            $batch->update([
                'total_amount' => $totalAmount,
                'item_count' => $itemCount,
            ]);

            // Auto-approve if requested (company owners skip manual approval)
            if ($autoApprove && $batch->isApprovable()) {
                $batch->update([
                    'status' => PaymentBatch::STATUS_APPROVED,
                    'approved_by' => $data['created_by'] ?? null,
                    'approved_at' => now(),
                ]);
            }

            $batch->load('items');

            Log::info('Payment batch created', [
                'batch_id' => $batch->id,
                'company_id' => $companyId,
                'items' => $itemCount,
                'total' => $totalAmount,
                'skipped_fully_paid' => count($skippedBills),
                'skipped_in_batch' => count($alreadyInBatch),
            ]);

            return [
                'batch' => $batch,
                'skipped_bills' => $skippedBills,
                'skipped_in_batch' => $alreadyInBatch,
            ];
        });
    }

    /**
     * Approve a payment batch.
     *
     * @param PaymentBatch $batch
     * @param int $userId
     * @return PaymentBatch
     *
     * @throws \Exception
     */
    public function approve(PaymentBatch $batch, int $userId): PaymentBatch
    {
        if (! $batch->isApprovable()) {
            throw new \Exception("Batch #{$batch->batch_number} cannot be approved in status '{$batch->status}'.");
        }

        $batch->update([
            'status' => PaymentBatch::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        Log::info('Payment batch approved', [
            'batch_id' => $batch->id,
            'approved_by' => $userId,
        ]);

        return $batch->fresh();
    }

    /**
     * Export a payment batch: generate the file and update status.
     *
     * @param PaymentBatch $batch
     * @return array ['content' => string, 'filename' => string, 'mime' => string]
     *
     * @throws \Exception
     */
    public function export(PaymentBatch $batch): array
    {
        if (! $batch->isExportable()) {
            throw new \Exception("Batch #{$batch->batch_number} cannot be exported in status '{$batch->status}'.");
        }

        $batch->load(['items', 'company', 'bankAccount']);

        $content = '';
        $filename = '';
        $mime = '';

        switch ($batch->format) {
            case PaymentBatch::FORMAT_PP30:
                $builder = new Pp30FileBuilder();
                $content = $builder->build($batch);
                $filename = "PP30_{$batch->batch_number}_{$batch->batch_date->format('Ymd')}.csv";
                $mime = 'text/csv';
                break;

            case PaymentBatch::FORMAT_SEPA_SCT:
                $builder = new SepaXmlBuilder();
                $content = $builder->build($batch);
                $filename = "SEPA_{$batch->batch_number}_{$batch->batch_date->format('Ymd')}.xml";
                $mime = 'application/xml';
                break;

            case PaymentBatch::FORMAT_PP50:
                // PP50 uses same CSV structure as PP30 but with public revenue fields
                $builder = new Pp30FileBuilder();
                $content = $builder->build($batch, true);
                $filename = "PP50_{$batch->batch_number}_{$batch->batch_date->format('Ymd')}.csv";
                $mime = 'text/csv';
                break;

            case PaymentBatch::FORMAT_CSV:
                $builder = new Pp30FileBuilder();
                $content = $builder->build($batch);
                $filename = "PAYMENT_{$batch->batch_number}_{$batch->batch_date->format('Ymd')}.csv";
                $mime = 'text/csv';
                break;

            default:
                throw new \Exception("Unsupported format: {$batch->format}");
        }

        // Store file path and update status
        $path = "payment-orders/{$batch->company_id}/{$filename}";
        \Storage::disk('local')->put($path, $content);

        $batch->update([
            'status' => PaymentBatch::STATUS_EXPORTED,
            'exported_at' => now(),
            'exported_file_path' => $path,
        ]);

        // Mark items as exported
        $batch->items()->update(['status' => PaymentBatchItem::STATUS_EXPORTED]);

        Log::info('Payment batch exported', [
            'batch_id' => $batch->id,
            'format' => $batch->format,
            'file' => $path,
        ]);

        return [
            'content' => $content,
            'filename' => $filename,
            'mime' => $mime,
        ];
    }

    /**
     * Confirm a payment batch: create bill payments and update statuses.
     *
     * @param PaymentBatch $batch
     * @return PaymentBatch
     *
     * @throws \Exception
     */
    public function confirm(PaymentBatch $batch): PaymentBatch
    {
        if (! $batch->isConfirmable()) {
            throw new \Exception("Batch #{$batch->batch_number} cannot be confirmed in status '{$batch->status}'.");
        }

        return DB::transaction(function () use ($batch) {
            $batch->load('items');

            $confirmedCount = 0;
            $skippedCount = 0;

            foreach ($batch->items as $item) {
                if (! $item->bill_id) {
                    $item->update(['status' => PaymentBatchItem::STATUS_CONFIRMED]);
                    $confirmedCount++;

                    continue;
                }

                $bill = Bill::find($item->bill_id);
                if (! $bill) {
                    $item->update(['status' => PaymentBatchItem::STATUS_FAILED]);
                    $skippedCount++;

                    continue;
                }

                // Re-check: bill may have been paid since batch creation
                $paidAmount = $bill->payments()->sum('amount');
                $remainingDue = $bill->total - $paidAmount;

                if ($remainingDue <= 0) {
                    $item->update(['status' => PaymentBatchItem::STATUS_FAILED]);
                    $skippedCount++;

                    continue;
                }

                // Cap payment at remaining due amount (not the original batch item amount)
                $paymentAmount = min($item->amount, (int) $remainingDue);

                if ($paymentAmount < $item->amount) {
                    Log::warning('Payment amount adjusted during confirmation', [
                        'batch_id' => $batch->id,
                        'item_id' => $item->id,
                        'bill_id' => $item->bill_id,
                        'original_amount' => $item->amount,
                        'adjusted_amount' => $paymentAmount,
                        'remaining_due' => $remainingDue,
                    ]);
                }

                // Generate payment number with lock to prevent duplicates
                $year = date('Y');
                $sequence = BillPayment::where('company_id', $batch->company_id)
                    ->whereYear('created_at', $year)
                    ->lockForUpdate()
                    ->count() + 1;
                $paymentNumber = sprintf('BPAY-%d-%06d', $year, $sequence);

                // Create bill payment
                BillPayment::create([
                    'bill_id' => $item->bill_id,
                    'company_id' => $batch->company_id,
                    'payment_number' => $paymentNumber,
                    'payment_date' => $batch->batch_date,
                    'amount' => $paymentAmount,
                    'notes' => "Payment order {$batch->batch_number}",
                ]);

                // Update bill paid status
                $bill->updatePaidStatus();

                $item->update(['status' => PaymentBatchItem::STATUS_CONFIRMED]);
                $confirmedCount++;
            }

            $batch->update([
                'status' => PaymentBatch::STATUS_CONFIRMED,
            ]);

            Log::info('Payment batch confirmed', [
                'batch_id' => $batch->id,
                'confirmed' => $confirmedCount,
                'skipped' => $skippedCount,
            ]);

            return $batch->fresh(['items']);
        });
    }

    /**
     * Cancel a payment batch.
     *
     * @param PaymentBatch $batch
     * @return PaymentBatch
     *
     * @throws \Exception
     */
    public function cancel(PaymentBatch $batch): PaymentBatch
    {
        if (! $batch->isCancellable()) {
            throw new \Exception("Batch #{$batch->batch_number} cannot be cancelled in status '{$batch->status}'.");
        }

        $batch->update([
            'status' => PaymentBatch::STATUS_CANCELLED,
        ]);

        Log::info('Payment batch cancelled', [
            'batch_id' => $batch->id,
        ]);

        return $batch->fresh();
    }

    /**
     * Get overdue summary: counts and totals by urgency bucket.
     *
     * @param int $companyId
     * @return array
     */
    public function getOverdueSummary(int $companyId): array
    {
        $today = Carbon::today();
        $endOfWeek = Carbon::today()->endOfWeek();
        $endOfMonth = Carbon::today()->endOfMonth();

        $unpaidBills = Bill::where('company_id', $companyId)
            ->whereIn('paid_status', [
                Bill::PAID_STATUS_UNPAID,
                Bill::PAID_STATUS_PARTIALLY_PAID,
            ])
            ->whereNotIn('status', [Bill::STATUS_DRAFT])
            ->whereNotNull('due_date')
            ->with('payments:id,bill_id,amount')
            ->get();

        $overdue = ['count' => 0, 'total' => 0];
        $dueThisWeek = ['count' => 0, 'total' => 0];
        $dueThisMonth = ['count' => 0, 'total' => 0];

        foreach ($unpaidBills as $bill) {
            $dueDate = Carbon::parse($bill->due_date);
            $paidAmount = $bill->payments->sum('amount');
            $dueAmount = (int) ($bill->total - $paidAmount);

            if ($dueAmount <= 0) {
                continue;
            }

            if ($dueDate->lt($today)) {
                $overdue['count']++;
                $overdue['total'] += $dueAmount;
            } elseif ($dueDate->lte($endOfWeek)) {
                $dueThisWeek['count']++;
                $dueThisWeek['total'] += $dueAmount;
            } elseif ($dueDate->lte($endOfMonth)) {
                $dueThisMonth['count']++;
                $dueThisMonth['total'] += $dueAmount;
            }
        }

        return [
            'overdue' => $overdue,
            'due_this_week' => $dueThisWeek,
            'due_this_month' => $dueThisMonth,
        ];
    }
}

// CLAUDE-CHECKPOINT
