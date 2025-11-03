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

Route::middleware('pdf-auth')->group(function () {

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

// Monitoring Endpoints
// ----------------------------------------------

// Route::get('/metrics', [PrometheusController::class, 'metrics'])->name('metrics'); // Disabled - dependency not installed
// Route::get('/metrics/health', [PrometheusController::class, 'health'])->name('prometheus.health'); // Disabled - dependency not installed

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
Route::get('/banking/callback/{company}/{bank}', [\App\Http\Controllers\V1\Admin\BankAuthController::class, 'handleCallback'])
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
}
