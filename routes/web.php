<?php

use App\Http\Controllers\V1\Admin\Auth\LoginController;
use App\Http\Controllers\V1\Admin\Expense\ShowReceiptController;
use App\Http\Controllers\V1\Admin\Report\CustomerSalesReportController;
use App\Http\Controllers\V1\Admin\Report\ExpensesReportController;
use App\Http\Controllers\V1\Admin\Report\ItemSalesReportController;
use App\Http\Controllers\V1\Admin\Report\ProfitLossReportController;
use App\Http\Controllers\V1\Admin\Report\TaxSummaryReportController;
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

    // download expense receipt
    // -------------------------------------------------
    Route::get('/expenses/{expense}/download-receipt', DownloadReceiptController::class);
    Route::get('/expenses/{expense}/receipt', ShowReceiptController::class);
});

// PDF Endpoints
// ----------------------------------------------
// ВАЖНО: web middleware е потребен за session/cookie поддршка во iframe
Route::middleware(['web', 'pdf-auth'])->group(function () {

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
        'customer_logo' => get_company_setting('customer_portal_logo', $company->id),
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

Route::get('/health', [App\Http\Controllers\HealthController::class, 'health']);
Route::get('/ready', [App\Http\Controllers\HealthController::class, 'ready']);

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
}
