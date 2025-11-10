<?php

namespace Modules\Mk\Services;

use App\Models\CreditNote;
use App\Models\Company;
use NumNum\UBL\CreditNote as UBLCreditNote;
use NumNum\UBL\CreditNoteLine;
use NumNum\UBL\Party;
use NumNum\UBL\Address;
use NumNum\UBL\Country;
use NumNum\UBL\Contact;
use NumNum\UBL\Item;
use NumNum\UBL\Price;
use NumNum\UBL\TaxTotal;
use NumNum\UBL\TaxSubTotal;
use NumNum\UBL\TaxCategory;
use NumNum\UBL\TaxScheme;
use NumNum\UBL\LegalMonetaryTotal;
use NumNum\UBL\PaymentMeans;
use NumNum\UBL\PaymentTerms;
use NumNum\UBL\BillingReference;
use NumNum\UBL\InvoiceDocumentReference;
use NumNum\UBL\Generator;
use Carbon\Carbon;

/**
 * Macedonian UBL CreditNote Mapper
 *
 * Maps InvoiceShelf credit notes to UBL 2.1 CreditNote XML format for Macedonian tax compliance
 * Supports Macedonian VAT rates (18%, 5%), company details, and local business requirements
 * References original invoice and includes credit note reason codes
 */
class MkUblCreditNoteMapper
{
    protected $creditNote;
    protected $company;

    /**
     * Create UBL XML from InvoiceShelf credit note
     */
    public function mapCreditNoteToUbl(CreditNote $creditNote): string
    {
        $this->creditNote = $creditNote;
        $this->company = $creditNote->company;

        // Create UBL CreditNote document
        $ublCreditNote = new UBLCreditNote();

        // Set basic credit note information
        $this->setBasicInformation($ublCreditNote);

        // Set billing reference to original invoice
        $this->setBillingReference($ublCreditNote);

        // Set supplier (company) information
        $this->setSupplierParty($ublCreditNote);

        // Set customer information
        $this->setCustomerParty($ublCreditNote);

        // Set payment information
        $this->setPaymentInformation($ublCreditNote);

        // Set credit note lines
        $this->setCreditNoteLines($ublCreditNote);

        // Set tax totals
        $this->setTaxTotals($ublCreditNote);

        // Set monetary totals (negative amounts for credits)
        $this->setLegalMonetaryTotal($ublCreditNote);

        // Generate XML
        $generator = new Generator();
        return $generator->creditNote($ublCreditNote);
    }

    /**
     * Set basic credit note information
     */
    protected function setBasicInformation(UBLCreditNote $ublCreditNote): void
    {
        $ublCreditNote->setId($this->creditNote->credit_note_number);
        $ublCreditNote->setIssueDate(Carbon::parse($this->creditNote->credit_note_date));

        // Set document currency (default MKD for Macedonia)
        $ublCreditNote->setDocumentCurrencyCode($this->creditNote->currency->code ?? 'MKD');

        // Set credit note type code (381 = Credit note)
        $ublCreditNote->setInvoiceTypeCode('381');

        // Add credit note reason/notes if present
        if ($this->creditNote->notes) {
            $ublCreditNote->setNote($this->creditNote->notes);
        }
    }

    /**
     * Set billing reference to original invoice
     */
    protected function setBillingReference(UBLCreditNote $ublCreditNote): void
    {
        // If this credit note references an invoice, add the billing reference
        if ($this->creditNote->invoice) {
            $invoiceDocRef = new InvoiceDocumentReference();
            $invoiceDocRef->setOriginalInvoiceId($this->creditNote->invoice->invoice_number);

            if ($this->creditNote->invoice->invoice_date) {
                $invoiceDocRef->setIssueDate(Carbon::parse($this->creditNote->invoice->invoice_date));
            }

            $billingReference = new BillingReference();
            $billingReference->setInvoiceDocumentReference($invoiceDocRef);

            $ublCreditNote->setBillingReferences([$billingReference]);
        }
    }

    /**
     * Set supplier (company) party information
     */
    protected function setSupplierParty(UBLCreditNote $ublCreditNote): void
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
            $supplierParty->setCompanyId($this->company->vat_number);
        }

        $ublCreditNote->setAccountingSupplierParty($supplierParty);
    }

    /**
     * Set customer party information
     */
    protected function setCustomerParty(UBLCreditNote $ublCreditNote): void
    {
        $customer = $this->creditNote->customer;

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
            $customerParty->setCompanyId($customer->tax_id);
        }

        $ublCreditNote->setAccountingCustomerParty($customerParty);
    }

    /**
     * Set payment information
     */
    protected function setPaymentInformation(UBLCreditNote $ublCreditNote): void
    {
        // Payment means (bank transfer is most common in Macedonia)
        $paymentMeans = new PaymentMeans();
        $paymentMeans->setPaymentMeansCode('30'); // Credit transfer

        // Add bank account information if available
        if ($this->company->bankAccounts->count() > 0) {
            $bankAccount = $this->company->bankAccounts->where('is_primary', true)->first()
                        ?? $this->company->bankAccounts->first();

            if ($bankAccount->iban) {
                $paymentMeans->setPayeeFinancialAccount($bankAccount->iban);
            }
        }

        $ublCreditNote->setPaymentMeans([$paymentMeans]);

        // Payment terms - credit notes typically reference the original invoice terms
        $paymentTerms = new PaymentTerms();
        $paymentTerms->setNote("Кредитно известување"); // Credit note in Macedonian
        $ublCreditNote->setPaymentTerms([$paymentTerms]);
    }

    /**
     * Set credit note lines
     */
    protected function setCreditNoteLines(UBLCreditNote $ublCreditNote): void
    {
        $creditNoteLines = [];

        foreach ($this->creditNote->items as $index => $item) {
            $creditNoteLine = new CreditNoteLine();
            $creditNoteLine->setId($index + 1);
            $creditNoteLine->setCreditedQuantity($item->quantity);

            // Create UBL Item
            $ublItem = new Item();
            $ublItem->setName($item->name);
            $ublItem->setDescription($item->description ?? '');

            $creditNoteLine->setItem($ublItem);

            // Set price
            $price = new Price();
            $price->setPriceAmount($item->price);
            $creditNoteLine->setPrice($price);

            // Calculate line total (negative for credit)
            $lineTotal = $item->quantity * $item->price;
            $creditNoteLine->setLineExtensionAmount($lineTotal);

            // Set tax information for this line
            if ($item->taxes->count() > 0) {
                $this->setLineTaxInfo($creditNoteLine, $item);
            }

            $creditNoteLines[] = $creditNoteLine;
        }

        $ublCreditNote->setCreditNoteLines($creditNoteLines);
    }

    /**
     * Set tax information for credit note line
     */
    protected function setLineTaxInfo(CreditNoteLine $creditNoteLine, $item): void
    {
        $taxSubTotals = [];

        foreach ($item->taxes as $tax) {
            $taxScheme = new TaxScheme();
            $taxScheme->setId('VAT'); // Standard VAT

            $taxCategory = new TaxCategory();
            $taxCategory->setId('S'); // Standard rate
            $taxCategory->setPercent($tax->tax_type->percent);
            $taxCategory->setTaxScheme($taxScheme);

            $taxSubTotal = new TaxSubTotal();
            $taxSubTotal->setTaxableAmount($item->quantity * $item->price);
            $taxSubTotal->setTaxAmount($tax->amount);
            $taxSubTotal->setTaxCategory($taxCategory);

            $taxSubTotals[] = $taxSubTotal;
        }

        if (!empty($taxSubTotals)) {
            $taxTotal = new TaxTotal();
            $taxTotal->setTaxSubtotals($taxSubTotals);

            // Calculate total tax amount for this line
            $totalTaxAmount = collect($taxSubTotals)->sum(function ($subTotal) {
                return $subTotal->getTaxAmount();
            });
            $taxTotal->setTaxAmount($totalTaxAmount);

            $creditNoteLine->setTaxTotal([$taxTotal]);
        }
    }

    /**
     * Set credit note tax totals
     */
    protected function setTaxTotals(UBLCreditNote $ublCreditNote): void
    {
        $taxSubTotals = [];
        $totalTaxAmount = 0;

        // Group taxes by tax type
        $taxGroups = $this->creditNote->taxes->groupBy('tax_type_id');

        foreach ($taxGroups as $taxTypeId => $taxes) {
            $taxType = $taxes->first()->tax_type;
            $groupTaxAmount = $taxes->sum('amount');
            $groupTaxableAmount = $taxes->sum(function ($tax) {
                return $tax->tax_type->percent > 0 ? ($tax->amount / $tax->tax_type->percent * 100) : 0;
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
            $taxTotal->setTaxSubtotals($taxSubTotals);

            $ublCreditNote->setTaxTotal([$taxTotal]);
        }
    }

    /**
     * Set legal monetary totals (negative amounts for credit notes)
     */
    protected function setLegalMonetaryTotal(UBLCreditNote $ublCreditNote): void
    {
        $legalMonetaryTotal = new LegalMonetaryTotal();

        // Line extension amount (subtotal without tax) - negative for credit
        $legalMonetaryTotal->setLineExtensionAmount($this->creditNote->sub_total);

        // Tax exclusive amount (same as line extension for standard credit notes)
        $legalMonetaryTotal->setTaxExclusiveAmount($this->creditNote->sub_total);

        // Tax inclusive amount (total with tax) - negative for credit
        $legalMonetaryTotal->setTaxInclusiveAmount($this->creditNote->total);

        // Payable amount (final amount to credit) - negative
        $legalMonetaryTotal->setPayableAmount($this->creditNote->total);

        // Allowance total (discounts)
        $discountAmount = $this->creditNote->discount_val ?? 0;
        $legalMonetaryTotal->setAllowanceTotalAmount($discountAmount);

        $ublCreditNote->setLegalMonetaryTotal($legalMonetaryTotal);
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

        // Load UBL 2.1 CreditNote XSD schema
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
        // Path to UBL 2.1 CreditNote schema in proper directory structure
        return storage_path('schemas/maindoc/UBL-CreditNote-2.1.xsd');
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
            'time_zone' => 'Europe/Skopje',
            'credit_note_type_code' => '381',
        ];
    }

    /**
     * Get credit note reason codes for Macedonia
     */
    public function getCreditNoteReasonCodes(): array
    {
        return [
            '1' => 'Грешка во фактура', // Invoice error
            '2' => 'Враќање на стока', // Goods return
            '3' => 'Попуст', // Discount
            '4' => 'Откажување', // Cancellation
            '5' => 'Корекција на цена', // Price correction
        ];
    }
}

// CLAUDE-CHECKPOINT
