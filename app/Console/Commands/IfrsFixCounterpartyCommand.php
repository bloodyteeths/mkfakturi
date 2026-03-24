<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Fix missing counterparty_name on IFRS line items
 *
 * The postInvoice/postBill/postPayment/postBillPayment/postCreditNote methods
 * were not setting counterparty_name on AR/AP line items, so the ledger card
 * journal query (which filters by counterparty_name) missed these entries.
 *
 * This command backfills counterparty_name from the parent model (invoice→customer,
 * bill→supplier, payment→customer, bill_payment→bill→supplier).
 */
class IfrsFixCounterpartyCommand extends Command
{
    protected $signature = 'ifrs:fix-counterparty
                            {--company= : Process only a specific company ID}
                            {--dry-run : Show what would be fixed without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Backfill missing counterparty_name on IFRS AR/AP line items';

    public function handle(): int
    {
        $companyId = $this->option('company');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('=== IFRS Fix Counterparty Names ===');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // 1. Fix invoices (AR line items from postInvoice)
        $invoiceCount = $this->fixFromInvoices($companyId, $dryRun, $force);

        // 2. Fix payments (AR line items from postPayment)
        $paymentCount = $this->fixFromPayments($companyId, $dryRun, $force);

        // 3. Fix bills (AP line items from postBill)
        $billCount = $this->fixFromBills($companyId, $dryRun, $force);

        // 4. Fix bill payments (AP line items from postBillPayment)
        $billPaymentCount = $this->fixFromBillPayments($companyId, $dryRun, $force);

        // 5. Fix credit notes (AR line items from postCreditNote)
        $creditNoteCount = $this->fixFromCreditNotes($companyId, $dryRun, $force);

        $total = $invoiceCount + $paymentCount + $billCount + $billPaymentCount + $creditNoteCount;

        $this->newLine();
        $prefix = $dryRun ? 'Would fix' : 'Fixed';
        $this->info("=== Summary ===");
        $this->line("  Invoices (AR):       {$prefix} {$invoiceCount}");
        $this->line("  Payments (AR):       {$prefix} {$paymentCount}");
        $this->line("  Bills (AP):          {$prefix} {$billCount}");
        $this->line("  Bill Payments (AP):  {$prefix} {$billPaymentCount}");
        $this->line("  Credit Notes (AR):   {$prefix} {$creditNoteCount}");
        $this->newLine();
        $this->info("Total: {$prefix} {$total} line items");

        return Command::SUCCESS;
    }

    private function fixFromInvoices(?string $companyId, bool $dryRun, bool $force): int
    {
        // Find invoice AR line items missing counterparty_name
        $query = DB::table('ifrs_line_items as li')
            ->join('invoices as inv', 'li.transaction_id', '=', 'inv.ifrs_transaction_id')
            ->join('customers as c', 'inv.customer_id', '=', 'c.id')
            ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
            ->where('a.account_type', 'RECEIVABLE')
            ->whereNull('li.counterparty_name')
            ->whereNotNull('inv.ifrs_transaction_id');

        if ($companyId) {
            $query->where('inv.company_id', $companyId);
        }

        $rows = $query->select(['li.id', 'c.name as customer_name', 'inv.invoice_number'])->get();

        $this->line("  Invoices (AR): {$rows->count()} line items to fix");

        if ($rows->isEmpty() || $dryRun) {
            return $rows->count();
        }

        foreach ($rows as $row) {
            DB::table('ifrs_line_items')->where('id', $row->id)
                ->update(['counterparty_name' => $row->customer_name]);
        }

        return $rows->count();
    }

    private function fixFromPayments(?string $companyId, bool $dryRun, bool $force): int
    {
        $query = DB::table('ifrs_line_items as li')
            ->join('payments as p', 'li.transaction_id', '=', 'p.ifrs_transaction_id')
            ->join('customers as c', 'p.customer_id', '=', 'c.id')
            ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
            ->where('a.account_type', 'RECEIVABLE')
            ->whereNull('li.counterparty_name')
            ->whereNotNull('p.ifrs_transaction_id');

        if ($companyId) {
            $query->where('p.company_id', $companyId);
        }

        $rows = $query->select(['li.id', 'c.name as customer_name'])->get();

        $this->line("  Payments (AR): {$rows->count()} line items to fix");

        if ($rows->isEmpty() || $dryRun) {
            return $rows->count();
        }

        foreach ($rows as $row) {
            DB::table('ifrs_line_items')->where('id', $row->id)
                ->update(['counterparty_name' => $row->customer_name]);
        }

        return $rows->count();
    }

    private function fixFromBills(?string $companyId, bool $dryRun, bool $force): int
    {
        $query = DB::table('ifrs_line_items as li')
            ->join('bills as b', 'li.transaction_id', '=', 'b.ifrs_transaction_id')
            ->join('suppliers as s', 'b.supplier_id', '=', 's.id')
            ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
            ->where('a.account_type', 'PAYABLE')
            ->whereNull('li.counterparty_name')
            ->whereNotNull('b.ifrs_transaction_id');

        if ($companyId) {
            $query->where('b.company_id', $companyId);
        }

        $rows = $query->select(['li.id', 's.name as supplier_name'])->get();

        $this->line("  Bills (AP): {$rows->count()} line items to fix");

        if ($rows->isEmpty() || $dryRun) {
            return $rows->count();
        }

        foreach ($rows as $row) {
            DB::table('ifrs_line_items')->where('id', $row->id)
                ->update(['counterparty_name' => $row->supplier_name]);
        }

        return $rows->count();
    }

    private function fixFromBillPayments(?string $companyId, bool $dryRun, bool $force): int
    {
        $query = DB::table('ifrs_line_items as li')
            ->join('bill_payments as bp', 'li.transaction_id', '=', 'bp.ifrs_transaction_id')
            ->join('bills as b', 'bp.bill_id', '=', 'b.id')
            ->join('suppliers as s', 'b.supplier_id', '=', 's.id')
            ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
            ->where('a.account_type', 'PAYABLE')
            ->whereNull('li.counterparty_name')
            ->whereNotNull('bp.ifrs_transaction_id');

        if ($companyId) {
            $query->where('bp.company_id', $companyId);
        }

        $rows = $query->select(['li.id', 's.name as supplier_name'])->get();

        $this->line("  Bill Payments (AP): {$rows->count()} line items to fix");

        if ($rows->isEmpty() || $dryRun) {
            return $rows->count();
        }

        foreach ($rows as $row) {
            DB::table('ifrs_line_items')->where('id', $row->id)
                ->update(['counterparty_name' => $row->supplier_name]);
        }

        return $rows->count();
    }

    private function fixFromCreditNotes(?string $companyId, bool $dryRun, bool $force): int
    {
        // Credit notes use the credit_notes table
        $hasCreditNotes = DB::getSchemaBuilder()->hasTable('credit_notes');
        if (!$hasCreditNotes) {
            $this->line("  Credit Notes (AR): table not found, skipping");
            return 0;
        }

        $query = DB::table('ifrs_line_items as li')
            ->join('credit_notes as cn', 'li.transaction_id', '=', 'cn.ifrs_transaction_id')
            ->join('customers as c', 'cn.customer_id', '=', 'c.id')
            ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
            ->where('a.account_type', 'RECEIVABLE')
            ->whereNull('li.counterparty_name')
            ->whereNotNull('cn.ifrs_transaction_id');

        if ($companyId) {
            $query->where('cn.company_id', $companyId);
        }

        $rows = $query->select(['li.id', 'c.name as customer_name'])->get();

        $this->line("  Credit Notes (AR): {$rows->count()} line items to fix");

        if ($rows->isEmpty() || $dryRun) {
            return $rows->count();
        }

        foreach ($rows as $row) {
            DB::table('ifrs_line_items')->where('id', $row->id)
                ->update(['counterparty_name' => $row->customer_name]);
        }

        return $rows->count();
    }
}
// CLAUDE-CHECKPOINT
