<?php

use App\Http\Controllers\V1\Admin\Expense\ShowReceiptController;
use App\Http\Controllers\V1\Admin\Report\BalanceSheetReportController;
use App\Http\Controllers\V1\Admin\Report\CustomerSalesReportController;
use App\Http\Controllers\V1\Admin\Report\ExpensesReportController;
use App\Http\Controllers\V1\Admin\Report\IncomeStatementReportController;
use App\Http\Controllers\V1\Admin\Report\ItemSalesReportController;
use App\Http\Controllers\V1\Admin\Report\ProfitLossReportController;
use App\Http\Controllers\V1\Admin\Report\TaxSummaryReportController;
use App\Http\Controllers\V1\Admin\Report\CashFlowReportController;
use App\Http\Controllers\V1\Admin\Report\EquityChangesReportController;
use App\Http\Controllers\V1\Admin\Report\TrialBalanceReportController;
use App\Http\Controllers\V1\Admin\Report\UjpFormReportController;
use App\Http\Controllers\V1\Customer\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\V1\Customer\EstimatePdfController as CustomerEstimatePdfController;
use App\Http\Controllers\V1\Customer\InvoicePdfController as CustomerInvoicePdfController;
use App\Http\Controllers\V1\Customer\PaymentPdfController as CustomerPaymentPdfController;
use App\Http\Controllers\V1\Customer\ProformaInvoicePdfController as CustomerProformaInvoicePdfController;
use App\Http\Controllers\V1\Modules\ScriptController;
use App\Http\Controllers\V1\Modules\StyleController;
use App\Http\Controllers\V1\PDF\DownloadReceiptController;
use App\Http\Controllers\V1\PDF\EstimatePdfController;
use App\Http\Controllers\V1\PDF\InvoicePdfController;
use App\Http\Controllers\V1\PDF\PaymentPdfController;
use App\Http\Controllers\V1\PDF\ProformaInvoicePdfController;
// use App\Http\Controllers\PrometheusController; // Disabled - dependency not installed
use App\Http\Controllers\Auth\OneIdAuthController;
use App\Models\Company;
use Illuminate\Support\Facades\Route;

// Module Asset Includes
// ----------------------------------------------

Route::get('/modules/styles/{style}', StyleController::class);

Route::get('/modules/scripts/{script}', ScriptController::class);

// Health check endpoint - comprehensive system health monitoring
Route::get('/health', [\App\Http\Controllers\HealthController::class, 'health']);

// CLAUDE-CHECKPOINT — debug-auth route removed (SQL injection / debug cleanup)

// Admin Auth
// ----------------------------------------------
// NOTE: The /login route has been removed from web.php
// Please use POST /api/v1/auth/login instead
// This ensures login and bootstrap use the same Sanctum session handling

Route::post('auth/logout', function () {
    Auth::guard('web')->logout();
});

// Customer auth
// ----------------------------------------------

Route::post('/{company:unique_hash}/customer/login', CustomerLoginController::class);

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

    // IFRS Accounting Reports
    // ----------------------------------
    Route::get('/trial-balance/{hash}', TrialBalanceReportController::class);
    Route::get('/balance-sheet/{hash}', BalanceSheetReportController::class);
    Route::get('/income-statement/{hash}', IncomeStatementReportController::class);
    Route::get('/cash-flow/{hash}', CashFlowReportController::class);
    Route::get('/equity-changes/{hash}', EquityChangesReportController::class);

    // UJP Tax Form PDFs
    Route::get('/ujp-forms/{hash}/{formCode}', UjpFormReportController::class);

    // download expense receipt
    // -------------------------------------------------
    Route::get('/expenses/{expense}/download-receipt', DownloadReceiptController::class);
    Route::get('/expenses/{expense}/receipt', ShowReceiptController::class);
});


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

    // proforma invoice pdf
    // -------------------------------------------------
    Route::get('/proforma-invoices/pdf/{proformaInvoice:unique_hash}', ProformaInvoicePdfController::class);
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

    Route::get('/proforma-invoices/{email_log:token}', [CustomerProformaInvoicePdfController::class, 'getProformaInvoice']);
    Route::get('/proforma-invoices/view/{email_log:token}', [CustomerProformaInvoicePdfController::class, 'getPdf'])->name('proforma-invoice');
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
Route::get('/metrics/test', function () {
    return response('# Test metrics endpoint working', 200, [
        'Content-Type' => 'text/plain; charset=utf-8',
    ]);
});

// CLAUDE-CHECKPOINT — metrics/debug route removed (debug cleanup)

Route::middleware('feature:monitoring')->group(function () {
    Route::get('/metrics', [PrometheusController::class, 'metrics'])->name('metrics');
    Route::get('/metrics/health', [PrometheusController::class, 'health'])->name('prometheus.health');
});

// eID / OneID OpenID Connect Login (P13-03)
// ----------------------------------------------
Route::get('/auth/oneid/redirect', [OneIdAuthController::class, 'redirect'])->name('auth.oneid.redirect');
Route::get('/auth/oneid/callback', [OneIdAuthController::class, 'callback'])->name('auth.oneid.callback');


// Setup for installation of app
// ----------------------------------------------

Route::get('/installation', function () {
    return view('app');
})->name('install')->middleware('redirect-if-installed');

// Auto-login after free signup (signed URL, expires in 5 minutes)
Route::get('/signup/auto-login/{user}', function (\App\Models\User $user) {
    \Illuminate\Support\Facades\Auth::login($user);
    request()->session()->regenerate();

    return redirect('/admin');
})->name('signup.auto-login')->middleware('signed');

// Public signup page (referral-based registration)
// No redirect-if-installed middleware - this is a public registration page
Route::get('/signup', function () {
    return view('app');
})->name('signup');

// Public pricing page (accessible without authentication)
Route::get('/pricing', function () {
    return view('app');
})->name('pricing');

// Partner signup page (partner-to-partner referral)
Route::get('/partner/signup', function () {
    return view('app');
})->name('partner.signup');

// Legal pages (public)
Route::get('/privacy', function () {
    return view('app');
})->name('privacy');

Route::get('/terms', function () {
    return view('app');
})->name('terms');

Route::get('/support', function () {
    return redirect('/admin/support');
});

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

// CLAUDE-CHECKPOINT — all /debug/* production routes removed (security cleanup)

