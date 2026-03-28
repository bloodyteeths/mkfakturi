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
| Gateway Webhook Event Log Routes
|--------------------------------------------------------------------------
|
| These routes handle webhooks from payment gateways and banks,
| storing them in gateway_webhook_events table for async processing.
|
| Security:
| - HMAC SHA256 signature verification (Paddle/Stripe)
| - Idempotency check via gateway_webhook_events table
| - Feature flag protection where applicable
|
*/

use App\Http\Controllers\Webhooks\WebhookController;

// Payment Gateway Webhooks (unified controller for logging + async processing)
Route::post('/webhooks/paddle', [WebhookController::class, 'paddle'])
    ->name('webhooks.paddle');

Route::post('/webhooks/cpay', [WebhookController::class, 'cpay'])
    ->name('webhooks.cpay');

// Stripe Webhooks
Route::post('/webhooks/stripe', [WebhookController::class, 'stripe'])
    ->name('webhooks.stripe');

// Bank Webhooks
Route::post('/webhooks/bank/nlb', [WebhookController::class, 'bankNlb'])
    ->name('webhooks.bank.nlb');

Route::post('/webhooks/bank/stopanska', [WebhookController::class, 'bankStopanska'])
    ->name('webhooks.bank.stopanska');

/*
|--------------------------------------------------------------------------
| CPAY Merchant Payment Callbacks (Per-Company CASYS)
|--------------------------------------------------------------------------
|
| These routes handle CPay redirects after a customer pays via QR code
| using the merchant's own CASYS credentials. Updates cached payment status.
|
*/
Route::get('/webhooks/cpay/merchant/ok', function (\Illuminate\Http\Request $request) {
    $orderId = $request->input('order_id');
    $companyId = $request->input('company_id');

    if ($orderId) {
        app(\Modules\Mk\Services\CpayMerchantService::class)
            ->handleCallback($orderId, true, $request->all());
    }

    // Redirect to a simple "Payment successful" page or close window
    return response('<html><body style="text-align:center;padding:60px;font-family:sans-serif"><h1>&#x2705; Payment Successful</h1><p>You can close this window.</p><script>window.close()</script></body></html>', 200)
        ->header('Content-Type', 'text/html');
})->name('cpay.merchant.ok');

Route::get('/webhooks/cpay/merchant/fail', function (\Illuminate\Http\Request $request) {
    $orderId = $request->input('order_id');

    if ($orderId) {
        app(\Modules\Mk\Services\CpayMerchantService::class)
            ->handleCallback($orderId, false, $request->all());
    }

    return response('<html><body style="text-align:center;padding:60px;font-family:sans-serif"><h1>&#x274C; Payment Failed</h1><p>Please try again or use a different payment method.</p><script>window.close()</script></body></html>', 200)
        ->header('Content-Type', 'text/html');
})->name('cpay.merchant.fail');

/*
|--------------------------------------------------------------------------
| CPay Merchant Payment Page (QR code destination)
|--------------------------------------------------------------------------
|
| When a customer scans the QR code, they land on this page which
| auto-submits a form to CPay with the payment parameters.
|
*/
Route::get('/pay/cpay/{orderId}', function (\Illuminate\Http\Request $request, string $orderId) {
    $cpayService = app(\Modules\Mk\Services\CpayMerchantService::class);
    $status = $cpayService->getPaymentStatus($orderId);

    if (! $status || $status['status'] !== 'pending') {
        return response('<html><body style="text-align:center;padding:60px;font-family:sans-serif"><h1>Payment not found or already processed.</h1></body></html>', 404)
            ->header('Content-Type', 'text/html');
    }

    // Recreate checkout to get form fields
    $companyId = $status['company_id'];
    $checkout = $cpayService->createCheckout($companyId, $status['amount'], $orderId, 'POS Payment');

    // Build auto-submit form
    $formFields = '';
    foreach ($checkout['form_fields'] as $name => $value) {
        $escapedValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $formFields .= '<input type="hidden" name="' . $name . '" value="' . $escapedValue . '">';
    }

    $html = <<<HTML
    <html>
    <head><meta name="viewport" content="width=device-width, initial-scale=1"><title>Redirecting to payment...</title></head>
    <body style="text-align:center;padding:60px;font-family:sans-serif">
        <h2>Redirecting to payment...</h2>
        <p>Please wait, you will be redirected to the secure payment page.</p>
        <form id="cpayForm" method="POST" action="{$checkout['checkout_url']}">
            {$formFields}
            <noscript><button type="submit">Continue to Payment</button></noscript>
        </form>
        <script>document.getElementById('cpayForm').submit();</script>
    </body>
    </html>
    HTML;

    return response($html, 200)->header('Content-Type', 'text/html');
});

// Inbound email for Accounts Payable (Email → Bill automation entrypoint)
Route::post('/webhooks/email-inbound', [InboundMailController::class, 'handle'])
    ->name('webhooks.email_inbound');

