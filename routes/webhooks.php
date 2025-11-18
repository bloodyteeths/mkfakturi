<?php

use App\Http\Controllers\Webhooks\CpayCallbackController;
use App\Http\Controllers\Webhooks\InboundMailController;
use App\Http\Controllers\Webhooks\PaddleWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from external payment gateways
| and other third-party services. CSRF protection is disabled for these
| routes as they come from external sources.
|
| Note: These routes are registered in App\Providers\RouteServiceProvider
| with CSRF middleware disabled via App\Http\Middleware\VerifyCsrfToken
|
*/

/*
|--------------------------------------------------------------------------
| CPAY Payment Gateway Webhook
|--------------------------------------------------------------------------
|
| Handles payment callbacks from CPAY (CASYS) payment gateway.
| This endpoint receives POST requests after payment completion.
|
| Security:
| - SHA256 signature verification
| - Idempotency check
| - Feature flag protection (FEATURE_ADVANCED_PAYMENTS)
|
*/
Route::post('/webhooks/cpay/callback', CpayCallbackController::class)
    ->name('cpay.callback');

/*
|--------------------------------------------------------------------------
| CPAY Payment Return URLs
|--------------------------------------------------------------------------
|
| These routes handle customer redirects after payment completion.
| The customer is redirected here from CPAY after successful/cancelled payment.
|
*/
Route::get('/cpay/success/{invoice}', function ($invoiceId) {
    // Redirect to invoice view with success message
    return redirect('/admin/invoices/'.$invoiceId)
        ->with('success', 'Payment completed successfully via CPAY');
})->name('cpay.success');

Route::get('/cpay/cancel/{invoice}', function ($invoiceId) {
    // Redirect to invoice view with cancellation message
    return redirect('/admin/invoices/'.$invoiceId)
        ->with('warning', 'Payment was cancelled');
})->name('cpay.cancel');

/*
|--------------------------------------------------------------------------
| Paddle Payment Gateway Webhook
|--------------------------------------------------------------------------
|
| Handles payment webhooks from Paddle payment gateway.
| This endpoint receives POST requests for transaction events.
|
| Security:
| - HMAC SHA256 signature verification
| - Idempotency check (7-day cache)
| - Feature flag protection (FEATURE_ADVANCED_PAYMENTS)
|
*/
Route::post('/webhooks/paddle', [PaddleWebhookController::class, 'handle'])
    ->name('paddle.webhook');

/*
|--------------------------------------------------------------------------
| Gateway Webhook Event Log Routes
|--------------------------------------------------------------------------
|
| These routes handle webhooks from payment gateways and banks,
| storing them in gateway_webhook_events table for async processing.
|
*/

use App\Http\Controllers\Webhooks\WebhookController;

// Payment Gateway Webhooks (using new unified controller)
Route::post('/webhooks/paddle', [WebhookController::class, 'paddle'])
    ->name('webhooks.paddle');

Route::post('/webhooks/cpay', [WebhookController::class, 'cpay'])
    ->name('webhooks.cpay');

// Bank Webhooks
Route::post('/webhooks/bank/nlb', [WebhookController::class, 'bankNlb'])
    ->name('webhooks.bank.nlb');

Route::post('/webhooks/bank/stopanska', [WebhookController::class, 'bankStopanska'])
    ->name('webhooks.bank.stopanska');

// Inbound email for Accounts Payable (Email → Bill automation entrypoint)
Route::post('/webhooks/email-inbound', [InboundMailController::class, 'handle'])
    ->name('webhooks.email_inbound');

// CLAUDE-CHECKPOINT
