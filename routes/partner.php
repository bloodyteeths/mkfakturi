<?php

use App\Http\Controllers\V1\Partner\PartnerApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Partner Portal API Routes
|--------------------------------------------------------------------------
|
| Partner-specific API endpoints for the referral portal.
| All routes require authentication and partner middleware.
| Feature flag: FEATURE_PARTNER_PORTAL must be enabled.
|
*/

// Partner Portal API routes (only if FEATURE_PARTNER_PORTAL enabled)
// DISABLED: These routes are now defined in routes/api.php with the correct controllers
// Route::prefix('api/v1/partner')
//     ->middleware(['auth:sanctum', 'feature:partner_portal', 'partner'])
//     ->group(function () {
//
//         // Dashboard statistics
//         Route::get('/dashboard', [PartnerApiController::class, 'dashboard'])
//             ->name('partner.dashboard');
//
//         // Commissions list
//         Route::get('/commissions', [PartnerApiController::class, 'commissions'])
//             ->name('partner.commissions');
//
//         // Clients list
//         Route::get('/clients', [PartnerApiController::class, 'clients'])
//             ->name('partner.clients');
//
//         // Partner profile
//         Route::get('/profile', [PartnerApiController::class, 'profile'])
//             ->name('partner.profile');
//     });

// CLAUDE-CHECKPOINT
