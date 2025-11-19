<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Lavary\Menu\ServiceProvider::class,
        \App\Providers\InvoiceParsingServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Webhook routes (without CSRF protection)
            Route::middleware('web')
                ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
                ->group(base_path('routes/webhooks.php'));

            // Partner Portal routes
            Route::middleware('web')
                ->group(base_path('routes/partner.php'));

            // MCP Internal API routes
            Route::middleware('api')
                ->group(base_path('routes/mcp.php'));

            // Debug routes (REMOVE IN PRODUCTION)
            if (file_exists(base_path('routes/debug.php'))) {
                Route::middleware('web')
                    ->group(base_path('routes/debug.php'));
            }
        },
        // CLAUDE-CHECKPOINT
    )
    ->withCommands([
        \App\Console\Commands\RefreshTemplateCache::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(AppServiceProvider::HOME);

        $middleware->validateCsrfTokens(except: [
            'login',
        ]);

        $middleware->append([
            \App\Http\Middleware\SecurityHeaders::class, // SEC-01-02: Security headers
            \App\Http\Middleware\ConfigMiddleware::class,
            \App\Http\Middleware\LocaleMiddleware::class,
            \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        ]);

        // Replace framework middleware with custom implementations
        $middleware->replace(\Illuminate\Foundation\Http\Middleware\TrimStrings::class, \App\Http\Middleware\TrimStrings::class);

        // Web middleware runs after the default "web" group, ensuring that
        // the session is started before referral tracking or CSRF handling.
        // Note: EncryptCookies and VerifyCsrfToken are already in web group and replaced below
        $middleware->web(append: [
            \App\Http\Middleware\CaptureReferral::class, // Capture affiliate referral codes
        ]);

        $middleware->statefulApi();
        $middleware->throttleApi('180,1');

        $middleware->replace(\Illuminate\Http\Middleware\TrustProxies::class, \App\Http\Middleware\TrustProxies::class);

        $middleware->replaceInGroup('web', \Illuminate\Cookie\Middleware\EncryptCookies::class, \App\Http\Middleware\EncryptCookies::class);

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
            'bouncer' => \App\Http\Middleware\ScopeBouncer::class,
            'capture-referral' => \App\Http\Middleware\CaptureReferral::class,
            'company' => \App\Http\Middleware\CompanyMiddleware::class,
            'cron-job' => \App\Http\Middleware\CronJobMiddleware::class,
            'customer' => \App\Http\Middleware\CustomerRedirectIfAuthenticated::class,
            'customer-guest' => \App\Http\Middleware\CustomerGuest::class,
            'customer-portal' => \App\Http\Middleware\CustomerPortalMiddleware::class,
            'feature' => \App\Http\Middleware\FeatureMiddleware::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'install' => \App\Http\Middleware\InstallationMiddleware::class,
            'invoice-limit' => \App\Http\Middleware\CheckInvoiceLimit::class, // FG-01-00: Invoice limits
            'user-limit' => \App\Http\Middleware\CheckUserLimit::class, // FG-01-30: User limits
            'mcp.token' => \App\Http\Middleware\VerifyMcpToken::class,
            'partner' => \App\Http\Middleware\PartnerScopeMiddleware::class,
            'partner-scope' => \App\Http\Middleware\PartnerScopeMiddleware::class,
            'partner.permission' => \App\Http\Middleware\CheckPartnerPermission::class, // AC-13: Partner permissions
            'pdf-company' => \App\Http\Middleware\ResolvePdfCompanyMiddleware::class,
            'pdf-auth' => \App\Http\Middleware\PdfMiddleware::class,
            'performance' => \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
            'redirect-if-installed' => \App\Http\Middleware\RedirectIfInstalled::class,
            'redirect-if-unauthenticated' => \App\Http\Middleware\RedirectIfUnauthorized::class,
            'super-admin' => \App\Http\Middleware\SuperAdminMiddleware::class, // AC-08: Super admin only routes
            'tier' => \App\Http\Middleware\CheckSubscriptionTier::class, // FG-01-00: Feature gating
        ]);
        // CLAUDE-CHECKPOINT

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\Authenticate::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
