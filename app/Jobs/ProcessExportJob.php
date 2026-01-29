<?php

namespace App\Jobs;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExportJob;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600; // 10 minutes for large exports

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ExportJob $exportJob
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->exportJob->markAsProcessing();

            // Get data based on export type
            $data = $this->getData();

            // Generate file based on format
            $filePath = match ($this->exportJob->format) {
                'csv' => $this->generateCsv($data),
                'xlsx' => $this->generateXlsx($data),
                'pdf' => $this->generatePdf($data),
                default => throw new \Exception("Unsupported format: {$this->exportJob->format}"),
            };

            // Mark as completed
            $this->exportJob->markAsCompleted($filePath, count($data));

            Log::info("Export job {$this->exportJob->id} completed successfully", [
                'type' => $this->exportJob->type,
                'format' => $this->exportJob->format,
                'rows' => count($data),
            ]);
        } catch (\Exception $e) {
            $this->exportJob->markAsFailed($e->getMessage());

            Log::error("Export job {$this->exportJob->id} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get data for export based on type
     */
    protected function getData(): array
    {
        // Use direct where() instead of whereCompany() scope because the scope
        // relies on request()->header('company') which is null in queue context
        // Use setEagerLoads([]) to disable auto-loaded relationships that cause nested arrays
        $query = match ($this->exportJob->type) {
            'invoices' => Invoice::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'bills' => Bill::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'customers' => Customer::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'suppliers' => Supplier::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'expenses' => Expense::where('company_id', $this->exportJob->company_id)
                ->setEagerLoads([])
                ->with(['category:id,name', 'supplier:id,name']),
            'payments' => Payment::where('company_id', $this->exportJob->company_id)
                ->setEagerLoads([])
                ->with(['customer:id,name', 'paymentMethod:id,name']),
            'transactions' => \App\Models\Transaction::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'items' => \App\Models\Item::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'estimates' => \App\Models\Estimate::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'proforma_invoices' => \App\Models\ProformaInvoice::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            'recurring_invoices' => \App\Models\RecurringInvoice::where('company_id', $this->exportJob->company_id)->setEagerLoads([]),
            default => throw new \Exception("Unknown export type: {$this->exportJob->type}"),
        };

        // Apply filters from params
        if ($params = $this->exportJob->params) {
            if (isset($params['start_date'])) {
                $dateField = match ($this->exportJob->type) {
                    'invoices' => 'invoice_date',
                    'expenses' => 'expense_date',
                    'payments' => 'payment_date',
                    default => 'created_at',
                };
                $query->where($dateField, '>=', $params['start_date']);
            }

            if (isset($params['end_date'])) {
                $dateField = match ($this->exportJob->type) {
                    'invoices' => 'invoice_date',
                    'expenses' => 'expense_date',
                    'payments' => 'payment_date',
                    default => 'created_at',
                };
                $query->where($dateField, '<=', $params['end_date']);
            }

            if (isset($params['status'])) {
                $query->where('status', $params['status']);
            }
        }

        // Get data and flatten any remaining nested structures
        $results = $query->get();

        // Convert to array and include meaningful relationship data
        return $results->map(function ($model) {
            $data = $model->attributesToArray(); // Only raw attributes, no appends/relations

            // Add relationship names for expenses
            if ($this->exportJob->type === 'expenses') {
                $data['category_name'] = $model->category?->name ?? '';
                $data['supplier_name'] = $model->supplier?->name ?? '';
                // Format amount for readability (stored in cents)
                $data['amount_formatted'] = number_format($data['amount'] / 100, 2);
            }

            // Add relationship names for payments
            if ($this->exportJob->type === 'payments') {
                $data['customer_name'] = $model->customer?->name ?? '';
                $data['payment_method_name'] = $model->paymentMethod?->name ?? '';
                $data['amount_formatted'] = number_format($data['amount'] / 100, 2);
            }

            return $data;
        })->toArray();
    }

    /**
     * Generate CSV file
     */
    protected function generateCsv(array $data): string
    {
        $filename = $this->getFilename('csv');
        $path = "exports/{$this->exportJob->company_id}/{$filename}";

        Excel::store(new ExportCollection($data, $this->exportJob->type), $path, 'local', \Maatwebsite\Excel\Excel::CSV);

        return $path;
    }

    /**
     * Generate XLSX file
     */
    protected function generateXlsx(array $data): string
    {
        $filename = $this->getFilename('xlsx');
        $path = "exports/{$this->exportJob->company_id}/{$filename}";

        Excel::store(new ExportCollection($data, $this->exportJob->type), $path, 'local');

        return $path;
    }

    /**
     * Generate PDF file
     */
    protected function generatePdf(array $data): string
    {
        $filename = $this->getFilename('pdf');
        $path = "exports/{$this->exportJob->company_id}/{$filename}";

        // Use type-specific view if exists, otherwise use generic view
        $viewName = view()->exists('exports.'.$this->exportJob->type)
            ? 'exports.'.$this->exportJob->type
            : 'exports.generic';

        // Create PDF in landscape orientation for better table display
        $pdf = Pdf::loadView($viewName, [
            'data' => $data,
            'type' => $this->exportJob->type,
            'company' => $this->exportJob->company,
            'params' => $this->exportJob->params,
        ])->setPaper('a4', 'landscape');

        // Save to storage
        Storage::put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate filename for export
     */
    protected function getFilename(string $extension): string
    {
        $timestamp = now()->format('Y-m-d_His');

        return "{$this->exportJob->type}_{$timestamp}.{$extension}";
    }
}

/**
 * Simple Excel export collection
 */
class ExportCollection implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings
{
    public function __construct(
        protected array $data,
        protected string $type
    ) {}

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        if (empty($this->data)) {
            return [];
        }

        return array_keys($this->data[0]);
    }
}
// CLAUDE-CHECKPOINT: Added 'items' type support to export job processing
