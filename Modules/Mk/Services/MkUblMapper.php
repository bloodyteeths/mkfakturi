<?php

namespace Modules\Mk\Services;

use App\Models\Invoice;
use App\Models\Company;
use NumNum\UBL\Invoice as UBLInvoice;
use NumNum\UBL\Party;
use NumNum\UBL\Address;
use NumNum\UBL\Country;
use NumNum\UBL\Contact;
use NumNum\UBL\InvoiceLine;
use NumNum\UBL\Item;
use NumNum\UBL\Price;
use NumNum\UBL\TaxTotal;
use NumNum\UBL\TaxSubTotal;
use NumNum\UBL\TaxCategory;
use NumNum\UBL\TaxScheme;
use NumNum\UBL\LegalMonetaryTotal;
use NumNum\UBL\PaymentMeans;
use NumNum\UBL\PaymentTerms;
use NumNum\UBL\PayeeFinancialAccount;
use NumNum\UBL\Generator;
use Carbon\Carbon;

/**
 * Macedonian UBL Invoice Mapper
 * 
 * Maps InvoiceShelf invoices to UBL 2.1 XML format for Macedonian tax compliance
 * Supports Macedonian VAT rates, company details, and local business requirements
 */
class MkUblMapper
{
    protected $invoice;
    protected $company;

    /**
     * Create UBL XML from InvoiceShelf invoice
     */
    public function mapInvoiceToUbl(Invoice $invoice): string
    {
        $this->invoice = $invoice;
        $this->company = $invoice->company;

        // Create UBL Invoice document
        $ublInvoice = new UBLInvoice();
        
        // Set basic invoice information
        $this->setBasicInformation($ublInvoice);
        
        // Set supplier (company) information
        $this->setSupplierParty($ublInvoice);
        
        // Set customer information
        $this->setCustomerParty($ublInvoice);
        
        // Set payment information
        $this->setPaymentInformation($ublInvoice);
        
        // Set invoice lines
        $this->setInvoiceLines($ublInvoice);
        
        // Set tax totals
        $this->setTaxTotals($ublInvoice);
        
        // Set monetary totals
        $this->setLegalMonetaryTotal($ublInvoice);

        // Generate XML
        $generator = new Generator();
        return $generator->invoice($ublInvoice);
    }

    /**
     * Set basic invoice information
     */
    protected function setBasicInformation(UBLInvoice $ublInvoice): void
    {
        $ublInvoice->setId($this->invoice->invoice_number);
        $ublInvoice->setIssueDate(Carbon::parse($this->invoice->invoice_date));
        
        if ($this->invoice->due_date) {
            $ublInvoice->setDueDate(Carbon::parse($this->invoice->due_date));
        }

        // Set document currency (default MKD for Macedonia)
        $ublInvoice->setDocumentCurrencyCode($this->invoice->currency->code ?? 'MKD');
        
        // Set invoice type code (380 = Commercial invoice)
        $ublInvoice->setInvoiceTypeCode('380');
        
        // Add note if present
        if ($this->invoice->notes) {
            $ublInvoice->setNote($this->invoice->notes);
        }
    }

    /**
     * Set supplier (company) party information
     */
    protected function setSupplierParty(UBLInvoice $ublInvoice): void
    {
        // Create address for supplier
        $country = (new Country())->setIdentificationCode('MK'); // Macedonia
        
        $address = new Address();
        $address->setCountry($country);
        
        if ($this->company->address) {
            $addressLines = explode("\n", $this->company->address->address_street_1 ?? '');
            $address->setStreetName($addressLines[0] ?? '');
            $address->setCityName($this->company->address->city ?? '');
            $address->setPostalZone($this->company->address->zip ?? '');
        }

        // Create supplier party
        $supplierParty = new Party();
        $supplierParty->setName($this->company->name);
        $supplierParty->setPhysicalLocation($address);
        $supplierParty->setPostalAddress($address);

        // Add company tax information
        if ($this->company->vat_number) {
            $supplierParty->setPartyIdentificationId($this->company->vat_number);
        }

        $ublInvoice->setAccountingSupplierParty($supplierParty);
    }

    /**
     * Set customer party information
     */
    protected function setCustomerParty(UBLInvoice $ublInvoice): void
    {
        $customer = $this->invoice->customer;
        
        // Create customer address
        $country = (new Country())->setIdentificationCode('MK'); // Default to Macedonia
        
        $address = new Address();
        $address->setCountry($country);
        
        if ($customer->addresses->count() > 0) {
            $customerAddress = $customer->addresses->first();
            $address->setStreetName($customerAddress->address_street_1 ?? '');
            $address->setCityName($customerAddress->city ?? '');
            $address->setPostalZone($customerAddress->zip ?? '');
            
            // Set country if available
            if ($customerAddress->country) {
                $country->setIdentificationCode($customerAddress->country->iso_2);
            }
        }

        // Create customer contact
        $contact = new Contact();
        $contact->setElectronicMail($customer->email ?? '');
        
        if ($customer->phone) {
            $contact->setTelephone($customer->phone);
        }

        // Create customer party
        $customerParty = new Party();
        $customerParty->setName($customer->name);
        $customerParty->setPostalAddress($address);
        $customerParty->setContact($contact);

        // Add customer tax ID if available
        if ($customer->tax_id) {
            $customerParty->setPartyIdentificationId($customer->tax_id);
        }

        $ublInvoice->setAccountingCustomerParty($customerParty);
    }

    /**
     * Set payment information
     */
    protected function setPaymentInformation(UBLInvoice $ublInvoice): void
    {
        // Payment means (bank transfer is most common in Macedonia)
        $paymentMeans = new PaymentMeans();
        $paymentMeans->setPaymentMeansCode('30'); // Credit transfer
        
        // Add bank account information if available
        if ($this->company->bankAccounts->count() > 0) {
            $bankAccount = $this->company->bankAccounts->where('is_primary', true)->first()
                        ?? $this->company->bankAccounts->first();

            if ($bankAccount->iban) {
                $payeeAccount = new PayeeFinancialAccount();
                $payeeAccount->setId($bankAccount->iban);
                $paymentMeans->setPayeeFinancialAccount($payeeAccount);
            }
        }

        $ublInvoice->setPaymentMeans([$paymentMeans]);

        // Payment terms
        if ($this->invoice->due_date) {
            $paymentTerms = new PaymentTerms();
            $daysUntilDue = Carbon::parse($this->invoice->invoice_date)
                ->diffInDays(Carbon::parse($this->invoice->due_date));

            $paymentTerms->setNote("Рок за плаќање: {$daysUntilDue} дена"); // Payment term: X days
            $ublInvoice->setPaymentTerms($paymentTerms);
        }
    }

    /**
     * Set invoice lines
     */
    protected function setInvoiceLines(UBLInvoice $ublInvoice): void
    {
        $invoiceLines = [];
        
        foreach ($this->invoice->items as $index => $item) {
            $invoiceLine = new InvoiceLine();
            $invoiceLine->setId($index + 1);
            $invoiceLine->setInvoicedQuantity($item->quantity);
            
            // Create UBL Item
            $ublItem = new Item();
            $ublItem->setName($item->name);
            $ublItem->setDescription($item->description ?? '');
            
            $invoiceLine->setItem($ublItem);
            
            // Set price
            $price = new Price();
            $price->setPriceAmount($item->price);
            $invoiceLine->setPrice($price);
            
            // Calculate line total
            $lineTotal = $item->quantity * $item->price;
            $invoiceLine->setLineExtensionAmount($lineTotal);
            
            // Set tax information for this line
            if ($item->taxes->count() > 0) {
                $this->setLineTaxInfo($invoiceLine, $item);
            }
            
            $invoiceLines[] = $invoiceLine;
        }
        
        $ublInvoice->setInvoiceLines($invoiceLines);
    }

    /**
     * Set tax information for invoice line
     */
    protected function setLineTaxInfo(InvoiceLine $invoiceLine, $item): void
    {
        $taxSubTotals = [];
        
        foreach ($item->taxes as $tax) {
            // Skip if taxType is not loaded
            if (!$tax->taxType) {
                continue;
            }

            $taxScheme = new TaxScheme();
            $taxScheme->setId('VAT'); // Standard VAT

            $taxCategory = new TaxCategory();
            $taxCategory->setId('S'); // Standard rate
            $taxCategory->setPercent($tax->taxType->percent);
            $taxCategory->setTaxScheme($taxScheme);

            $taxSubTotal = new TaxSubTotal();
            $taxSubTotal->setTaxableAmount($item->quantity * $item->price);
            $taxSubTotal->setTaxAmount($tax->amount);
            $taxSubTotal->setTaxCategory($taxCategory);

            $taxSubTotals[] = $taxSubTotal;
        }
        
        if (!empty($taxSubTotals)) {
            $taxTotal = new TaxTotal();

            // Add each tax subtotal individually
            foreach ($taxSubTotals as $taxSubTotal) {
                $taxTotal->addTaxSubTotal($taxSubTotal);
            }

            // Calculate total tax amount for this line
            $totalTaxAmount = collect($taxSubTotals)->sum(function ($subTotal) {
                return $subTotal->getTaxAmount();
            });
            $taxTotal->setTaxAmount($totalTaxAmount);

            $invoiceLine->setTaxTotal($taxTotal);
        }
    }

    /**
     * Set invoice tax totals
     */
    protected function setTaxTotals(UBLInvoice $ublInvoice): void
    {
        $taxSubTotals = [];
        $totalTaxAmount = 0;
        
        // Group taxes by tax type
        $taxGroups = $this->invoice->taxes->groupBy('tax_type_id');
        
        foreach ($taxGroups as $taxTypeId => $taxes) {
            $taxType = $taxes->first()->taxType;

            // Skip if taxType is not loaded
            if (!$taxType) {
                continue;
            }

            $groupTaxAmount = $taxes->sum('amount');
            $groupTaxableAmount = $taxes->sum(function ($tax) {
                return $tax->taxType && $tax->taxType->percent > 0 ? ($tax->amount / $tax->taxType->percent * 100) : 0;
            });

            $taxScheme = new TaxScheme();
            $taxScheme->setId('VAT');
            $taxScheme->setName('ДДВ'); // VAT in Macedonian

            $taxCategory = new TaxCategory();
            $taxCategory->setId($this->getTaxCategoryId($taxType->percent));
            $taxCategory->setPercent($taxType->percent);
            $taxCategory->setTaxScheme($taxScheme);

            $taxSubTotal = new TaxSubTotal();
            $taxSubTotal->setTaxableAmount($groupTaxableAmount);
            $taxSubTotal->setTaxAmount($groupTaxAmount);
            $taxSubTotal->setTaxCategory($taxCategory);

            $taxSubTotals[] = $taxSubTotal;
            $totalTaxAmount += $groupTaxAmount;
        }
        
        if (!empty($taxSubTotals)) {
            $taxTotal = new TaxTotal();
            $taxTotal->setTaxAmount($totalTaxAmount);

            // Add each tax subtotal individually
            foreach ($taxSubTotals as $taxSubTotal) {
                $taxTotal->addTaxSubTotal($taxSubTotal);
            }

            $ublInvoice->setTaxTotal($taxTotal);
        }
    }

    /**
     * Set legal monetary totals
     */
    protected function setLegalMonetaryTotal(UBLInvoice $ublInvoice): void
    {
        $legalMonetaryTotal = new LegalMonetaryTotal();
        
        // Line extension amount (subtotal without tax)
        $legalMonetaryTotal->setLineExtensionAmount($this->invoice->sub_total);
        
        // Tax exclusive amount (same as line extension for standard invoices)
        $legalMonetaryTotal->setTaxExclusiveAmount($this->invoice->sub_total);
        
        // Tax inclusive amount (total with tax)
        $legalMonetaryTotal->setTaxInclusiveAmount($this->invoice->total);
        
        // Payable amount (final amount to pay)
        $legalMonetaryTotal->setPayableAmount($this->invoice->total);
        
        // Allowance total (discounts)
        $discountAmount = $this->invoice->discount_val ?? 0;
        $legalMonetaryTotal->setAllowanceTotalAmount($discountAmount);
        
        $ublInvoice->setLegalMonetaryTotal($legalMonetaryTotal);
    }

    /**
     * Get UBL tax category ID based on tax percentage
     */
    protected function getTaxCategoryId(float $percent): string
    {
        if ($percent == 0) {
            return 'Z'; // Zero rate
        } elseif ($percent == 5) {
            return 'AA'; // Lower rate (Macedonia reduced VAT)
        } elseif ($percent == 18) {
            return 'S'; // Standard rate (Macedonia standard VAT)
        } else {
            return 'S'; // Default to standard
        }
    }

    /**
     * Validate generated UBL XML against XSD schema
     */
    public function validateUblXml(string $xml): array
    {
        $errors = [];
        
        // Enable internal error handling
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        // Create DOMDocument
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        // Load UBL 2.1 Invoice XSD schema
        $schemaPath = $this->getUblSchemaPath();
        
        if (!file_exists($schemaPath)) {
            $errors[] = "UBL Schema file not found: {$schemaPath}";
            return $errors;
        }
        
        // Validate against schema
        if (!$dom->schemaValidate($schemaPath)) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $error) {
                $errors[] = "Line {$error->line}: {$error->message}";
            }
        }
        
        libxml_clear_errors();
        return $errors;
    }

    /**
     * Get path to UBL XSD schema file
     */
    protected function getUblSchemaPath(): string
    {
        // Path to UBL 2.1 Invoice schema in proper directory structure
        return storage_path('schemas/maindoc/UBL-Invoice-2.1.xsd');
    }

    /**
     * Get Macedonian business context information
     */
    public function getMacedonianContext(): array
    {
        return [
            'country_code' => 'MK',
            'currency' => 'MKD',
            'standard_vat_rate' => 18,
            'reduced_vat_rate' => 5,
            'tax_scheme' => 'VAT',
            'tax_scheme_name' => 'ДДВ',
            'language' => 'mk',
            'time_zone' => 'Europe/Skopje'
        ];
    }
}