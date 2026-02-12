<?php

namespace Tests\Feature;

use App\Contracts\ExchangeRateProvider;
use App\Services\CurrencyExchangeService;
use App\Services\FrankfurterExchangeRateService;
use App\Services\NbrmExchangeRateService;
use Tests\TestCase;

/**
 * Feature tests for ExchangeRateProvider interface binding and integration.
 *
 * Tests the service container binding, provider switching via config,
 * and the CurrencyExchangeService integration with configured providers.
 */
class ExchangeRateProviderTest extends TestCase
{
    /**
     * Test that the provider interface binding returns the correct class
     * based on config (default: NBRM).
     */
    public function test_provider_interface_binding_returns_correct_class(): void
    {
        // Default config should be 'nbrm'
        config(['mk.exchange_rates.provider' => 'nbrm']);

        $provider = $this->app->make(ExchangeRateProvider::class);

        $this->assertInstanceOf(NbrmExchangeRateService::class, $provider);
        $this->assertEquals('nbrm', $provider->getProviderName());
    }

    /**
     * Test that switching config to 'frankfurter' returns FrankfurterExchangeRateService.
     */
    public function test_frankfurter_fallback_works_when_configured(): void
    {
        config(['mk.exchange_rates.provider' => 'frankfurter']);

        $provider = $this->app->make(ExchangeRateProvider::class);

        $this->assertInstanceOf(FrankfurterExchangeRateService::class, $provider);
        $this->assertEquals('frankfurter', $provider->getProviderName());
    }

    /**
     * Test that CurrencyExchangeService uses the configured provider.
     */
    public function test_currency_exchange_service_uses_configured_provider(): void
    {
        config(['mk.exchange_rates.provider' => 'nbrm']);

        $service = $this->app->make(CurrencyExchangeService::class);

        $this->assertInstanceOf(CurrencyExchangeService::class, $service);
        $this->assertEquals('nbrm', $service->getProviderName());
    }

    /**
     * Test that CurrencyExchangeService switches to Frankfurter when configured.
     */
    public function test_currency_exchange_service_uses_frankfurter_when_configured(): void
    {
        config(['mk.exchange_rates.provider' => 'frankfurter']);

        $service = $this->app->make(CurrencyExchangeService::class);

        $this->assertInstanceOf(CurrencyExchangeService::class, $service);
        $this->assertEquals('frankfurter', $service->getProviderName());
    }

    /**
     * Test that an unknown provider name defaults to NBRM.
     */
    public function test_unknown_provider_defaults_to_nbrm(): void
    {
        config(['mk.exchange_rates.provider' => 'unknown_provider']);

        $provider = $this->app->make(ExchangeRateProvider::class);

        $this->assertInstanceOf(NbrmExchangeRateService::class, $provider);
        $this->assertEquals('nbrm', $provider->getProviderName());
    }

    /**
     * Test that both providers implement the ExchangeRateProvider interface.
     */
    public function test_both_providers_implement_interface(): void
    {
        $nbrm = $this->app->make(NbrmExchangeRateService::class);
        $frankfurter = $this->app->make(FrankfurterExchangeRateService::class);

        $this->assertInstanceOf(ExchangeRateProvider::class, $nbrm);
        $this->assertInstanceOf(ExchangeRateProvider::class, $frankfurter);
    }

    /**
     * Test that the NBRM config values are correctly loaded.
     */
    public function test_nbrm_config_values(): void
    {
        $this->assertEquals(
            'https://www.nbrm.mk/KLServiceNOV',
            config('mk.exchange_rates.nbrm.base_url')
        );

        $this->assertEquals(
            86400,
            config('mk.exchange_rates.nbrm.cache_ttl')
        );
    }

    /**
     * Test that the Frankfurter config values are correctly loaded.
     */
    public function test_frankfurter_config_values(): void
    {
        $this->assertEquals(
            'https://api.frankfurter.dev/v1',
            config('mk.exchange_rates.frankfurter.base_url')
        );

        $this->assertEquals(
            14400,
            config('mk.exchange_rates.frankfurter.cache_ttl')
        );
    }
}
// CLAUDE-CHECKPOINT
