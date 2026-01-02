<?php

use Illuminate\Support\Facades\Route;
use Modules\Mk\Bitrix\Controllers\UnsubscribeController;

/*
|--------------------------------------------------------------------------
| Bitrix24 CRM Integration Routes
|--------------------------------------------------------------------------
|
| Routes for Bitrix24 CRM integration including:
| - Public unsubscribe handling
| - Postmark webhook for email events
| - API endpoints for lead sync
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

// Unsubscribe routes - public access for email recipients
Route::get('/unsubscribe', [UnsubscribeController::class, 'show'])
    ->name('outreach.unsubscribe');

Route::post('/unsubscribe', [UnsubscribeController::class, 'process'])
    ->name('outreach.unsubscribe.process');

/*
|--------------------------------------------------------------------------
| Webhook Routes (No CSRF, External Callbacks)
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from Postmark for email events.
| CSRF protection is disabled via VerifyCsrfToken middleware exemption.
|
*/

Route::prefix('webhooks')->group(function () {
    // Postmark email event webhooks (opens, clicks, bounces, spam complaints)
    Route::post('/postmark', function () {
        // Placeholder for PostmarkWebhookController
        // Will process email opens, clicks, bounces, and spam complaints
        return response()->json(['status' => 'ok']);
    })->name('webhooks.postmark');
});

/*
|--------------------------------------------------------------------------
| API Routes (Protected by Bitrix Auth Middleware)
|--------------------------------------------------------------------------
|
| These routes are protected by the bitrix.auth middleware which validates
| the shared secret from incoming Bitrix24 webhook requests.
|
*/

Route::prefix('api/bitrix')->middleware('bitrix.auth')->group(function () {
    // Lead sync endpoints
    Route::post('/leads/sync', function () {
        // Placeholder for lead sync from Bitrix24
        return response()->json(['status' => 'ok']);
    })->name('api.bitrix.leads.sync');

    // Lead status update callback
    Route::post('/leads/status', function () {
        // Placeholder for lead status update callback
        return response()->json(['status' => 'ok']);
    })->name('api.bitrix.leads.status');

    // Outreach campaign triggers
    Route::post('/outreach/trigger', function () {
        // Placeholder for outreach campaign trigger
        return response()->json(['status' => 'ok']);
    })->name('api.bitrix.outreach.trigger');
});

// CLAUDE-CHECKPOINT
