<?php

use App\Http\Controllers\V1\Admin\Auth\LoginController;
use App\Http\Controllers\V1\Admin\Expense\ShowReceiptController;
use App\Http\Controllers\V1\Admin\General\BootstrapController;
use App\Http\Controllers\V1\Admin\Report\BalanceSheetReportController;
use App\Http\Controllers\V1\Admin\Report\CustomerSalesReportController;
use App\Http\Controllers\V1\Admin\Report\ExpensesReportController;
use App\Http\Controllers\V1\Admin\Report\IncomeStatementReportController;
use App\Http\Controllers\V1\Admin\Report\ItemSalesReportController;
use App\Http\Controllers\V1\Admin\Report\ProfitLossReportController;
use App\Http\Controllers\V1\Admin\Report\TaxSummaryReportController;
use App\Http\Controllers\V1\Admin\Report\TrialBalanceReportController;
use App\Http\Controllers\V1\Customer\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\V1\Customer\EstimatePdfController as CustomerEstimatePdfController;
use App\Http\Controllers\V1\Customer\InvoicePdfController as CustomerInvoicePdfController;
use App\Http\Controllers\V1\Customer\PaymentPdfController as CustomerPaymentPdfController;
use App\Http\Controllers\V1\Modules\ScriptController;
use App\Http\Controllers\V1\Modules\StyleController;
use App\Http\Controllers\V1\PDF\DownloadReceiptController;
use App\Http\Controllers\V1\PDF\EstimatePdfController;
use App\Http\Controllers\V1\PDF\InvoicePdfController;
use App\Http\Controllers\V1\PDF\PaymentPdfController;
// use App\Http\Controllers\PrometheusController; // Disabled - dependency not installed
use App\Models\Company;
use Illuminate\Support\Facades\Route;

// Module Asset Includes
// ----------------------------------------------

Route::get('/modules/styles/{style}', StyleController::class);

Route::get('/modules/scripts/{script}', ScriptController::class);

// Health check endpoint - comprehensive system health monitoring
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'health']);

// Admin Auth
// ----------------------------------------------

Route::post('login', [LoginController::class, 'login']);

Route::post('auth/logout', function () {
    Auth::guard('web')->logout();
});

// Customer auth
// ----------------------------------------------

Route::post('/{company:slug}/customer/login', CustomerLoginController::class);

Route::post('/{company:slug}/customer/logout', function () {
    Auth::guard('customer')->logout();
});

// SPA Bootstrap for authenticated admin users
// Uses session-based auth (web guard) instead of Sanctum
Route::get('/api/v1/bootstrap', BootstrapController::class)
    ->middleware(['web', 'install', 'auth', 'company', 'bouncer']);

// Report PDF & Expense Endpoints
// ----------------------------------------------

Route::middleware('auth:sanctum')->prefix('reports')->group(function () {

    // sales report by customer
    // ----------------------------------
    Route::get('/sales/customers/{hash}', CustomerSalesReportController::class);

    // sales report by items
    // ----------------------------------
    Route::get('/sales/items/{hash}', ItemSalesReportController::class);

    // report for expenses
    // ----------------------------------
    Route::get('/expenses/{hash}', ExpensesReportController::class);

    // report for tax summary
    // ----------------------------------
    Route::get('/tax-summary/{hash}', TaxSummaryReportController::class);

    // report for profit and loss
    // ----------------------------------
    Route::get('/profit-loss/{hash}', ProfitLossReportController::class);

    // IFRS Accounting Reports
    // ----------------------------------
    Route::get('/trial-balance/{hash}', TrialBalanceReportController::class);
    Route::get('/balance-sheet/{hash}', BalanceSheetReportController::class);
    Route::get('/income-statement/{hash}', IncomeStatementReportController::class);

    // download expense receipt
    // -------------------------------------------------
    Route::get('/expenses/{expense}/download-receipt', DownloadReceiptController::class);
    Route::get('/expenses/{expense}/receipt', ShowReceiptController::class);
});

// CLAUDE-CHECKPOINT

// PDF Endpoints
// ----------------------------------------------
// ВАЖНО: web middleware е потребен за session/cookie поддршка во iframe
Route::middleware(['web', 'pdf-company', 'company', 'pdf-auth'])->group(function () {

    //  invoice pdf
    // -------------------------------------------------
    Route::get('/invoices/pdf/{invoice:unique_hash}', InvoicePdfController::class);

    // estimate pdf
    // -------------------------------------------------
    Route::get('/estimates/pdf/{estimate:unique_hash}', EstimatePdfController::class);

    // payment pdf
    // -------------------------------------------------
    Route::get('/payments/pdf/{payment:unique_hash}', PaymentPdfController::class);
});

// customer pdf endpoints for invoice, estimate and Payment
// -------------------------------------------------

Route::prefix('/customer')->group(function () {
    Route::get('/invoices/{email_log:token}', [CustomerInvoicePdfController::class, 'getInvoice']);
    Route::get('/invoices/view/{email_log:token}', [CustomerInvoicePdfController::class, 'getPdf'])->name('invoice');

    Route::get('/estimates/{email_log:token}', [CustomerEstimatePdfController::class, 'getEstimate']);
    Route::get('/estimates/view/{email_log:token}', [CustomerEstimatePdfController::class, 'getPdf'])->name('estimate');

    Route::get('/payments/{email_log:token}', [CustomerPaymentPdfController::class, 'getPayment']);
    Route::get('/payments/view/{email_log:token}', [CustomerPaymentPdfController::class, 'getPdf'])->name('payment');
});

// CPAY Payment Callback
// ----------------------------------------------

Route::post('/payment/cpay/callback', [App\Http\Controllers\CpayCallbackController::class, 'handle'])->name('payment.cpay.callback');

// Monitoring Endpoints
// ----------------------------------------------
// Feature flag: FEATURE_MONITORING
// Requires: arquivei/laravel-prometheus-exporter, laravel/telescope
use App\Http\Controllers\PrometheusController;

// Simple test endpoint (always available)
Route::get('/metrics/test', function() {
    return response('# Test metrics endpoint working', 200, [
        'Content-Type' => 'text/plain; charset=utf-8'
    ]);
});

// Debug endpoint to test PrometheusExporter
Route::get('/metrics/debug', function() {
    try {
        $output = "# Debug info\n";

        // Test 1: Check if PrometheusExporter class exists
        $output .= "# PrometheusExporter class exists: " . (class_exists(\Arquivei\LaravelPrometheusExporter\PrometheusExporter::class) ? 'YES' : 'NO') . "\n";

        // Test 2: Try to resolve from container
        try {
            $prometheus = app(\Arquivei\LaravelPrometheusExporter\PrometheusExporter::class);
            $output .= "# PrometheusExporter resolved: YES\n";
            $output .= "# PrometheusExporter class: " . get_class($prometheus) . "\n";
        } catch (\Exception $e) {
            $output .= "# PrometheusExporter resolve FAILED: " . $e->getMessage() . "\n";
            $output .= "# Error class: " . get_class($e) . "\n";
        }

        // Test 3: Check config
        $output .= "# Config namespace: " . config('prometheus-exporter.namespace', 'NOT SET') . "\n";
        $output .= "# Config storage: " . config('prometheus-exporter.storage_adapter', 'NOT SET') . "\n";

        return response($output, 200, [
            'Content-Type' => 'text/plain; charset=utf-8'
        ]);
    } catch (\Exception $e) {
        return response("# Critical error: " . $e->getMessage() . "\n# File: " . $e->getFile() . ":" . $e->getLine(), 500, [
            'Content-Type' => 'text/plain; charset=utf-8'
        ]);
    }
});

Route::middleware('feature:monitoring')->group(function () {
    Route::get('/metrics', [PrometheusController::class, 'metrics'])->name('metrics');
    Route::get('/metrics/health', [PrometheusController::class, 'health'])->name('prometheus.health');
});

// Setup for installation of app
// ----------------------------------------------

Route::get('/installation', function () {
    return view('app');
})->name('install')->middleware('redirect-if-installed');

// Move other http requests to the Vue App
// -------------------------------------------------

Route::get('/admin/{vue?}', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('admin.dashboard')->middleware(['install', 'redirect-if-unauthenticated']);

Route::get('{company:slug}/customer/{vue?}', function (Company $company) {
    return view('app')->with([
        'customer_logo' => logo_asset_url(get_company_setting('customer_portal_logo', $company->id)),
        'current_theme' => get_company_setting('customer_portal_theme', $company->id),
        'customer_page_title' => get_company_setting('customer_portal_page_title', $company->id),
    ]);
})->where('vue', '[\/\w\.-]*')->name('customer.dashboard')->middleware(['install']);

Route::get('/', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('home')->middleware(['install', 'guest']);

Route::get('/reset-password/{token}', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('reset-password')->middleware(['install', 'guest']);

Route::get('/forgot-password', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('forgot-password')->middleware(['install', 'guest']);

Route::get('/login', function () {
    return view('app');
})->where('vue', '[\/\w\.-]*')->name('login')->middleware(['install', 'guest']);

// Webhook Routes
// ----------------------------------------------

// Paddle webhook endpoint (no CSRF protection needed for webhooks)
Route::post('/webhooks/paddle', [\Modules\Mk\Http\PaddleWebhookController::class, 'handle'])
    ->name('webhooks.paddle')
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Health check endpoints for Docker
// PSD2 Banking OAuth Callback
// ----------------------------------
// Static callback URL for OAuth provider registration (company ID passed via state parameter)
Route::get('/banking/callback/{bank}', [\App\Http\Controllers\V1\Admin\BankAuthController::class, 'handleCallback'])
    ->name('banking.callback');

// Simple ping endpoint for Railway healthcheck (no DB required)
Route::get('/ping', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()], 200);
});

Route::get('/ready', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Debug route to view Laravel logs (only in production for Railway debugging)
if (env('APP_ENV') === 'production' && env('RAILWAY_ENVIRONMENT')) {
    Route::get('/debug/logs', function () {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $lastLines = implode("\n", array_slice(explode("\n", $logs), -200)); // Last 200 lines
            return response('<pre>' . htmlspecialchars($lastLines) . '</pre>');
        }
        return response('No log file found');
    });

    // Debug route to check PHP-FPM errors
    Route::get('/debug/php-errors', function () {
        $output = "=== PHP-FPM Error Log ===\n\n";

        $phpFpmLog = storage_path('logs/php-fpm-error.log');
        if (file_exists($phpFpmLog)) {
            $logs = file_get_contents($phpFpmLog);
            $lastLines = implode("\n", array_slice(explode("\n", $logs), -100));
            $output .= "PHP-FPM Errors:\n" . $lastLines . "\n\n";
        } else {
            $output .= "PHP-FPM error log not found at: $phpFpmLog\n\n";
        }

        $laravelLog = storage_path('logs/laravel.log');
        if (file_exists($laravelLog)) {
            $logs = file_get_contents($laravelLog);
            $lastLines = implode("\n", array_slice(explode("\n", $logs), -100));
            $output .= "=== Laravel Log (last 100 lines) ===\n" . $lastLines;
        }

        return response('<pre>' . htmlspecialchars($output) . '</pre>');
    });

    // Debug route to check installation status
    Route::get('/debug/installation-status', function () {
        $output = [];
        $output[] = '🔍 Installation Status Check';
        $output[] = '============================';
        $output[] = '';

        // Check 1: isDbCreated()
        $output[] = '1. InstallUtils::isDbCreated() check:';
        $dbCreated = \App\Space\InstallUtils::isDbCreated();
        $output[] = '   Result: ' . ($dbCreated ? '✅ TRUE' : '❌ FALSE');

        $markerFile = storage_path('app/database_created');
        $output[] = '   Marker file: ' . $markerFile;
        $output[] = '   File exists: ' . (file_exists($markerFile) ? '✅ YES' : '❌ NO');
        if (file_exists($markerFile)) {
            $output[] = '   File contents: ' . file_get_contents($markerFile);
            $output[] = '   File permissions: ' . substr(sprintf('%o', fileperms($markerFile)), -4);
        }
        $output[] = '';

        // Check 2: profile_complete setting
        $output[] = '2. Setting::getSetting(\'profile_complete\') check:';
        try {
            $profileComplete = \App\Models\Setting::getSetting('profile_complete');
            $output[] = '   Result: ' . ($profileComplete ?? 'NULL');
            $output[] = '   Expected: COMPLETED';
            $output[] = '   Match: ' . ($profileComplete === 'COMPLETED' ? '✅ YES' : '❌ NO');

            // Query database directly
            $setting = \App\Models\Setting::where('option', 'profile_complete')->first();
            if ($setting) {
                $output[] = '   Database row: option=' . $setting->option . ', value=' . $setting->value;
            } else {
                $output[] = '   ❌ Row not found in settings table!';
            }
        } catch (\Exception $e) {
            $output[] = '   ❌ ERROR: ' . $e->getMessage();
        }
        $output[] = '';

        // Check 3: Middleware logic
        $output[] = '3. InstallationMiddleware logic:';
        $shouldRedirect = (!$dbCreated || $profileComplete !== 'COMPLETED');
        $output[] = '   Should redirect to /installation: ' . ($shouldRedirect ? '❌ YES' : '✅ NO');
        $output[] = '';

        // Check 4: Environment variables
        $output[] = '4. Environment Variables:';
        $output[] = '   APP_ENV: ' . env('APP_ENV');
        $output[] = '   RAILWAY_ENVIRONMENT: ' . (env('RAILWAY_ENVIRONMENT') ?? 'NOT SET');
        $output[] = '   RAILWAY_SKIP_INSTALL: ' . (env('RAILWAY_SKIP_INSTALL') ?? 'NOT SET');
        $output[] = '';

        return response('<pre>' . implode("\n", $output) . '</pre>');
    });

    // TEMPORARY: Show actual database data
    Route::get('/debug/show-data', function () {
        $output = [];
        $output[] = '📊 Production Database Contents';
        $output[] = '=================================';
        $output[] = '';

        try {
            $companies = \App\Models\Company::all();
            $output[] = "Total companies: " . $companies->count();
            foreach ($companies as $company) {
                $output[] = "  - {$company->name} (ID: {$company->id}, slug: {$company->slug})";
            }
            $output[] = '';

            // Analyze ALL companies
            foreach ($companies as $company) {
                $output[] = "========================================";
                $output[] = "COMPANY: {$company->name} (ID: {$company->id})";
                $output[] = "========================================";
                $output[] = '';

                // Invoices
                $invoices = \App\Models\Invoice::where('company_id', $company->id)->get();
                $output[] = "INVOICES: " . $invoices->count();
                foreach ($invoices as $inv) {
                    $output[] = sprintf(
                        "  - #%s: %s MKD (status=%s, paid_status=%s, customer=%s, date=%s)",
                        $inv->invoice_number,
                        number_format($inv->total, 2),
                        $inv->status,
                        $inv->paid_status,
                        $inv->customer->name ?? 'N/A',
                        $inv->invoice_date
                    );
                }
                $output[] = '';

                // Expenses
                $expenses = \App\Models\Expense::where('company_id', $company->id)->get();
                $output[] = "EXPENSES: " . $expenses->count();
                $totalExpenses = 0;
                foreach ($expenses as $exp) {
                    $totalExpenses += $exp->amount;
                    $output[] = sprintf(
                        "  - #%s: %s MKD (date=%s)",
                        $exp->expense_number ?? 'N/A',
                        number_format($exp->amount, 2),
                        $exp->expense_date
                    );
                }
                $output[] = "Total expenses: " . number_format($totalExpenses, 2) . " MKD";
                $output[] = '';

                // MCP Stats
                $dataProvider = app(\App\Services\McpDataProvider::class);
                $stats = $dataProvider->getCompanyStats($company);
                $output[] = "MCP DATA PROVIDER STATS:";
                $output[] = "  - Revenue: " . number_format($stats['revenue'], 2) . " MKD";
                $output[] = "  - Expenses: " . number_format($stats['expenses'], 2) . " MKD";
                $output[] = "  - Outstanding: " . number_format($stats['outstanding'], 2) . " MKD";
                $output[] = "  - Invoices count: " . $stats['invoices_count'];
                $output[] = "  - Customers: " . $stats['customers'];
                $output[] = '';

                // IFRS data
                if ($company->ifrs_entity_id) {
                    $output[] = "IFRS ENTITY: ID {$company->ifrs_entity_id}";
                    $ifrsTxns = \IFRS\Models\Transaction::where('entity_id', $company->ifrs_entity_id)->count();
                    $output[] = "  - IFRS Transactions: {$ifrsTxns}";
                }
                $output[] = '';
            }

        } catch (\Exception $e) {
            $output[] = '❌ ERROR: ' . $e->getMessage();
            $output[] = $e->getTraceAsString();
        }

        return response('<pre>' . implode("\n", $output) . '</pre>');
    });

    // TEMPORARY: Fix currency multiplier bug in existing data
    Route::get('/debug/fix-currency-bug', function () {
        $output = [];
        $output[] = '🔧 Fixing currency multiplier bug in existing data...';
        $output[] = '';
        $output[] = 'This will divide all MKD amounts by 100 (fixing the v-money3 bug)';
        $output[] = '';

        try {
            $mkdCurrency = \App\Models\Currency::where('code', 'MKD')->first();
            if (!$mkdCurrency) {
                $output[] = '❌ MKD currency not found';
                return response('<pre>' . implode("\n", $output) . '</pre>');
            }

            $output[] = "MKD Currency ID: {$mkdCurrency->id}, Precision: {$mkdCurrency->precision}";
            $output[] = '';

            // Fix invoices
            $invoices = \App\Models\Invoice::where('currency_id', $mkdCurrency->id)->get();
            $output[] = "Found {$invoices->count()} invoices with MKD currency";
            $fixedInvoices = 0;

            foreach ($invoices as $invoice) {
                $oldTotal = $invoice->total;
                $invoice->total = $oldTotal / 100;
                $invoice->sub_total = $invoice->sub_total / 100;
                $invoice->tax = $invoice->tax / 100;
                $invoice->due_amount = $invoice->due_amount / 100;
                $invoice->discount_val = $invoice->discount_val / 100;
                $invoice->base_total = $invoice->base_total / 100;
                $invoice->base_sub_total = $invoice->base_sub_total / 100;
                $invoice->base_tax = $invoice->base_tax / 100;
                $invoice->base_due_amount = $invoice->base_due_amount / 100;
                $invoice->base_discount_val = $invoice->base_discount_val / 100;
                $invoice->save();

                $output[] = "  ✅ Invoice #{$invoice->invoice_number}: {$oldTotal} → {$invoice->total} MKD";
                $fixedInvoices++;
            }

            // Fix expenses
            $expenses = \App\Models\Expense::where('currency_id', $mkdCurrency->id)->get();
            $output[] = '';
            $output[] = "Found {$expenses->count()} expenses with MKD currency";
            $fixedExpenses = 0;

            foreach ($expenses as $expense) {
                $oldAmount = $expense->amount;
                $expense->amount = $oldAmount / 100;
                $expense->base_amount = $expense->base_amount / 100;
                $expense->save();

                $output[] = "  ✅ Expense #{$expense->expense_number}: {$oldAmount} → {$expense->amount} MKD";
                $fixedExpenses++;
            }

            // Fix payments
            $payments = \App\Models\Payment::where('currency_id', $mkdCurrency->id)->get();
            $output[] = '';
            $output[] = "Found {$payments->count()} payments with MKD currency";
            $fixedPayments = 0;

            foreach ($payments as $payment) {
                $oldAmount = $payment->amount;
                $payment->amount = $oldAmount / 100;
                $payment->base_amount = $payment->base_amount / 100;
                $payment->save();

                $output[] = "  ✅ Payment #{$payment->payment_number}: {$oldAmount} → {$payment->amount} MKD";
                $fixedPayments++;
            }

            $output[] = '';
            $output[] = "🎉 Fixed {$fixedInvoices} invoices, {$fixedExpenses} expenses, and {$fixedPayments} payments!";
            $output[] = '';
            $output[] = 'Your amounts should now be correct.';
            $output[] = 'Please refresh the AI insights to see accurate numbers.';

        } catch (\Exception $e) {
            $output[] = '❌ ERROR: ' . $e->getMessage();
            $output[] = $e->getTraceAsString();
        }

        return response('<pre>' . implode("\n", $output) . '</pre>');
    });

    // TEMPORARY: Clean demo data via web endpoint
    Route::get('/debug/clean-demo-data', function () {
        $output = [];
        $output[] = '🧹 Cleaning demo data...';
        $output[] = '';

        try {
            // Delete demo company
            $demoCompany = \App\Models\Company::where('slug', 'makedonska-softver-doo')->first();
            if ($demoCompany) {
                $invoiceCount = \App\Models\Invoice::where('company_id', $demoCompany->id)->count();
                $expenseCount = \App\Models\Expense::where('company_id', $demoCompany->id)->count();

                $output[] = "Found demo company: {$demoCompany->name} (ID: {$demoCompany->id})";
                $output[] = "  - {$invoiceCount} invoices";
                $output[] = "  - {$expenseCount} expenses";

                $demoCompany->delete();
                $output[] = "✅ Deleted demo company and all related data";
            } else {
                $output[] = 'ℹ️  No demo company found (slug: makedonska-softver-doo)';
            }

            // Delete demo user
            $demoUser = \App\Models\User::where('email', 'marko.petrovski@megasoft.mk')->first();
            if ($demoUser) {
                $output[] = "Found demo user: {$demoUser->email}";
                $demoUser->delete();
                $output[] = "✅ Deleted demo user";
            } else {
                $output[] = 'ℹ️  No demo user found';
            }

            // Clean demo pattern invoices
            $demoInvoiceCount = \App\Models\Invoice::where('invoice_number', 'LIKE', 'ФАК-%')->count();
            if ($demoInvoiceCount > 0) {
                $deleted = \App\Models\Invoice::where('invoice_number', 'LIKE', 'ФАК-%')->delete();
                $output[] = "✅ Deleted {$deleted} demo invoices (pattern: ФАК-xxxxxx)";
            }

            $output[] = '';
            $output[] = '🎉 Demo data cleanup complete!';
            $output[] = 'Your production data is now clean.';

        } catch (\Exception $e) {
            $output[] = '❌ ERROR: ' . $e->getMessage();
            $output[] = '';
            $output[] = 'Stack trace:';
            $output[] = $e->getTraceAsString();
        }

        return response('<pre>' . implode("\n", $output) . '</pre>');
    });

    // Debug invoice PDF issues (logo 404, PDF 404)
    // ----------------------------------------------
    Route::get('/debug/fix-invoice-pdf', function () {
        // Simple security: require secret parameter
        $secret = request()->get('secret');
        if ($secret !== 'facturino2025') {
            return response('<h1>Access Denied</h1><p>Add ?secret=facturino2025 to the URL</p>', 403);
        }

        $output = [];
        $output[] = "=== Invoice PDF Debug & Fix ===\n";

        // Check invoices
        $invoices = \App\Models\Invoice::all();
        $output[] = "\nFound {$invoices->count()} invoices:";

        foreach ($invoices as $invoice) {
            $output[] = "  Invoice #{$invoice->invoice_number}:";
            $output[] = "    - ID: {$invoice->id}";
            $output[] = "    - unique_hash: ".($invoice->unique_hash ?? 'NULL - MISSING!');
            $output[] = "    - company_id: {$invoice->company_id}";
            $output[] = "    - status: {$invoice->status}";

            // Fix missing unique_hash
            if (! $invoice->unique_hash) {
                $invoice->unique_hash = \Illuminate\Support\Str::random(20);
                $invoice->save();
                $output[] = "    ✅ Generated new unique_hash: {$invoice->unique_hash}";
            }
        }

        // Check company logos
        $output[] = "\n=== Company Logos ===";
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            $output[] = "\nCompany: {$company->name} (ID: {$company->id})";
            $output[] = "  logo_path: ".($company->logo_path ?? 'NULL');
            $output[] = "  logo (URL): ".($company->logo ?? 'NULL');

            // Check media
            $logoMedia = $company->getMedia('logo')->first();
            if ($logoMedia) {
                $output[] = "  Media record exists:";
                $output[] = "    - file_name: {$logoMedia->file_name}";
                $output[] = "    - disk: {$logoMedia->disk}";
                $output[] = "    - path: {$logoMedia->getPath()}";

                // Check if file actually exists
                try {
                    $exists = \Storage::disk($logoMedia->disk)->exists($logoMedia->getPathRelativeToRoot());
                    $output[] = "    - file_exists: ".($exists ? 'YES' : 'NO - MISSING!');

                    if (! $exists) {
                        $output[] = "    ⚠️  Logo file is missing from storage!";
                    }
                } catch (\Exception $e) {
                    $output[] = "    - Error checking file: ".$e->getMessage();
                }
            } else {
                $output[] = "  No logo media record";
            }

            // Check for default logo
            $defaultLogo = base_path('logo/facturino_logo.png');
            $output[] = "  Default logo exists: ".(file_exists($defaultLogo) ? 'YES' : 'NO');
        }

        return response('<pre>'.implode("\n", $output).'</pre>');
    });

    // Debug storage configuration (Cloudflare R2, media uploads)
    // ----------------------------------------------------------------
    Route::get('/debug/storage-config', function () {
        // Simple security: require secret parameter
        $secret = request()->get('secret');
        if ($secret !== 'facturino2025') {
            return response('<h1>Access Denied</h1><p>Add ?secret=facturino2025 to the URL</p>', 403);
        }

        $output = [];
        $output[] = "=== Storage Configuration Debug ===\n";

        // Check filesystem configuration
        $output[] = "\n--- Filesystem Config ---";
        $output[] = "Default disk: ".config('filesystems.default');
        $output[] = "Cloud disk: ".config('filesystems.cloud');
        $output[] = "Media disk: ".config('media-library.disk_name');

        // Check S3-compatible (R2) configuration
        $output[] = "\n--- S3-Compatible (Cloudflare R2) Config ---";
        $s3Config = config('filesystems.disks.s3compat');
        if ($s3Config) {
            $output[] = "Endpoint: ".($s3Config['endpoint'] ?? 'NOT SET');
            $output[] = "Bucket: ".($s3Config['bucket'] ?? 'NOT SET');
            $output[] = "Region: ".($s3Config['region'] ?? 'NOT SET');
            $output[] = "Access Key exists: ".(!empty($s3Config['key']) ? 'YES' : 'NO');
            $output[] = "Secret Key exists: ".(!empty($s3Config['secret']) ? 'YES' : 'NO');

            // Test S3 connection
            try {
                $testFile = 'test-'.time().'.txt';
                \Storage::disk('s3compat')->put($testFile, 'Test connection from facturino.mk');
                $exists = \Storage::disk('s3compat')->exists($testFile);
                $output[] = "✅ Test file upload: ".($exists ? 'SUCCESS' : 'FAILED');

                if ($exists) {
                    \Storage::disk('s3compat')->delete($testFile);
                    $output[] = "✅ Test file cleanup: SUCCESS";
                }
            } catch (\Exception $e) {
                $output[] = "❌ S3 Connection Error: ".$e->getMessage();
            }
        } else {
            $output[] = "❌ S3-compatible disk not configured!";
        }

        // Check recent media uploads
        $output[] = "\n--- Recent Media Uploads (Last 10) ---";
        $recentMedia = \Spatie\MediaLibrary\MediaCollections\Models\Media::orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        if ($recentMedia->isEmpty()) {
            $output[] = "No media uploads found.";
        } else {
            foreach ($recentMedia as $media) {
                $output[] = "\nMedia ID: {$media->id}";
                $output[] = "  File: {$media->file_name}";
                $output[] = "  Disk: {$media->disk}";
                $output[] = "  Collection: {$media->collection_name}";
                $output[] = "  Model: {$media->model_type} (ID: {$media->model_id})";
                $output[] = "  Created: {$media->created_at}";

                // Check if file exists on its disk
                try {
                    $exists = \Storage::disk($media->disk)->exists($media->getPathRelativeToRoot());
                    $output[] = "  Exists on disk: ".($exists ? 'YES ✅' : 'NO ❌');

                    if ($exists && $media->disk === 's3compat') {
                        $url = \Storage::disk('s3compat')->url($media->getPathRelativeToRoot());
                        $output[] = "  R2 URL: {$url}";
                    }
                } catch (\Exception $e) {
                    $output[] = "  Error checking file: ".$e->getMessage();
                }
            }
        }

        // Check company logos specifically
        $output[] = "\n--- Company Logos ---";
        $companies = \App\Models\Company::all();
        foreach ($companies as $company) {
            $output[] = "\nCompany: {$company->name}";
            $logoMedia = $company->getMedia('logo')->first();
            if ($logoMedia) {
                $output[] = "  Logo file: {$logoMedia->file_name}";
                $output[] = "  Stored on disk: {$logoMedia->disk}";
                try {
                    $exists = \Storage::disk($logoMedia->disk)->exists($logoMedia->getPathRelativeToRoot());
                    $output[] = "  File exists: ".($exists ? 'YES ✅' : 'NO ❌');
                } catch (\Exception $e) {
                    $output[] = "  Error: ".$e->getMessage();
                }
            } else {
                $output[] = "  No logo uploaded";
            }
        }

        // Environment variables check
        $output[] = "\n--- Environment Variables ---";
        $envVars = [
            'FILESYSTEM_DISK',
            'S3_COMPAT_ENDPOINT',
            'S3_COMPAT_BUCKET',
            'S3_COMPAT_REGION',
        ];
        foreach ($envVars as $var) {
            $value = env($var);
            $output[] = "{$var}: ".($value ? $value : 'NOT SET ❌');
        }
        $output[] = "S3_COMPAT_KEY: ".(env('S3_COMPAT_KEY') ? 'SET ✅' : 'NOT SET ❌');
        $output[] = "S3_COMPAT_SECRET: ".(env('S3_COMPAT_SECRET') ? 'SET ✅' : 'NOT SET ❌');

        return response('<pre>'.implode("\n", $output).'</pre>');
    });
}
