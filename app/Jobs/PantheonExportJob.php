<?php

namespace App\Jobs;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Media24si\eSlog2\Business;
use Media24si\eSlog2\Invoice as eSlogInvoice;
use Media24si\eSlog2\InvoiceItem;
use Media24si\eSlog2\TaxSummary;

class PantheonExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoiceIds;

    protected $companyId;

    protected $exportFileName;

    /**
     * Create a new job instance.
     *
     * @param  array  $invoiceIds  Array of invoice IDs to export
     * @param  int  $companyId  Company ID for context
     * @param  string|null  $exportFileName  Optional custom export filename
     */
    public function __construct(array $invoiceIds, int $companyId, ?string $exportFileName = null)
    {
        $this->invoiceIds = $invoiceIds;
        $this->companyId = $companyId;
        $this->exportFileName = $exportFileName ?? 'pantheon_export_'.Carbon::now()->format('Y_m_d_H_i_s').'.xml';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting PANTHEON eSlog export', [
                'invoice_ids' => $this->invoiceIds,
                'company_id' => $this->companyId,
                'filename' => $this->exportFileName,
            ]);

            // Load invoices with necessary relationships
            $invoices = Invoice::with([
                'customer',
                'customer.billingAddress',
                'company',
                'company.address',
                'currency',
                'items',
                'items.taxes',
                'taxes',
            ])
                ->whereIn('id', $this->invoiceIds)
                ->where('company_id', $this->companyId)
                ->get();

            if ($invoices->isEmpty()) {
                Log::warning('No invoices found for export', [
                    'invoice_ids' => $this->invoiceIds,
                    'company_id' => $this->companyId,
                ]);

                return;
            }

            $xmlContent = $this->generateeSlogXML($invoices);

            // Validate XML structure
            if (! $this->validateXML($xmlContent)) {
                throw new \Exception('Generated XML failed validation');
            }

            // Save to storage/exports directory
            $filePath = 'exports/'.$this->exportFileName;
            Storage::put($filePath, $xmlContent);

            Log::info('PANTHEON eSlog export completed successfully', [
                'file_path' => $filePath,
                'invoice_count' => $invoices->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('PANTHEON eSlog export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_ids' => $this->invoiceIds,
            ]);
            throw $e;
        }
    }

    /**
     * Generate eSlog XML for multiple invoices
     */
    protected function generateeSlogXML($invoices): string
    {
        $xmlElements = [];

        foreach ($invoices as $invoice) {
            $eslogInvoice = $this->convertInvoiceToeSlog($invoice);
            $xmlElements[] = $eslogInvoice->generateXml()->asXML();
        }

        // Wrap multiple invoices in a root element if needed
        if (count($xmlElements) === 1) {
            return $xmlElements[0];
        }

        // For multiple invoices, create a wrapper
        $wrapper = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $wrapper .= '<eSlogExport xmlns="urn:eslog:2.00" exportDate="'.Carbon::now()->toISOString().'">'."\n";

        foreach ($xmlElements as $xml) {
            // Remove XML declaration from individual invoices
            $cleanXml = preg_replace('/<\?xml[^>]*\?>/', '', $xml);
            $wrapper .= $cleanXml."\n";
        }

        $wrapper .= '</eSlogExport>';

        return $wrapper;
    }

    /**
     * Convert Invoice model to eSlog Invoice
     */
    protected function convertInvoiceToeSlog(Invoice $invoice): eSlogInvoice
    {
        $eslogInvoice = new eSlogInvoice;

        // Basic invoice information
        $eslogInvoice->setInvoiceNumber($invoice->invoice_number)
            ->setDateIssued(Carbon::parse($invoice->invoice_date))
            ->setDateDue(Carbon::parse($invoice->due_date))
            ->setCurrency($invoice->currency->code ?? 'EUR')
            ->setInvoiceType(eSlogInvoice::TYPE_INVOICE);

        // Set seller (company) information
        $seller = $this->createBusiness($invoice->company, $invoice->company->address);
        $eslogInvoice->setSeller($seller);

        // Set buyer (customer) information
        $buyer = $this->createBusiness($invoice->customer, $invoice->customer->billingAddress);
        $eslogInvoice->setBuyer($buyer);

        // Add invoice items
        foreach ($invoice->items as $index => $item) {
            $eslogItem = $this->convertInvoiceItem($item, $index + 1);
            $eslogInvoice->addItem($eslogItem);
        }

        // Calculate totals and set amounts
        $this->setInvoiceTotals($eslogInvoice, $invoice);

        // Add tax summaries
        $this->addTaxSummaries($eslogInvoice, $invoice);

        return $eslogInvoice;
    }

    /**
     * Create Business object from Company/Customer model
     */
    protected function createBusiness($entity, $address = null): Business
    {
        $business = new Business;

        $business->setName($entity->name ?? '')
            ->setRegistrationNumber($entity->company_number ?? '')
            ->setVatId($entity->vat_number ?? '');

        if ($address) {
            $business->setAddress($address->address_street_1 ?? '')
                ->setCity($address->city ?? '')
                ->setZipCode($address->zip ?? '')
                ->setCountry($address->country->name ?? '')
                ->setCountryIsoCode($address->country->code ?? 'MK');
        }

        // Set contact information if available
        if (isset($entity->phone)) {
            $business->setPhone($entity->phone);
        }
        if (isset($entity->email)) {
            $business->setEmail($entity->email);
        }

        // Set banking information if available
        if (isset($entity->iban)) {
            $business->setIban($entity->iban);
        }
        if (isset($entity->bic)) {
            $business->setBic($entity->bic);
        }

        return $business;
    }

    /**
     * Convert InvoiceItem to eSlog InvoiceItem
     */
    protected function convertInvoiceItem($item, int $rowNumber): InvoiceItem
    {
        $eslogItem = new InvoiceItem;

        $eslogItem->setRowNumber($rowNumber)
            ->setName($item->name ?? '')
            ->setDescription($item->description ?? '')
            ->setQuantity((float) $item->quantity)
            ->setUnit(InvoiceItem::UNIT_UNIT);

        // Convert amounts from cents to currency units
        $price = $item->price / 100;
        $total = $item->total / 100;
        $totalWithTax = ($item->total + ($item->tax ?? 0)) / 100;

        $eslogItem->setPriceWithoutTax($price)
            ->setPriceWithoutTaxBeforeDiscounts($price) // Assuming no item-level discounts for now
            ->setTotalWithoutTax($total)
            ->setTotalWithTax($totalWithTax);

        // Set tax information
        $taxRate = 0;
        if ($item->taxes->isNotEmpty()) {
            $tax = $item->taxes->first();
            $taxRate = (float) $tax->percent;
        }

        $eslogItem->setTaxRate($taxRate)
            ->setTaxRateType(TaxSummary::CODE_STANDARD_RATE);

        return $eslogItem;
    }

    /**
     * Set invoice totals and amounts
     */
    protected function setInvoiceTotals(eSlogInvoice $eslogInvoice, Invoice $invoice): void
    {
        // Convert amounts from cents to currency units
        $subTotal = $invoice->sub_total / 100;
        $tax = $invoice->tax / 100;
        $total = $invoice->total / 100;
        $dueAmount = $invoice->due_amount / 100;

        $eslogInvoice->setTotalWithoutDiscount($subTotal)
            ->setTotalWithoutTax($subTotal)
            ->setTotalWithTax($total)
            ->setAmountDueForPayment($dueAmount);

        // Set paid amount if partially paid
        $paidAmount = $total - $dueAmount;
        if ($paidAmount > 0) {
            $eslogInvoice->setPaidAmount($paidAmount);
        }
    }

    /**
     * Add tax summaries to the invoice
     */
    protected function addTaxSummaries(eSlogInvoice $eslogInvoice, Invoice $invoice): void
    {
        // Group taxes by rate
        $taxGroups = [];

        // Collect taxes from invoice level
        foreach ($invoice->taxes as $tax) {
            $rate = (float) $tax->percent;
            if (! isset($taxGroups[$rate])) {
                $taxGroups[$rate] = [
                    'rate' => $rate,
                    'amount' => 0,
                    'baseAmount' => 0,
                ];
            }
            $taxGroups[$rate]['amount'] += $tax->amount / 100;
        }

        // Collect taxes from items if tax_per_item is enabled
        if ($invoice->tax_per_item === 'YES') {
            foreach ($invoice->items as $item) {
                foreach ($item->taxes as $tax) {
                    $rate = (float) $tax->percent;
                    if (! isset($taxGroups[$rate])) {
                        $taxGroups[$rate] = [
                            'rate' => $rate,
                            'amount' => 0,
                            'baseAmount' => 0,
                        ];
                    }
                    $taxGroups[$rate]['amount'] += $tax->amount / 100;
                    $taxGroups[$rate]['baseAmount'] += ($item->total / 100);
                }
            }
        } else {
            // For invoice-level taxes, use subtotal as base
            foreach ($taxGroups as $rate => &$group) {
                $group['baseAmount'] = $invoice->sub_total / 100;
            }
        }

        // Add tax summaries to eSlog invoice
        foreach ($taxGroups as $taxGroup) {
            $taxSummary = new TaxSummary;
            $taxSummary->setRate($taxGroup['rate'])
                ->setAmount($taxGroup['amount'])
                ->setBaseAmount($taxGroup['baseAmount'])
                ->setCategoryCode(TaxSummary::CODE_STANDARD_RATE);

            $eslogInvoice->addTaxSummary($taxSummary);
        }
    }

    /**
     * Validate generated XML against basic structure
     */
    protected function validateXML(string $xmlContent): bool
    {
        try {
            $xml = new \SimpleXMLElement($xmlContent);

            // Basic validation - check if it contains expected elements
            $hasInvoiceElements = $xml->xpath('//Invoice') || $xml->xpath('//M_INVOIC');

            if (! $hasInvoiceElements) {
                Log::error('XML validation failed: No invoice elements found');

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('XML validation failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
