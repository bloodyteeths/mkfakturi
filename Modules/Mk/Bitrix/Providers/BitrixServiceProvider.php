<?php

namespace Modules\Mk\Bitrix\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Mk\Bitrix\Services\HubSpotApiClient;
use Modules\Mk\Bitrix\Services\HubSpotService;
use Modules\Mk\Bitrix\Services\OutreachService;
use Modules\Mk\Bitrix\Services\PostmarkOutreachService;
use Modules\Mk\Bitrix\Commands\BitrixSetupCommand;
use Modules\Mk\Bitrix\Commands\BitrixImportLeadsCommand;
use Modules\Mk\Bitrix\Commands\HubSpotSetupCommand;
use Modules\Mk\Bitrix\Commands\HubSpotImportLeadsCommand;
use Modules\Mk\Bitrix\Commands\HubSpotPollDealsCommand;
use Modules\Mk\Bitrix\Commands\HubSpotProcessStageChangesCommand;
use Modules\Mk\Bitrix\Commands\HubSpotCreatePartnerCommand;
use Modules\Mk\Bitrix\Commands\HubSpotGeneratePartnerLinksCommand;
use Modules\Mk\Bitrix\Commands\HubSpotSyncPartnerActivityCommand;
use Modules\Mk\Bitrix\Commands\OutreachSendBatchCommand;
use Modules\Mk\Bitrix\Middleware\BitrixAuthMiddleware;
use Modules\Mk\Bitrix\Services\PartnerMetricsService;

class BitrixServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../config/bitrix.php', 'bitrix');
        $this->mergeConfigFrom(__DIR__ . '/../config/hubspot.php', 'hubspot');

        // Bind services as singletons
        $this->app->singleton(HubSpotApiClient::class);
        $this->app->singleton(HubSpotService::class);
        $this->app->singleton(PostmarkOutreachService::class);

        // OutreachService now depends on HubSpotApiClient
        $this->app->singleton(OutreachService::class, function ($app) {
            return new OutreachService(
                $app->make(HubSpotApiClient::class),
                $app->make(PostmarkOutreachService::class)
            );
        });

        // PartnerMetricsService for HubSpot activity sync
        $this->app->singleton(PartnerMetricsService::class);
    }

    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                // Legacy Bitrix commands (kept for backwards compatibility)
                BitrixSetupCommand::class,
                BitrixImportLeadsCommand::class,

                // HubSpot commands
                HubSpotSetupCommand::class,
                HubSpotImportLeadsCommand::class,
                HubSpotPollDealsCommand::class,
                HubSpotProcessStageChangesCommand::class,
                HubSpotCreatePartnerCommand::class,
                HubSpotGeneratePartnerLinksCommand::class,
                HubSpotSyncPartnerActivityCommand::class,

                // Outreach commands
                OutreachSendBatchCommand::class,
            ]);
        }

        // Register middleware
        $this->app['router']->aliasMiddleware('bitrix.auth', BitrixAuthMiddleware::class);

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/bitrix.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../../resources/views', 'bitrix');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/bitrix.php' => config_path('bitrix.php'),
            __DIR__ . '/../config/hubspot.php' => config_path('hubspot.php'),
        ], 'bitrix-config');
    }
}

// CLAUDE-CHECKPOINT
