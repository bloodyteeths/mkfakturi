<?php

use Illuminate\Support\Facades\Route;
use Modules\Mk\Bitrix\Controllers\BitrixPartnerController;
use Modules\Mk\Bitrix\Controllers\BitrixWebhookController;
use Modules\Mk\Bitrix\Controllers\PartnerTriggerController;
use Modules\Mk\Bitrix\Controllers\PostmarkWebhookController;
use Modules\Mk\Bitrix\Controllers\UnsubscribeController;
use Modules\Mk\Bitrix\Controllers\PartnerCreationController;

/*
|--------------------------------------------------------------------------
| CRM Integration Routes
|--------------------------------------------------------------------------
|
| These routes handle CRM integration (HubSpot), Postmark email webhooks,
| and email unsubscribe functionality for outreach campaigns.
|
| Public routes:
| - /unsubscribe (GET/POST) - Email unsubscribe flow
| - /api/partner/create (GET) - Signed URL for partner creation from HubSpot
|
| Webhook routes:
| - /webhooks/postmark (POST) - Postmark email event webhooks
|
| Legacy Bitrix routes (deprecated):
| - /api/bitrix/* - Old Bitrix24 integration (migrated to HubSpot)
|
*/

// Public routes (no auth required)
Route::get('/unsubscribe', [UnsubscribeController::class, 'show'])
    ->name('outreach.unsubscribe');

Route::post('/unsubscribe', [UnsubscribeController::class, 'process'])
    ->name('outreach.unsubscribe.process');

// Webhook routes (exempt from CSRF, registered separately in bootstrap/app.php)
Route::post('/webhooks/postmark', [PostmarkWebhookController::class, 'handle'])
    ->name('webhooks.postmark');

// Partner trigger endpoint (signed URL, no auth middleware needed)
// This is a clickable link that can be added to HubSpot deal notes
Route::get('/api/partner/create', [PartnerTriggerController::class, 'createFromDeal'])
    ->name('api.partner.create');

// HubSpot partner creation webhook (HMAC-signed URL)
// Wife clicks this link in HubSpot to create partner account
Route::get('/webhooks/hubspot/create-partner/{dealId}', [PartnerCreationController::class, 'createPartner'])
    ->name('webhooks.hubspot.create-partner');

// Legacy Bitrix API routes (deprecated - returns 410 Gone)
Route::prefix('api/bitrix')
    ->middleware('bitrix.auth')
    ->group(function () {
        Route::post('/events', [BitrixWebhookController::class, 'handle'])
            ->name('api.bitrix.events');

        Route::post('/create-partner', [BitrixPartnerController::class, 'store'])
            ->name('api.bitrix.create-partner');
    });

// CLAUDE-CHECKPOINT
