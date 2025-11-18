<?php

namespace App\Providers;

use App\Services\InvoiceParsing\Invoice2DataClient;
use App\Services\InvoiceParsing\InvoiceParserClient;
use Illuminate\Support\ServiceProvider;

class InvoiceParsingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InvoiceParserClient::class, Invoice2DataClient::class);
    }
}
