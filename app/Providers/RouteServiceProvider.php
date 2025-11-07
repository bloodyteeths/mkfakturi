<?php

namespace App\Providers;

use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/admin/dashboard';

    /**
     * The path to the "customer home" route for your application.
     *
     * This is used by Laravel authentication to redirect customers after login.
     *
     * @var string
     */
    public const CUSTOMER_HOME = '/customer/dashboard';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        Route::bind('invoice', function ($value) {
            $invoice = Invoice::withoutGlobalScopes()
                ->where('unique_hash', $value)
                ->firstOrFail();

            if (! request()->header('company')) {
                request()->headers->set('company', $invoice->company_id);
            }

            return $invoice;
        });

        Route::bind('estimate', function ($value) {
            $estimate = Estimate::withoutGlobalScopes()
                ->where('unique_hash', $value)
                ->firstOrFail();

            if (! request()->header('company')) {
                request()->headers->set('company', $estimate->company_id);
            }

            return $estimate;
        });

        Route::bind('payment', function ($value) {
            $payment = Payment::withoutGlobalScopes()
                ->where('unique_hash', $value)
                ->firstOrFail();

            if (! request()->header('company')) {
                request()->headers->set('company', $payment->company_id);
            }

            return $payment;
        });

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Webhook routes (without CSRF protection)
            Route::middleware('web')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
                ->group(base_path('routes/webhooks.php'));

            // Partner Portal routes
            Route::middleware('web')
                ->group(base_path('routes/partner.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60);
        });
    }
}
