<?php

namespace App\Providers;

use App\Services\InvoiceParsing\AzureDocumentIntelligenceClient;
use App\Services\InvoiceParsing\Invoice2DataClient;
use App\Services\InvoiceParsing\InvoiceParserClient;
use Illuminate\Support\ServiceProvider;

class InvoiceParsingServiceProvider extends ServiceProvider
{
    /**
     * Register the invoice parser client binding.
     *
     * Selects the implementation based on the 'services.invoice_parser_driver' config:
     * - 'azure'       => AzureDocumentIntelligenceClient (Azure Document Intelligence REST API)
     * - 'invoice2data' => Invoice2DataClient (self-hosted Python FastAPI + Tesseract)
     *
     * Default driver: 'invoice2data' (preserves existing behaviour).
     */
    public function register(): void
    {
        $this->app->bind(InvoiceParserClient::class, function () {
            $driver = config('services.invoice_parser_driver', 'invoice2data');

            return match ($driver) {
                'azure' => new AzureDocumentIntelligenceClient(),
                default => new Invoice2DataClient(),
            };
        });
    }
} // CLAUDE-CHECKPOINT
