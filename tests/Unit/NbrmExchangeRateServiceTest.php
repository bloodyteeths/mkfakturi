<?php

namespace Tests\Unit;

use App\Services\NbrmExchangeRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Unit tests for NbrmExchangeRateService.
 *
 * Tests XML parsing, caching, error handling, and currency support
 * for the NBRM (National Bank of Republic of Macedonia) exchange rate provider.
 */
class NbrmExchangeRateServiceTest extends TestCase
{
    /**
     * Sample NBRM XML response for testing.
     */
    protected function getSampleNbrmXml(): string
    {
        return '<?xml version="1.0" encoding="utf-8"?>
<ArrayOfKursZbirkaNOV xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
  <KursZbirkaNOV>
    <Valuta>EUR</Valuta>
    <Oznaka>EUR</Oznaka>
    <Nomin>1</Nomin>
    <Sreden>61.500000</Sreden>
    <Kupoven>61.192500</Kupoven>
    <Prodazen>61.807500</Prodazen>
  </KursZbirkaNOV>
  <KursZbirkaNOV>
    <Valuta>USD</Valuta>
    <Oznaka>USD</Oznaka>
    <Nomin>1</Nomin>
    <Sreden>56.200000</Sreden>
    <Kupoven>55.919000</Kupoven>
    <Prodazen>56.481000</Prodazen>
  </KursZbirkaNOV>
  <KursZbirkaNOV>
    <Valuta>GBP</Valuta>
    <Oznaka>GBP</Oznaka>
    <Nomin>1</Nomin>
    <Sreden>71.800000</Sreden>
    <Kupoven>71.441000</Kupoven>
    <Prodazen>72.159000</Prodazen>
  </KursZbirkaNOV>
  <KursZbirkaNOV>
    <Valuta>CHF</Valuta>
    <Oznaka>CHF</Oznaka>
    <Nomin>1</Nomin>
    <Sreden>63.400000</Sreden>
    <Kupoven>63.083000</Kupoven>
    <Prodazen>63.717000</Prodazen>
  </KursZbirkaNOV>
  <KursZbirkaNOV>
    <Valuta>JPY</Valuta>
    <Oznaka>JPY</Oznaka>
    <Nomin>100</Nomin>
    <Sreden>37.600000</Sreden>
    <Kupoven>37.412000</Kupoven>
    <Prodazen>37.788000</Prodazen>
  </KursZbirkaNOV>
</ArrayOfKursZbirkaNOV>';
    }

    /**
     * Test that parseNbrmXml correctly parses a standard NBRM XML response.
     */
    public function test_parses_nbrm_xml_response_correctly(): void
    {
        $service = new NbrmExchangeRateService();

        $rates = $service->parseNbrmXml($this->getSampleNbrmXml());

        $this->assertIsArray($rates);
        $this->assertArrayHasKey('EUR', $rates);
        $this->assertArrayHasKey('USD', $rates);
        $this->assertArrayHasKey('GBP', $rates);
        $this->assertArrayHasKey('CHF', $rates);
        $this->assertArrayHasKey('JPY', $rates);

        // EUR: 1 EUR = 61.5 MKD (denomination 1)
        $this->assertEquals(61.5, $rates['EUR']);

        // USD: 1 USD = 56.2 MKD (denomination 1)
        $this->assertEquals(56.2, $rates['USD']);

        // GBP: 1 GBP = 71.8 MKD (denomination 1)
        $this->assertEquals(71.8, $rates['GBP']);

        // CHF: 1 CHF = 63.4 MKD (denomination 1)
        $this->assertEquals(63.4, $rates['CHF']);

        // JPY: 100 JPY = 37.6 MKD, so 1 JPY = 0.376 MKD
        $this->assertEquals(0.376, $rates['JPY']);
    }

    /**
     * Test that the service returns cached rate on second call.
     */
    public function test_returns_cached_rate_on_second_call(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response($this->getSampleNbrmXml(), 200),
        ]);

        $service = new NbrmExchangeRateService();

        // First call — should hit the API
        $rate1 = $service->getRate('EUR', 'MKD');
        $this->assertEquals(61.5, $rate1);

        // Second call — should hit cache (no second HTTP request)
        Http::fake([
            'www.nbrm.mk/*' => Http::response('', 500),
        ]);

        $rate2 = $service->getRate('EUR', 'MKD');
        $this->assertEquals(61.5, $rate2);
    }

    /**
     * Test that the service throws an exception when NBRM API is unavailable.
     */
    public function test_throws_exception_when_nbrm_unavailable(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response('Service Unavailable', 503),
        ]);

        $service = new NbrmExchangeRateService();

        $this->expectException(\RuntimeException::class);

        $service->getRate('EUR', 'MKD');
    }

    /**
     * Test that the service supports common currencies (EUR, USD, GBP, CHF).
     */
    public function test_supports_eur_usd_gbp_chf_currencies(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response($this->getSampleNbrmXml(), 200),
        ]);

        $service = new NbrmExchangeRateService();

        $currencies = $service->getSupportedCurrencies();

        $this->assertContains('MKD', $currencies);
        $this->assertContains('EUR', $currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('GBP', $currencies);
        $this->assertContains('CHF', $currencies);
    }

    /**
     * Test that getProviderName returns 'nbrm'.
     */
    public function test_provider_name_returns_nbrm(): void
    {
        $service = new NbrmExchangeRateService();

        $this->assertEquals('nbrm', $service->getProviderName());
    }

    /**
     * Test same-currency rate returns 1.0.
     */
    public function test_same_currency_returns_one(): void
    {
        $service = new NbrmExchangeRateService();

        $rate = $service->getRate('MKD', 'MKD');

        $this->assertEquals(1.0, $rate);
    }

    /**
     * Test cross-rate calculation (e.g., EUR to USD via MKD).
     */
    public function test_cross_rate_calculation(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response($this->getSampleNbrmXml(), 200),
        ]);

        $service = new NbrmExchangeRateService();

        // EUR to USD cross-rate: 61.5 / 56.2 = ~1.094306
        $rate = $service->getRate('EUR', 'USD');

        $expected = 61.5 / 56.2;
        $this->assertEqualsWithDelta($expected, $rate, 0.0001);
    }

    /**
     * Test MKD to foreign currency rate (inverted).
     */
    public function test_mkd_to_foreign_rate(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response($this->getSampleNbrmXml(), 200),
        ]);

        $service = new NbrmExchangeRateService();

        // MKD to EUR: 1 / 61.5
        $rate = $service->getRate('MKD', 'EUR');

        $expected = 1.0 / 61.5;
        $this->assertEqualsWithDelta($expected, $rate, 0.000001);
    }

    /**
     * Test that parseNbrmXml handles denomination (Nomin) correctly for JPY.
     */
    public function test_handles_denomination_correctly(): void
    {
        $service = new NbrmExchangeRateService();

        $rates = $service->parseNbrmXml($this->getSampleNbrmXml());

        // JPY has Nomin=100, Sreden=37.6
        // Per-unit rate: 37.6 / 100 = 0.376
        $this->assertEquals(0.376, $rates['JPY']);
    }

    /**
     * Test that parseNbrmXml throws exception on invalid XML.
     */
    public function test_throws_on_invalid_xml(): void
    {
        $service = new NbrmExchangeRateService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse NBRM XML');

        $service->parseNbrmXml('this is not valid xml <<<<');
    }

    /**
     * Test that parseNbrmXml throws exception on empty rate list.
     */
    public function test_throws_on_empty_rate_list(): void
    {
        $service = new NbrmExchangeRateService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no valid exchange rate entries');

        $service->parseNbrmXml('<?xml version="1.0"?><ArrayOfKursZbirkaNOV></ArrayOfKursZbirkaNOV>');
    }

    /**
     * Test getMultipleRates returns correct format.
     */
    public function test_get_multiple_rates(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response($this->getSampleNbrmXml(), 200),
        ]);

        $service = new NbrmExchangeRateService();

        $pairs = [
            ['from' => 'EUR', 'to' => 'MKD'],
            ['from' => 'USD', 'to' => 'MKD'],
            ['from' => 'MKD', 'to' => 'MKD'],
        ];

        $rates = $service->getMultipleRates($pairs);

        $this->assertArrayHasKey('EUR/MKD', $rates);
        $this->assertArrayHasKey('USD/MKD', $rates);
        $this->assertArrayHasKey('MKD/MKD', $rates);
        $this->assertEquals(61.5, $rates['EUR/MKD']);
        $this->assertEquals(56.2, $rates['USD/MKD']);
        $this->assertEquals(1.0, $rates['MKD/MKD']);
    }

    /**
     * Test that unsupported currency throws RuntimeException.
     */
    public function test_unsupported_currency_throws_exception(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response($this->getSampleNbrmXml(), 200),
        ]);

        $service = new NbrmExchangeRateService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('NBRM does not publish rate for XYZ');

        $service->getRate('XYZ', 'MKD');
    }

    /**
     * Test that the service handles NBRM API returning empty body.
     */
    public function test_handles_empty_api_response(): void
    {
        Cache::flush();

        Http::fake([
            'www.nbrm.mk/*' => Http::response('', 200),
        ]);

        $service = new NbrmExchangeRateService();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('empty response');

        $service->getRate('EUR', 'MKD');
    }
}
