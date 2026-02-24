<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use DOMDocument;
use DOMElement;

/**
 * Corporate Income Tax XML Service (ДБ-ВП / DB-VP)
 *
 * Generates XML files compliant with North Macedonia Public Revenue Office
 * Corporate Income Tax (Данок на добивка) annual return format.
 *
 * Follows the same DOMDocument pattern as VatXmlService.
 */
class CitXmlService
{
    protected const CIT_NAMESPACE = 'http://www.ujp.gov.mk/db';

    /**
     * Generate CIT return XML.
     *
     * @param  array  $citData  Output from CorporateIncomeTaxService::calculate()
     */
    public function generate(Company $company, int $year, array $citData): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(self::CIT_NAMESPACE, 'DBVP');
        $root->setAttribute('version', '1.0');
        $dom->appendChild($root);

        $this->buildHeader($dom, $root, $year);
        $this->buildTaxPayer($dom, $root, $company);
        $this->buildTaxPeriod($dom, $root, $year);
        $this->buildIncomeStatement($dom, $root, $citData);
        $this->buildTaxBaseAdjustments($dom, $root, $citData);
        $this->buildTaxCalculation($dom, $root, $citData);
        $this->buildAdvanceReconciliation($dom, $root, $citData);
        $this->buildDeclaration($dom, $root, $company);

        return $dom->saveXML();
    }

    /**
     * Build Header section.
     */
    protected function buildHeader(DOMDocument $dom, DOMElement $root, int $year): void
    {
        $header = $dom->createElement('Header');
        $root->appendChild($header);

        $header->appendChild($dom->createElement('DocumentType', 'DB-VP'));
        $header->appendChild($dom->createElement('SubmissionDate', now()->format('Y-m-d')));
        $header->appendChild($dom->createElement('FormVersion', (string) $year));

        $softwareInfo = $dom->createElement('SoftwareInfo');
        $header->appendChild($softwareInfo);

        $softwareInfo->appendChild($dom->createElement('SoftwareName', 'Facturino Macedonia'));
        $softwareInfo->appendChild($dom->createElement('SoftwareVersion', '1.0.0'));
        $softwareInfo->appendChild($dom->createElement('SoftwareProvider', 'Facturino'));
    }

    /**
     * Build TaxPayer section.
     */
    protected function buildTaxPayer(DOMDocument $dom, DOMElement $root, Company $company): void
    {
        $taxPayer = $dom->createElement('TaxPayer');
        $root->appendChild($taxPayer);

        // ЕДБ (Единствен Даночен Број) - 13 digits, no prefix
        $edb = $this->formatMacedoniaVatNumber($company->vat_number);
        $taxPayer->appendChild($dom->createElement('EDB', $edb));
        $taxPayer->appendChild($dom->createElement('CompanyName', htmlspecialchars($company->name)));

        $address = $dom->createElement('Address');
        $taxPayer->appendChild($address);

        $companyAddress = $company->address;
        $address->appendChild($dom->createElement('Street', htmlspecialchars($companyAddress->address_street_1 ?? '')));
        $address->appendChild($dom->createElement('City', htmlspecialchars($companyAddress->city ?? '')));
        $address->appendChild($dom->createElement('PostalCode', htmlspecialchars($companyAddress->zip ?? '')));
        $address->appendChild($dom->createElement('Country', 'MK'));

        if ($companyAddress->state ?? null) {
            $address->appendChild($dom->createElement('Municipality', htmlspecialchars($companyAddress->state)));
        }

        $contactInfo = $dom->createElement('ContactInfo');
        $taxPayer->appendChild($contactInfo);

        if ($companyAddress->phone ?? null) {
            $contactInfo->appendChild($dom->createElement('Phone', htmlspecialchars($companyAddress->phone)));
        }
        if ($company->owner && $company->owner->email) {
            $contactInfo->appendChild($dom->createElement('Email', htmlspecialchars($company->owner->email)));
        }
    }

    /**
     * Build TaxPeriod section (annual).
     */
    protected function buildTaxPeriod(DOMDocument $dom, DOMElement $root, int $year): void
    {
        $taxPeriod = $dom->createElement('TaxPeriod');
        $root->appendChild($taxPeriod);

        $taxPeriod->appendChild($dom->createElement('Year', (string) $year));
        $taxPeriod->appendChild($dom->createElement('Period', 'ANNUAL'));
        $taxPeriod->appendChild($dom->createElement('PeriodStart', "{$year}-01-01"));
        $taxPeriod->appendChild($dom->createElement('PeriodEnd', "{$year}-12-31"));
    }

    /**
     * Build IncomeStatement section — revenue, expenses, operating result.
     */
    protected function buildIncomeStatement(DOMDocument $dom, DOMElement $root, array $citData): void
    {
        $section = $dom->createElement('IncomeStatement');
        $root->appendChild($section);

        $accountingProfit = $citData['accounting_profit'] ?? 0;
        $totalRevenue = 0;
        $totalExpenses = 0;

        if (isset($citData['income_statement'])) {
            $totalRevenue = $citData['income_statement']['total_revenue'] ?? 0;
            $totalExpenses = $citData['income_statement']['total_expenses'] ?? 0;
        } else {
            // Derive from accounting profit: if positive, revenue > expenses
            $totalRevenue = max($accountingProfit, 0);
            $totalExpenses = $totalRevenue - $accountingProfit;
        }

        $section->appendChild($dom->createElement('TotalRevenue', $this->formatAmount($totalRevenue)));
        $section->appendChild($dom->createElement('TotalExpenses', $this->formatAmount($totalExpenses)));
        $section->appendChild($dom->createElement('OperatingResult', $this->formatAmount($accountingProfit)));

        if ($accountingProfit >= 0) {
            $section->appendChild($dom->createElement('ResultType', 'PROFIT'));
        } else {
            $section->appendChild($dom->createElement('ResultType', 'LOSS'));
        }
    }

    /**
     * Build TaxBaseAdjustments section — non-deductible expenses and reliefs.
     */
    protected function buildTaxBaseAdjustments(DOMDocument $dom, DOMElement $root, array $citData): void
    {
        $section = $dom->createElement('TaxBaseAdjustments');
        $root->appendChild($section);

        // Non-deductible expenses (add-backs)
        $addBacks = $dom->createElement('NonDeductibleExpenses');
        $section->appendChild($addBacks);

        $adjustments = $citData['adjustments'] ?? [];
        $totalAddBacks = 0;

        foreach ($adjustments as $i => $adj) {
            $item = $dom->createElement('Adjustment');
            $addBacks->appendChild($item);

            $item->appendChild($dom->createElement('LineNumber', (string) ($i + 1)));
            $item->appendChild($dom->createElement('Description', htmlspecialchars($adj['description'] ?? '')));
            $item->appendChild($dom->createElement('Amount', $this->formatAmount($adj['amount'] ?? 0)));

            $totalAddBacks += (float) ($adj['amount'] ?? 0);
        }

        $addBacks->appendChild($dom->createElement('TotalNonDeductible', $this->formatAmount($totalAddBacks)));

        // Tax reliefs
        $reliefs = $dom->createElement('TaxReliefs');
        $section->appendChild($reliefs);

        $lossCarryforward = $citData['loss_carryforward'] ?? 0;
        $reliefs->appendChild($dom->createElement('LossCarryforward', $this->formatAmount($lossCarryforward)));
        $reliefs->appendChild($dom->createElement('TotalReliefs', $this->formatAmount($lossCarryforward)));
    }

    /**
     * Build TaxCalculation section — taxable base, rate, CIT amount.
     */
    protected function buildTaxCalculation(DOMDocument $dom, DOMElement $root, array $citData): void
    {
        $section = $dom->createElement('TaxCalculation');
        $root->appendChild($section);

        $section->appendChild($dom->createElement('AccountingProfit', $this->formatAmount($citData['accounting_profit'] ?? 0)));
        $section->appendChild($dom->createElement('TotalAdjustments', $this->formatAmount($citData['total_adjustments'] ?? 0)));
        $section->appendChild($dom->createElement('LossCarryforward', $this->formatAmount($citData['loss_carryforward'] ?? 0)));
        $section->appendChild($dom->createElement('TaxableBase', $this->formatAmount($citData['taxable_base'] ?? 0)));
        $section->appendChild($dom->createElement('TaxRate', number_format(($citData['cit_rate'] ?? 0.10) * 100, 2, '.', '')));
        $section->appendChild($dom->createElement('CITAmount', $this->formatAmount($citData['cit_amount'] ?? 0)));
    }

    /**
     * Build AdvanceReconciliation section — monthly advances vs actual CIT.
     */
    protected function buildAdvanceReconciliation(DOMDocument $dom, DOMElement $root, array $citData): void
    {
        $section = $dom->createElement('AdvanceReconciliation');
        $root->appendChild($section);

        $citAmount = $citData['cit_amount'] ?? 0;
        $advancePayments = $citData['advance_payments'] ?? 0;
        $balanceDue = $citData['balance_due'] ?? ($citAmount - $advancePayments);

        $section->appendChild($dom->createElement('TotalAdvancePayments', $this->formatAmount($advancePayments)));
        $section->appendChild($dom->createElement('CITDue', $this->formatAmount($citAmount)));

        if ($balanceDue >= 0) {
            $section->appendChild($dom->createElement('BalanceDue', $this->formatAmount($balanceDue)));
            $section->appendChild($dom->createElement('BalanceRefund', '0.00'));
        } else {
            $section->appendChild($dom->createElement('BalanceDue', '0.00'));
            $section->appendChild($dom->createElement('BalanceRefund', $this->formatAmount(abs($balanceDue))));
        }
    }

    /**
     * Build Declaration section.
     */
    protected function buildDeclaration(DOMDocument $dom, DOMElement $root, Company $company): void
    {
        $declaration = $dom->createElement('Declaration');
        $root->appendChild($declaration);

        $company->loadMissing('owner');
        $ownerName = $company->owner ? $company->owner->name : $company->name;
        $position = 'Управител';

        $declaration->appendChild($dom->createElement('DeclarantName', htmlspecialchars($ownerName)));
        $declaration->appendChild($dom->createElement('DeclarantPosition', htmlspecialchars($position)));
        $declaration->appendChild($dom->createElement('DeclarationDate', now()->format('Y-m-d')));

        $responsiblePerson = $dom->createElement('ResponsiblePerson');
        $declaration->appendChild($responsiblePerson);

        $responsiblePerson->appendChild($dom->createElement('Name', htmlspecialchars($ownerName)));
        $responsiblePerson->appendChild($dom->createElement('Position', htmlspecialchars($position)));

        if ($company->owner && $company->owner->email) {
            $responsiblePerson->appendChild($dom->createElement('Email', htmlspecialchars($company->owner->email)));
        }
    }

    /**
     * Format amount for XML output (2 decimal places, no thousands separator).
     */
    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Format VAT number to Macedonia ЕДБ standard (13 digits, no prefix).
     * UJP uses plain 13-digit ЕДБ numbers (e.g. 4004026525934), not EU "MK" format.
     */
    protected function formatMacedoniaVatNumber(?string $vatNumber): string
    {
        if (!$vatNumber) {
            return '0000000000000';
        }

        $cleanNumber = preg_replace('/^(MK|МК)/i', '', $vatNumber);
        $cleanNumber = preg_replace('/[^0-9]/', '', $cleanNumber);

        // ЕДБ is 13 digits
        $cleanNumber = str_pad(substr($cleanNumber, 0, 13), 13, '0', STR_PAD_LEFT);

        return $cleanNumber;
    }
}

// CLAUDE-CHECKPOINT
