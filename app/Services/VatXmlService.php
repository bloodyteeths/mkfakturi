<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\TaxType;
use App\Models\Tax;
use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Exception;

/**
 * Macedonia ДДВ-04 VAT Return XML Generator Service
 * 
 * Generates XML files compliant with North Macedonia Public Revenue Office 
 * ДДВ-04 (VAT return) format for automated tax compliance.
 * 
 * Key features:
 * - Supports Macedonia VAT rates (18% standard, 5% reduced)
 * - Generates from existing invoice/payment data
 * - XSD schema validation
 * - Cyrillic text support
 * - Handles MKD currency and Macedonia business requirements
 */
class VatXmlService
{
    protected const SCHEMA_PATH = 'storage/schemas/mk_ddv04.xsd';
    protected const VAT_NAMESPACE = 'http://www.ujp.gov.mk/ddv04';
    protected const STANDARD_VAT_RATE = 18.00;
    protected const REDUCED_VAT_RATE = 5.00;
    
    protected $company;
    protected Carbon $periodStart;
    protected Carbon $periodEnd;
    protected string $periodType;
    
    /**
     * Generate ДДВ-04 XML for specified company and period
     */
    public function generateVatReturn(
        $company, 
        Carbon $periodStart, 
        Carbon $periodEnd, 
        string $periodType = 'MONTHLY'
    ): string {
        $this->company = $company;
        $this->periodStart = $periodStart;
        $this->periodEnd = $periodEnd;
        $this->periodType = $periodType;
        
        // Create XML document
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        
        // Create root element with namespace
        $root = $dom->createElementNS(self::VAT_NAMESPACE, 'DDV04');
        $root->setAttribute('version', '1.0');
        $dom->appendChild($root);
        
        // Build XML structure
        $this->buildHeader($dom, $root);
        $this->buildTaxPayer($dom, $root);
        $this->buildTaxPeriod($dom, $root);
        $this->buildVATCalculation($dom, $root);
        $this->buildVATSummary($dom, $root);
        $this->buildDeclaration($dom, $root);
        
        return $dom->saveXML();
    }
    
    /**
     * Validate generated XML against ДДВ-04 XSD schema
     */
    public function validateXml(string $xml): bool
    {
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        
        // Try Laravel storage_path first, fallback to relative path
        try {
            if (function_exists('storage_path') && function_exists('app') && app()->bound('path.storage')) {
                $schemaPath = storage_path('schemas/mk_ddv04.xsd');
            } else {
                $schemaPath = 'storage/schemas/mk_ddv04.xsd';
            }
        } catch (Exception $e) {
            $schemaPath = 'storage/schemas/mk_ddv04.xsd';
        }
        
        if (!file_exists($schemaPath)) {
            throw new InvalidArgumentException("XSD schema file not found: {$schemaPath}");
        }
        
        return $dom->schemaValidate($schemaPath);
    }
    
    /**
     * Get validation errors from XML
     */
    public function getValidationErrors(string $xml): array
    {
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $this->validateXml($xml);
        
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $errors[] = trim($error->message);
        }
        
        libxml_clear_errors();
        return $errors;
    }

    /**
     * Get currency precision for the company
     *
     * @return int Currency precision (0 for MKD, 2 for USD, etc.)
     */
    protected function getCurrencyPrecision(): int
    {
        if (!$this->company || !$this->company->id) {
            return 2; // Default to 2 decimal places if company not set
        }

        $currencyId = CompanySetting::getSetting('currency', $this->company->id);
        if (!$currencyId) {
            return 2; // Default to 2 decimal places if currency not set
        }

        $currency = Currency::find($currencyId);
        if (!$currency) {
            return 2; // Default to 2 decimal places if currency not found
        }

        return (int) $currency->precision;
    }

    /**
     * Format amount for XML output based on currency precision
     *
     * @param float $amount Amount in smallest unit (cents/denars)
     * @return string Formatted amount
     */
    protected function formatAmount(float $amount): string
    {
        $precision = $this->getCurrencyPrecision();

        // For zero-precision currencies (like MKD), don't divide by 100
        // For standard currencies (like USD), divide by 100 to get dollars from cents
        $value = $precision === 0 ? $amount : $amount / 100;

        return number_format($value, 2);
    }

    /**
     * Build Header section
     */
    protected function buildHeader(DOMDocument $dom, DOMElement $root): void
    {
        $header = $dom->createElement('Header');
        $root->appendChild($header);
        
        $header->appendChild($dom->createElement('DocumentType', 'DDV-04'));
        $header->appendChild($dom->createElement('SubmissionDate', now()->format('Y-m-d')));
        $header->appendChild($dom->createElement('FormVersion', '2024'));
        
        // Software info
        $softwareInfo = $dom->createElement('SoftwareInfo');
        $header->appendChild($softwareInfo);
        
        $softwareInfo->appendChild($dom->createElement('SoftwareName', 'Facturino Macedonia'));
        $softwareInfo->appendChild($dom->createElement('SoftwareVersion', '1.0.0'));
        $softwareInfo->appendChild($dom->createElement('SoftwareProvider', 'Facturino'));
    }
    
    /**
     * Build TaxPayer section with company information
     */
    protected function buildTaxPayer(DOMDocument $dom, DOMElement $root): void
    {
        $taxPayer = $dom->createElement('TaxPayer');
        $root->appendChild($taxPayer);
        
        // VAT number - ensure Macedonia format (MK + 11 digits)
        $vatNumber = $this->formatMacedoniaVatNumber($this->company->vat_number);
        $taxPayer->appendChild($dom->createElement('VATNumber', $vatNumber));
        $taxPayer->appendChild($dom->createElement('CompanyName', htmlspecialchars($this->company->name)));
        
        // Address
        $address = $dom->createElement('Address');
        $taxPayer->appendChild($address);
        
        $companyAddress = $this->company->address;
        $address->appendChild($dom->createElement('Street', htmlspecialchars($companyAddress->address_street_1 ?? '')));
        $address->appendChild($dom->createElement('City', htmlspecialchars($companyAddress->city ?? '')));
        $address->appendChild($dom->createElement('PostalCode', htmlspecialchars($companyAddress->zip ?? '')));
        $address->appendChild($dom->createElement('Country', 'MK'));
        if ($companyAddress->state) {
            $address->appendChild($dom->createElement('Municipality', htmlspecialchars($companyAddress->state)));
        }
        
        // Contact info
        $contactInfo = $dom->createElement('ContactInfo');
        $taxPayer->appendChild($contactInfo);
        
        if ($companyAddress->phone) {
            $contactInfo->appendChild($dom->createElement('Phone', htmlspecialchars($companyAddress->phone)));
        }
        if ($this->company->owner && $this->company->owner->email) {
            $contactInfo->appendChild($dom->createElement('Email', htmlspecialchars($this->company->owner->email)));
        }
        if ($this->company->website) {
            $contactInfo->appendChild($dom->createElement('Website', htmlspecialchars($this->company->website)));
        }
        
        // Tax payer category - default to STANDARD
        $taxPayer->appendChild($dom->createElement('TaxPayerCategory', 'STANDARD'));
    }
    
    /**
     * Build TaxPeriod section
     */
    protected function buildTaxPeriod(DOMDocument $dom, DOMElement $root): void
    {
        $taxPeriod = $dom->createElement('TaxPeriod');
        $root->appendChild($taxPeriod);
        
        $taxPeriod->appendChild($dom->createElement('Year', $this->periodStart->format('Y')));
        $taxPeriod->appendChild($dom->createElement('Period', $this->periodType));
        $taxPeriod->appendChild($dom->createElement('PeriodStart', $this->periodStart->format('Y-m-d')));
        $taxPeriod->appendChild($dom->createElement('PeriodEnd', $this->periodEnd->format('Y-m-d')));
    }
    
    /**
     * Build VATCalculation section with rates and amounts
     */
    protected function buildVATCalculation(DOMDocument $dom, DOMElement $root): void
    {
        $vatCalculation = $dom->createElement('VATCalculation');
        $root->appendChild($vatCalculation);
        
        // Get VAT data for the period
        $vatData = $this->calculateVatForPeriod();
        
        // Standard rate (18%)
        $standardRate = $dom->createElement('StandardRate');
        $vatCalculation->appendChild($standardRate);
        $this->buildVATRateElement($dom, $standardRate, self::STANDARD_VAT_RATE, $vatData['standard']);
        
        // Reduced rate (5%)
        $reducedRate = $dom->createElement('ReducedRate');
        $vatCalculation->appendChild($reducedRate);
        $this->buildVATRateElement($dom, $reducedRate, self::REDUCED_VAT_RATE, $vatData['reduced']);
        
        // Zero rate (0%)
        $zeroRate = $dom->createElement('ZeroRate');
        $vatCalculation->appendChild($zeroRate);
        $this->buildVATRateElement($dom, $zeroRate, 0.00, $vatData['zero']);
        
        // Exempt supplies
        $exemptSupplies = $dom->createElement('ExemptSupplies');
        $vatCalculation->appendChild($exemptSupplies);
        $this->buildVATRateElement($dom, $exemptSupplies, 0.00, $vatData['exempt']);
        
        // Input VAT (simplified - would need expense/purchase data)
        $inputVAT = $dom->createElement('InputVAT');
        $vatCalculation->appendChild($inputVAT);
        
        $inputVAT->appendChild($dom->createElement('PurchaseVAT', '0.00'));
        $inputVAT->appendChild($dom->createElement('ImportVAT', '0.00'));
        $inputVAT->appendChild($dom->createElement('CapitalGoodsVAT', '0.00'));
        $inputVAT->appendChild($dom->createElement('OtherDeductibleVAT', '0.00'));
        $inputVAT->appendChild($dom->createElement('TotalInputVAT', '0.00'));
    }
    
    /**
     * Build VAT rate element
     */
    protected function buildVATRateElement(DOMDocument $dom, DOMElement $parent, float $rate, array $data): void
    {
        $precision = $this->getCurrencyPrecision();
        $formattedTaxableBase = $this->formatAmount($data['taxable_base']);
        $formattedVATAmount = $this->formatAmount($data['vat_amount']);

        \Log::info('VatXmlService::buildVATRateElement - Formatting amounts', [
            'rate' => $rate,
            'taxable_base_raw' => $data['taxable_base'],
            'vat_amount_raw' => $data['vat_amount'],
            'currency_precision' => $precision,
            'taxable_base_formatted' => $formattedTaxableBase,
            'vat_amount_formatted' => $formattedVATAmount,
        ]);

        $parent->appendChild($dom->createElement('Rate', number_format($rate, 2)));
        $parent->appendChild($dom->createElement('TaxableBase', $formattedTaxableBase));
        $parent->appendChild($dom->createElement('VATAmount', $formattedVATAmount));
        $parent->appendChild($dom->createElement('TransactionCount', $data['transaction_count']));
    }
    
    /**
     * Build VATSummary section
     */
    protected function buildVATSummary(DOMDocument $dom, DOMElement $root): void
    {
        $vatSummary = $dom->createElement('VATSummary');
        $root->appendChild($vatSummary);
        
        $vatData = $this->calculateVatForPeriod();
        $totalOutputVAT = $vatData['standard']['vat_amount'] + $vatData['reduced']['vat_amount'];
        $totalInputVAT = 0; // Would need expense/purchase data
        $netVATDue = max(0, $totalOutputVAT - $totalInputVAT);
        $vatRefund = max(0, $totalInputVAT - $totalOutputVAT);

        $vatSummary->appendChild($dom->createElement('TotalOutputVAT', $this->formatAmount($totalOutputVAT)));
        $vatSummary->appendChild($dom->createElement('TotalInputVAT', $this->formatAmount($totalInputVAT)));
        $vatSummary->appendChild($dom->createElement('NetVATDue', $this->formatAmount($netVATDue)));
        $vatSummary->appendChild($dom->createElement('VATRefund', $this->formatAmount($vatRefund)));
        $vatSummary->appendChild($dom->createElement('PreviousPeriodCredit', '0.00'));
        $vatSummary->appendChild($dom->createElement('VATToPay', $this->formatAmount($netVATDue)));
        $vatSummary->appendChild($dom->createElement('VATCarryForward', $this->formatAmount($vatRefund)));
    }
    
    /**
     * Build Declaration section
     */
    protected function buildDeclaration(DOMDocument $dom, DOMElement $root): void
    {
        $declaration = $dom->createElement('Declaration');
        $root->appendChild($declaration);
        
        $ownerName = $this->company->owner ? $this->company->owner->name : $this->company->name;
        
        $declaration->appendChild($dom->createElement('DeclarantName', htmlspecialchars($ownerName)));
        $declaration->appendChild($dom->createElement('DeclarantPosition', 'Управник / Director'));
        $declaration->appendChild($dom->createElement('DeclarationDate', now()->format('Y-m-d')));
        
        // Responsible person
        $responsiblePerson = $dom->createElement('ResponsiblePerson');
        $declaration->appendChild($responsiblePerson);
        
        $responsiblePerson->appendChild($dom->createElement('Name', htmlspecialchars($ownerName)));
        $responsiblePerson->appendChild($dom->createElement('Position', 'Управник / Director'));
        
        if ($this->company->owner && $this->company->owner->email) {
            $responsiblePerson->appendChild($dom->createElement('Email', htmlspecialchars($this->company->owner->email)));
        }
    }
    
    /**
     * Calculate VAT data for the specified period
     */
    protected function calculateVatForPeriod(): array
    {
        $vatData = [
            'standard' => ['taxable_base' => 0, 'vat_amount' => 0, 'transaction_count' => 0],
            'reduced' => ['taxable_base' => 0, 'vat_amount' => 0, 'transaction_count' => 0],
            'zero' => ['taxable_base' => 0, 'vat_amount' => 0, 'transaction_count' => 0],
            'exempt' => ['taxable_base' => 0, 'vat_amount' => 0, 'transaction_count' => 0],
        ];
        
        // Check if we're in a proper Laravel context with database connection
        $hasDatabase = false;
        if (function_exists('app')) {
            try {
                app('db')->connection()->getPdo();
                $hasDatabase = true;
            } catch (Exception $e) {
                $hasDatabase = false;
            }
        }
        
        // If company has an id property and we have database access, try to get real invoice data  
        if (isset($this->company->id) && class_exists('App\Models\Invoice') && $hasDatabase) {
            try {
                $invoices = Invoice::where('company_id', $this->company->id)
                    ->where('paid_status', Invoice::STATUS_PAID)
                    ->whereBetween('invoice_date', [
                        $this->periodStart->format('Y-m-d'),
                        $this->periodEnd->format('Y-m-d')
                    ])
                    ->with(['taxes.taxType', 'items.taxes.taxType'])
                    ->get();

                // Check if any invoices were found
                if ($invoices->isEmpty()) {
                    throw new Exception(
                        sprintf(
                            "No paid invoices found for period %s to %s. Please ensure you have invoices marked as PAID within this period.",
                            $this->periodStart->format('Y-m-d'),
                            $this->periodEnd->format('Y-m-d')
                        )
                    );
                }

                foreach ($invoices as $invoice) {
                    // Process invoice-level taxes
                    foreach ($invoice->taxes as $tax) {
                        $this->categorizeVatAmount($tax, $vatData);
                    }
                    
                    // Process item-level taxes
                    foreach ($invoice->items as $item) {
                        foreach ($item->taxes as $tax) {
                            $this->categorizeVatAmount($tax, $vatData);
                        }
                    }
                    
                    // Count transactions by dominant VAT rate
                    $dominantRate = $this->getDominantVatRate($invoice);
                    if ($dominantRate >= 15) {
                        $vatData['standard']['transaction_count']++;
                    } elseif ($dominantRate >= 3) {
                        $vatData['reduced']['transaction_count']++;
                    } else {
                        $vatData['zero']['transaction_count']++;
                    }
                }
            } catch (Exception $e) {
                // Re-throw exception with context
                throw new Exception("Failed to calculate VAT from invoices: " . $e->getMessage(), 0, $e);
            }
        } else {
            // No database access or company not set
            throw new Exception("Cannot calculate VAT: Company not properly initialized or database not accessible");
        }
        
        return $vatData;
    }
    
    /**
     * Get sample VAT data for testing
     */
    protected function getSampleVatData(): array
    {
        return [
            'standard' => [
                'taxable_base' => 50000000, // 500,000.00 MKD in cents
                'vat_amount' => 9000000,    // 90,000.00 MKD in cents (18%)
                'transaction_count' => 15
            ],
            'reduced' => [
                'taxable_base' => 10000000, // 100,000.00 MKD in cents
                'vat_amount' => 500000,     // 5,000.00 MKD in cents (5%)
                'transaction_count' => 5
            ],
            'zero' => [
                'taxable_base' => 5000000,  // 50,000.00 MKD in cents
                'vat_amount' => 0,          // 0.00 MKD (0%)
                'transaction_count' => 3
            ],
            'exempt' => [
                'taxable_base' => 2000000,  // 20,000.00 MKD in cents
                'vat_amount' => 0,          // 0.00 MKD (exempt)
                'transaction_count' => 2
            ],
        ];
    }
    
    /**
     * Categorize VAT amount by rate
     */
    protected function categorizeVatAmount(Tax $tax, array &$vatData): void
    {
        $rate = $tax->percent ?? $tax->taxType->percent ?? 0;
        $amount = $tax->amount ?? 0;

        // Log the raw values from database
        \Log::info('VatXmlService::categorizeVatAmount - Processing tax', [
            'tax_id' => $tax->id,
            'rate' => $rate,
            'amount_raw' => $amount,
            'base_amount' => $tax->base_amount ?? null,
            'invoice_item_total' => $tax->invoiceItem->total ?? null,
        ]);

        // Calculate taxable base from VAT amount and rate
        // Note: base_amount field stores currency-converted VAT amount, NOT taxable base
        // Formula: taxable_base = vat_amount / (rate / 100)
        $taxableBase = 0;
        if ($rate > 0) {
            $taxableBase = ($amount * 100) / $rate;
        } elseif ($tax->invoiceItem) {
            // For zero-rate or exempt, use the item total
            $taxableBase = $tax->invoiceItem->total ?? 0;
        }

        \Log::info('VatXmlService::categorizeVatAmount - Calculated values', [
            'taxable_base_calculated' => $taxableBase,
            'vat_amount' => $amount,
        ]);

        if ($rate >= 15) { // Standard rate (18%)
            $vatData['standard']['vat_amount'] += $amount;
            $vatData['standard']['taxable_base'] += $taxableBase;
        } elseif ($rate >= 3) { // Reduced rate (5%)
            $vatData['reduced']['vat_amount'] += $amount;
            $vatData['reduced']['taxable_base'] += $taxableBase;
        } else { // Zero or exempt
            $vatData['zero']['vat_amount'] += $amount;
            $vatData['zero']['taxable_base'] += $taxableBase;
        }
    }
    
    /**
     * Get dominant VAT rate for an invoice
     */
    protected function getDominantVatRate(Invoice $invoice): float
    {
        $rates = collect();
        
        foreach ($invoice->taxes as $tax) {
            $rates->push($tax->percent ?? $tax->taxType->percent ?? 0);
        }
        
        foreach ($invoice->items as $item) {
            foreach ($item->taxes as $tax) {
                $rates->push($tax->percent ?? $tax->taxType->percent ?? 0);
            }
        }
        
        return $rates->isEmpty() ? 0 : $rates->max();
    }
    
    /**
     * Format VAT number to Macedonia standard (MK + 11 digits)
     */
    protected function formatMacedoniaVatNumber(?string $vatNumber): string
    {
        if (!$vatNumber) {
            return 'MK00000000000'; // Default placeholder
        }
        
        // Remove any existing country prefix
        $cleanNumber = preg_replace('/^MK/i', '', $vatNumber);
        $cleanNumber = preg_replace('/[^0-9]/', '', $cleanNumber);
        
        // Pad or truncate to 11 digits
        $cleanNumber = str_pad(substr($cleanNumber, 0, 11), 11, '0', STR_PAD_LEFT);
        
        return 'MK' . $cleanNumber;
    }
}

