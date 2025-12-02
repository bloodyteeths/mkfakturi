<?php

namespace App\Console\Commands;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Create Retroactive Stock Movements Command
 *
 * This command creates stock movements for existing bills and invoices
 * that were created before the stock module was enabled.
 *
 * Usage:
 *   php artisan stock:create-retroactive [options]
 *
 * Options:
 *   --company=<id>  Process only a specific company
 *   --type=<type>   Process only 'bills' or 'invoices' (default: both)
 *   --dry-run       Show what would be processed without making changes
 *   --force         Skip confirmation prompt
 */
class CreateRetroactiveStockMovements extends Command
{
    protected $signature = 'stock:create-retroactive
                            {--company= : Process only a specific company ID}
                            {--type= : Process only "bills" or "invoices" (default: both)}
                            {--dry-run : Show what would be processed without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Create stock movements for existing bills and invoices (retroactive)';

    protected StockService $stockService;

    protected array $stats = [
        'bills_processed' => 0,
        'bill_items_processed' => 0,
        'bill_items_skipped' => 0,
        'invoices_processed' => 0,
        'invoice_items_processed' => 0,
        'invoice_items_skipped' => 0,
        'errors' => 0,
    ];

    public function __construct(StockService $stockService)
    {
        parent::__construct();
        $this->stockService = $stockService;
    }

    public function handle(): int
    {
        // Check if stock module is enabled
        if (! StockService::isEnabled()) {
            $this->error('Stock module is not enabled. Set FACTURINO_STOCK_V1_ENABLED=true in .env');

            return Command::FAILURE;
        }

        $companyId = $this->option('company');
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Validate type option
        if ($type && ! in_array($type, ['bills', 'invoices'])) {
            $this->error('Invalid type. Use "bills" or "invoices".');

            return Command::FAILURE;
        }

        $this->info('=== Retroactive Stock Movement Creator ===');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get companies to process
        $companiesQuery = Company::query();
        if ($companyId) {
            $companiesQuery->where('id', $companyId);
        }
        $companies = $companiesQuery->get();

        if ($companies->isEmpty()) {
            $this->error('No companies found to process.');

            return Command::FAILURE;
        }

        // Show summary of what will be processed
        $this->showPreviewSummary($companies, $type);

        // Confirm unless forced
        if (! $dryRun && ! $force) {
            if (! $this->confirm('Do you want to proceed with creating retroactive stock movements?')) {
                $this->info('Operation cancelled.');

                return Command::SUCCESS;
            }
        }

        // Process each company
        foreach ($companies as $company) {
            $this->processCompany($company, $type, $dryRun);
        }

        // Show final statistics
        $this->showStats($dryRun);

        return $this->stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Show preview of what will be processed.
     */
    protected function showPreviewSummary($companies, ?string $type): void
    {
        $this->info("Companies to process: {$companies->count()}");

        foreach ($companies as $company) {
            $this->line("  - {$company->name} (ID: {$company->id})");

            // Count trackable items
            $trackableItems = Item::where('company_id', $company->id)
                ->where('track_quantity', true)
                ->count();
            $this->line("    Trackable items: {$trackableItems}");

            if (! $type || $type === 'bills') {
                // Count bills without stock movements
                $billItemsToProcess = $this->getBillItemsWithoutMovements($company->id)->count();
                $this->line("    Bill items to process: {$billItemsToProcess}");
            }

            if (! $type || $type === 'invoices') {
                // Count invoices without stock movements
                $invoiceItemsToProcess = $this->getInvoiceItemsWithoutMovements($company->id)->count();
                $this->line("    Invoice items to process: {$invoiceItemsToProcess}");
            }
        }

        $this->newLine();
    }

    /**
     * Process a single company.
     */
    protected function processCompany(Company $company, ?string $type, bool $dryRun): void
    {
        $this->info("Processing company: {$company->name} (ID: {$company->id})");

        // Get or create default warehouse for this company
        $defaultWarehouse = Warehouse::getOrCreateDefault($company->id);

        if (! $type || $type === 'bills') {
            $this->processBills($company, $defaultWarehouse, $dryRun);
        }

        if (! $type || $type === 'invoices') {
            $this->processInvoices($company, $defaultWarehouse, $dryRun);
        }

        $this->newLine();
    }

    /**
     * Process bills for a company.
     */
    protected function processBills(Company $company, Warehouse $defaultWarehouse, bool $dryRun): void
    {
        $billItems = $this->getBillItemsWithoutMovements($company->id);

        if ($billItems->isEmpty()) {
            $this->line('  No bill items to process.');

            return;
        }

        $this->line("  Processing {$billItems->count()} bill items...");

        $bar = $this->output->createProgressBar($billItems->count());
        $bar->start();

        foreach ($billItems as $billItem) {
            try {
                $result = $this->processBillItem($billItem, $defaultWarehouse, $dryRun);
                if ($result) {
                    $this->stats['bill_items_processed']++;
                } else {
                    $this->stats['bill_items_skipped']++;
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error('Retroactive stock movement failed for bill item', [
                    'bill_item_id' => $billItem->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Count unique bills
        $uniqueBills = $billItems->pluck('bill_id')->unique()->count();
        $this->stats['bills_processed'] += $uniqueBills;
    }

    /**
     * Process a single bill item.
     */
    protected function processBillItem(BillItem $billItem, Warehouse $defaultWarehouse, bool $dryRun): bool
    {
        // Check if item exists and has track_quantity enabled
        $item = Item::find($billItem->item_id);
        if (! $item || ! $item->track_quantity) {
            return false;
        }

        // Get warehouse from bill item or use default
        $warehouseId = $billItem->warehouse_id ?? $defaultWarehouse->id;

        if ($dryRun) {
            return true;
        }

        // Create stock IN movement
        $unitCost = (int) ($billItem->base_price ?? $billItem->price);

        $this->stockService->recordStockIn(
            $billItem->company_id,
            $warehouseId,
            $billItem->item_id,
            (float) $billItem->quantity,
            $unitCost,
            StockMovement::SOURCE_BILL_ITEM,
            $billItem->id,
            $billItem->bill?->bill_date,
            "[Retroactive] Stock IN from Bill #{$billItem->bill?->bill_number}",
            [
                'bill_id' => $billItem->bill_id,
                'bill_number' => $billItem->bill?->bill_number,
                'retroactive' => true,
            ]
        );

        return true;
    }

    /**
     * Process invoices for a company.
     */
    protected function processInvoices(Company $company, Warehouse $defaultWarehouse, bool $dryRun): void
    {
        $invoiceItems = $this->getInvoiceItemsWithoutMovements($company->id);

        if ($invoiceItems->isEmpty()) {
            $this->line('  No invoice items to process.');

            return;
        }

        $this->line("  Processing {$invoiceItems->count()} invoice items...");

        $bar = $this->output->createProgressBar($invoiceItems->count());
        $bar->start();

        foreach ($invoiceItems as $invoiceItem) {
            try {
                $result = $this->processInvoiceItem($invoiceItem, $defaultWarehouse, $dryRun);
                if ($result) {
                    $this->stats['invoice_items_processed']++;
                } else {
                    $this->stats['invoice_items_skipped']++;
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                Log::error('Retroactive stock movement failed for invoice item', [
                    'invoice_item_id' => $invoiceItem->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Count unique invoices
        $uniqueInvoices = $invoiceItems->pluck('invoice_id')->unique()->count();
        $this->stats['invoices_processed'] += $uniqueInvoices;
    }

    /**
     * Process a single invoice item.
     */
    protected function processInvoiceItem(InvoiceItem $invoiceItem, Warehouse $defaultWarehouse, bool $dryRun): bool
    {
        // Check if item exists and has track_quantity enabled
        $item = Item::find($invoiceItem->item_id);
        if (! $item || ! $item->track_quantity) {
            return false;
        }

        // Get warehouse from invoice item or use default
        $warehouseId = $invoiceItem->warehouse_id ?? $defaultWarehouse->id;

        if ($dryRun) {
            return true;
        }

        // Create stock OUT movement
        $this->stockService->recordStockOut(
            $invoiceItem->company_id,
            $warehouseId,
            $invoiceItem->item_id,
            (float) $invoiceItem->quantity,
            StockMovement::SOURCE_INVOICE_ITEM,
            $invoiceItem->id,
            $invoiceItem->invoice?->invoice_date,
            "[Retroactive] Stock OUT from Invoice #{$invoiceItem->invoice?->invoice_number}",
            [
                'invoice_id' => $invoiceItem->invoice_id,
                'invoice_number' => $invoiceItem->invoice?->invoice_number,
                'retroactive' => true,
            ]
        );

        return true;
    }

    /**
     * Get bill items that don't have stock movements yet.
     */
    protected function getBillItemsWithoutMovements(int $companyId)
    {
        return BillItem::where('company_id', $companyId)
            ->whereNotNull('item_id')
            ->whereHas('item', function ($query) {
                $query->where('track_quantity', true);
            })
            ->whereDoesntHave('stockMovements')
            ->with(['bill', 'item'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get invoice items that don't have stock movements yet.
     */
    protected function getInvoiceItemsWithoutMovements(int $companyId)
    {
        return InvoiceItem::where('company_id', $companyId)
            ->whereNotNull('item_id')
            ->whereHas('item', function ($query) {
                $query->where('track_quantity', true);
            })
            ->whereDoesntHave('stockMovements')
            ->with(['invoice', 'item'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Show final statistics.
     */
    protected function showStats(bool $dryRun): void
    {
        $this->newLine();
        $this->info('=== Summary ===');

        $prefix = $dryRun ? 'Would process' : 'Processed';

        $this->line("Bills: {$this->stats['bills_processed']}");
        $this->line("  {$prefix}: {$this->stats['bill_items_processed']} bill items");
        $this->line("  Skipped: {$this->stats['bill_items_skipped']} bill items (non-trackable)");

        $this->line("Invoices: {$this->stats['invoices_processed']}");
        $this->line("  {$prefix}: {$this->stats['invoice_items_processed']} invoice items");
        $this->line("  Skipped: {$this->stats['invoice_items_skipped']} invoice items (non-trackable)");

        if ($this->stats['errors'] > 0) {
            $this->error("Errors: {$this->stats['errors']} (check logs for details)");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Use --force to execute without prompts.');
        }
    }
}
// CLAUDE-CHECKPOINT
