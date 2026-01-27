<?php

namespace App\Jobs;

use App\Mail\DataExportReadyMail;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\UserDataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use ZipArchive;

class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public $timeout = 600; // 10 minutes for large exports

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserDataExport $export
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting data export job", ['export_id' => $this->export->id]);

            $this->export->markAsProcessing();

            $user = $this->export->user;

            if (! $user) {
                throw new \Exception('User not found for export');
            }

            Log::info("Data export: Processing for user", [
                'export_id' => $this->export->id,
                'user_id' => $user->id,
            ]);

            // Create temporary directory for export files
            $tempDir = storage_path('app/temp/user-data-export-'.$this->export->id);
            if (! file_exists($tempDir)) {
                if (! mkdir($tempDir, 0755, true)) {
                    throw new \Exception('Could not create temporary directory: '.$tempDir);
                }
            }

            Log::info("Data export: Created temp directory", ['path' => $tempDir]);

            // 1. Export user profile data
            $this->exportUserProfile($user, $tempDir);
            Log::info("Data export: Profile exported");

            // 2. Export companies data
            $this->exportCompanies($user, $tempDir);
            Log::info("Data export: Companies exported");

            // 3. Export invoices data
            $this->exportInvoices($user, $tempDir);
            Log::info("Data export: Invoices exported");

            // 4. Export customers data
            $this->exportCustomers($user, $tempDir);
            Log::info("Data export: Customers exported");

            // 5. Export expenses data
            $this->exportExpenses($user, $tempDir);
            Log::info("Data export: Expenses exported");

            // 6. Export payments data
            $this->exportPayments($user, $tempDir);
            Log::info("Data export: Payments exported");

            // Create ZIP file
            $zipPath = $this->createZipArchive($tempDir);
            Log::info("Data export: ZIP created", ['path' => $zipPath]);

            // Get file size
            $fileSize = Storage::size($zipPath);

            // Mark as completed
            $this->export->markAsCompleted($zipPath, $fileSize);

            // Clean up temp directory
            $this->cleanupTempDirectory($tempDir);
            Log::info("Data export: Temp directory cleaned up");

            // Send email notification to user
            try {
                Mail::to($user->email)->send(new DataExportReadyMail($user, $this->export));
                Log::info("User data export email sent to {$user->email}", [
                    'export_id' => $this->export->id,
                ]);
            } catch (\Exception $e) {
                // Don't fail the job if email fails - export is still ready
                Log::warning("Failed to send data export email", [
                    'export_id' => $this->export->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info("User data export {$this->export->id} completed successfully", [
                'user_id' => $user->id,
                'file_size' => $fileSize,
            ]);
        } catch (\Exception $e) {
            $this->export->markAsFailed($e->getMessage());

            Log::error("User data export {$this->export->id} failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Export user profile information
     */
    protected function exportUserProfile($user, string $tempDir): void
    {
        $profileData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone ?? '',
            'created_at' => $user->created_at?->toDateTimeString(),
            'updated_at' => $user->updated_at?->toDateTimeString(),
        ];

        $jsonPath = $tempDir.'/profile.json';
        file_put_contents($jsonPath, json_encode($profileData, JSON_PRETTY_PRINT));
    }

    /**
     * Export companies data
     */
    protected function exportCompanies($user, string $tempDir): void
    {
        $companies = $user->companies()->with(['address', 'address.country'])->get();

        $companiesData = $companies->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'unique_hash' => $company->unique_hash ?? '',
                'address_street_1' => $company->address->address_street_1 ?? '',
                'address_street_2' => $company->address->address_street_2 ?? '',
                'city' => $company->address->city ?? '',
                'state' => $company->address->state ?? '',
                'zip' => $company->address->zip ?? '',
                'country' => $company->address->country->name ?? '',
                'phone' => $company->address->phone ?? '',
                'website' => $company->website ?? '',
                'created_at' => $company->created_at?->toDateTimeString(),
            ];
        })->toArray();

        $jsonPath = $tempDir.'/companies.json';
        file_put_contents($jsonPath, json_encode($companiesData, JSON_PRETTY_PRINT));
    }

    /**
     * Export invoices data
     */
    protected function exportInvoices($user, string $tempDir): void
    {
        $invoices = Invoice::whereHas('company.users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->with(['customer', 'currency'])->get();

        if ($invoices->isEmpty()) {
            return;
        }

        $csv = Writer::createFromPath($tempDir.'/invoices.csv', 'w+');
        $csv->insertOne([
            'Invoice Number',
            'Customer Name',
            'Invoice Date',
            'Due Date',
            'Status',
            'Total Amount',
            'Currency',
            'Created At',
        ]);

        foreach ($invoices as $invoice) {
            $csv->insertOne([
                $invoice->invoice_number ?? '',
                $invoice->customer->name ?? '',
                $invoice->invoice_date?->toDateString() ?? '',
                $invoice->due_date?->toDateString() ?? '',
                $invoice->status ?? '',
                $invoice->total ?? 0,
                $invoice->currency->code ?? '',
                $invoice->created_at?->toDateTimeString() ?? '',
            ]);
        }
    }

    /**
     * Export customers data
     */
    protected function exportCustomers($user, string $tempDir): void
    {
        $customers = Customer::whereHas('company.users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->get();

        if ($customers->isEmpty()) {
            return;
        }

        $csv = Writer::createFromPath($tempDir.'/customers.csv', 'w+');
        $csv->insertOne([
            'Name',
            'Email',
            'Phone',
            'Company Name',
            'Website',
            'Created At',
        ]);

        foreach ($customers as $customer) {
            $csv->insertOne([
                $customer->name ?? '',
                $customer->email ?? '',
                $customer->phone ?? '',
                $customer->company_name ?? '',
                $customer->website ?? '',
                $customer->created_at?->toDateTimeString() ?? '',
            ]);
        }
    }

    /**
     * Export expenses data
     */
    protected function exportExpenses($user, string $tempDir): void
    {
        $expenses = Expense::whereHas('company.users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->with(['category', 'customer', 'currency'])->get();

        if ($expenses->isEmpty()) {
            return;
        }

        $csv = Writer::createFromPath($tempDir.'/expenses.csv', 'w+');
        $csv->insertOne([
            'Expense Number',
            'Category',
            'Customer',
            'Expense Date',
            'Amount',
            'Currency',
            'Notes',
            'Created At',
        ]);

        foreach ($expenses as $expense) {
            $csv->insertOne([
                $expense->expense_number ?? '',
                $expense->category->name ?? '',
                $expense->customer->name ?? '',
                $expense->expense_date?->toDateString() ?? '',
                $expense->amount ?? 0,
                $expense->currency->code ?? '',
                $expense->notes ?? '',
                $expense->created_at?->toDateTimeString() ?? '',
            ]);
        }
    }

    /**
     * Export payments data
     */
    protected function exportPayments($user, string $tempDir): void
    {
        $payments = Payment::whereHas('company.users', function ($query) use ($user) {
            $query->where('users.id', $user->id);
        })->with(['customer', 'invoice', 'currency', 'paymentMethod'])->get();

        if ($payments->isEmpty()) {
            return;
        }

        $csv = Writer::createFromPath($tempDir.'/payments.csv', 'w+');
        $csv->insertOne([
            'Payment Number',
            'Customer',
            'Invoice Number',
            'Payment Date',
            'Amount',
            'Currency',
            'Payment Method',
            'Notes',
            'Created At',
        ]);

        foreach ($payments as $payment) {
            $csv->insertOne([
                $payment->payment_number ?? '',
                $payment->customer->name ?? '',
                $payment->invoice->invoice_number ?? '',
                $payment->payment_date?->toDateString() ?? '',
                $payment->amount ?? 0,
                $payment->currency->code ?? '',
                $payment->paymentMethod->name ?? '',
                $payment->notes ?? '',
                $payment->created_at?->toDateTimeString() ?? '',
            ]);
        }
    }

    /**
     * Create ZIP archive from temporary directory
     */
    protected function createZipArchive(string $tempDir): string
    {
        $zipFileName = 'user-data-export-'.$this->export->id.'-'.now()->format('YmdHis').'.zip';
        $zipPath = 'user-data-exports/'.$this->export->user_id.'/'.$zipFileName;
        $fullZipPath = storage_path('app/'.$zipPath);

        // Create directory if it doesn't exist
        $zipDir = dirname($fullZipPath);
        if (! file_exists($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($fullZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Could not create ZIP archive');
        }

        // Add all files from temp directory
        $files = scandir($tempDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $filePath = $tempDir.'/'.$file;
            if (is_file($filePath)) {
                $zip->addFile($filePath, $file);
            }
        }

        // Add README file
        $readme = $this->generateReadme();
        $zip->addFromString('README.txt', $readme);

        $zip->close();

        return $zipPath;
    }

    /**
     * Generate README content for the export
     */
    protected function generateReadme(): string
    {
        return <<<'README'
GDPR DATA EXPORT
================

This archive contains all your personal and business data stored in our system.

Files Included:
- profile.json: Your user profile information
- companies.json: Companies you have access to
- invoices.csv: Summary of all invoices
- customers.csv: Customer records
- expenses.csv: Expense records
- payments.csv: Payment records

This data export was generated in compliance with GDPR Article 20 (Right to Data Portability).

Export Date: {date}

If you have any questions about this data export, please contact our support team.

README;
    }

    /**
     * Clean up temporary directory
     */
    protected function cleanupTempDirectory(string $tempDir): void
    {
        if (! is_dir($tempDir)) {
            return;
        }

        $files = array_diff(scandir($tempDir), ['.', '..']);
        foreach ($files as $file) {
            $filePath = $tempDir.'/'.$file;
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        rmdir($tempDir);
    }
}
// CLAUDE-CHECKPOINT
