<?php

namespace Modules\Mk\Payroll\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Bank Payment File Service
 *
 * Generates SEPA Credit Transfer (pain.001.001.03) XML files for bulk salary payments.
 * Also provides CSV export as a fallback format.
 *
 * SEPA XML structure:
 * - GrpHdr: Message ID, creation datetime, number of transactions, control sum
 * - PmtInf: Payment info with company IBAN as debtor
 * - CdtTrfTxInf: One per employee with their IBAN and net salary amount
 *
 * Note: This service expects the PayrollRun model to exist.
 * The model will be created in the PAY-MODEL-01 ticket.
 */
class BankPaymentFileService
{
    /**
     * Generate SEPA Credit Transfer XML (pain.001.001.03)
     *
     * @param mixed $run PayrollRun model instance
     * @return string SEPA XML content
     */
    public function generateSepaXml($run): string
    {
        // Load relationships
        $run->load(['lines.employee', 'company']);

        // Validate IBANs
        $invalidEmployees = $this->validateIbans($run);
        if (! empty($invalidEmployees)) {
            throw new \Exception('Cannot generate SEPA XML: Some employees have invalid or missing IBANs');
        }

        $company = $run->company;
        $messageId = $this->generateMessageId($run);
        // Use period_end as payment date since PayrollRun doesn't have payment_date field
        $paymentDate = Carbon::parse($run->period_end)->format('Y-m-d');
        $creationDateTime = Carbon::now()->format('Y-m-d\TH:i:s');

        // Calculate totals
        $numberOfTransactions = $run->lines->count();
        $controlSum = $run->total_net;

        // Start building XML
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Root element: Document
        $document = $xml->createElement('Document');
        $document->setAttribute('xmlns', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');
        $document->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->appendChild($document);

        // CstmrCdtTrfInitn (Customer Credit Transfer Initiation)
        $cstmrCdtTrfInitn = $xml->createElement('CstmrCdtTrfInitn');
        $document->appendChild($cstmrCdtTrfInitn);

        // Group Header
        $grpHdr = $xml->createElement('GrpHdr');
        $cstmrCdtTrfInitn->appendChild($grpHdr);

        $grpHdr->appendChild($xml->createElement('MsgId', $messageId));
        $grpHdr->appendChild($xml->createElement('CreDtTm', $creationDateTime));
        $grpHdr->appendChild($xml->createElement('NbOfTxs', $numberOfTransactions));
        $grpHdr->appendChild($xml->createElement('CtrlSum', number_format($controlSum, 2, '.', '')));

        // Initiating Party (Company)
        $initgPty = $xml->createElement('InitgPty');
        $grpHdr->appendChild($initgPty);
        $initgPty->appendChild($xml->createElement('Nm', $this->sanitizeForXml($company->name)));

        // Payment Information
        $pmtInf = $xml->createElement('PmtInf');
        $cstmrCdtTrfInitn->appendChild($pmtInf);

        $pmtInfId = "PAYROLL-{$run->id}";
        $pmtInf->appendChild($xml->createElement('PmtInfId', $pmtInfId));
        $pmtInf->appendChild($xml->createElement('PmtMtd', 'TRF')); // Transfer
        $pmtInf->appendChild($xml->createElement('BtchBookg', 'true'));
        $pmtInf->appendChild($xml->createElement('NbOfTxs', $numberOfTransactions));
        $pmtInf->appendChild($xml->createElement('CtrlSum', number_format($controlSum, 2, '.', '')));

        // Payment Type Information
        $pmtTpInf = $xml->createElement('PmtTpInf');
        $pmtInf->appendChild($pmtTpInf);

        $svcLvl = $xml->createElement('SvcLvl');
        $pmtTpInf->appendChild($svcLvl);
        $svcLvl->appendChild($xml->createElement('Cd', 'SEPA'));

        // Requested Execution Date
        $pmtInf->appendChild($xml->createElement('ReqdExctnDt', $paymentDate));

        // Debtor (Company)
        $dbtr = $xml->createElement('Dbtr');
        $pmtInf->appendChild($dbtr);
        $dbtr->appendChild($xml->createElement('Nm', $this->sanitizeForXml($company->name)));

        // Debtor Account
        $dbtrAcct = $xml->createElement('DbtrAcct');
        $pmtInf->appendChild($dbtrAcct);

        $dbtrId = $xml->createElement('Id');
        $dbtrAcct->appendChild($dbtrId);
        $dbtrId->appendChild($xml->createElement('IBAN', $this->formatIban($company->iban ?? '')));

        // Debtor Agent (Bank)
        $dbtrAgt = $xml->createElement('DbtrAgt');
        $pmtInf->appendChild($dbtrAgt);

        $finInstnId = $xml->createElement('FinInstnId');
        $dbtrAgt->appendChild($finInstnId);
        $finInstnId->appendChild($xml->createElement('BIC', $company->bic ?? 'NOTPROVIDED'));

        // Charge Bearer
        $pmtInf->appendChild($xml->createElement('ChrgBr', 'SLEV')); // Service Level

        // Credit Transfer Transaction Information (one per employee)
        foreach ($run->lines as $line) {
            $employee = $line->employee;
            $amount = $line->net_salary;

            $cdtTrfTxInf = $xml->createElement('CdtTrfTxInf');
            $pmtInf->appendChild($cdtTrfTxInf);

            // Payment ID
            $pmtId = $xml->createElement('PmtId');
            $cdtTrfTxInf->appendChild($pmtId);
            $pmtId->appendChild($xml->createElement('EndToEndId', "PAYROLL-{$run->id}-EMP-{$employee->id}"));

            // Amount
            $amt = $xml->createElement('Amt');
            $cdtTrfTxInf->appendChild($amt);

            $instdAmt = $xml->createElement('InstdAmt', number_format($amount, 2, '.', ''));
            $instdAmt->setAttribute('Ccy', $company->currency_code ?? 'MKD');
            $amt->appendChild($instdAmt);

            // Creditor Agent (Employee's Bank)
            $cdtrAgt = $xml->createElement('CdtrAgt');
            $cdtTrfTxInf->appendChild($cdtrAgt);

            $cdtrFinInstnId = $xml->createElement('FinInstnId');
            $cdtrAgt->appendChild($cdtrFinInstnId);
            // Extract BIC from IBAN if not provided (Macedonian banks)
            $employeeBic = $this->extractBicFromIban($employee->bank_account_iban ?? '');
            $cdtrFinInstnId->appendChild($xml->createElement('BIC', $employeeBic));

            // Creditor (Employee)
            $cdtr = $xml->createElement('Cdtr');
            $cdtTrfTxInf->appendChild($cdtr);
            $employeeName = $this->sanitizeForXml("{$employee->first_name} {$employee->last_name}");
            $cdtr->appendChild($xml->createElement('Nm', $employeeName));

            // Creditor Account
            $cdtrAcct = $xml->createElement('CdtrAcct');
            $cdtTrfTxInf->appendChild($cdtrAcct);

            $cdtrId = $xml->createElement('Id');
            $cdtrAcct->appendChild($cdtrId);
            $cdtrId->appendChild($xml->createElement('IBAN', $this->formatIban($employee->bank_account_iban ?? '')));

            // Remittance Information
            $rmtInf = $xml->createElement('RmtInf');
            $cdtTrfTxInf->appendChild($rmtInf);

            $periodStart = Carbon::parse($run->period_start)->format('Y-m-d');
            $periodEnd = Carbon::parse($run->period_end)->format('Y-m-d');
            $ustrd = $xml->createElement('Ustrd', "Salary {$periodStart} to {$periodEnd}");
            $rmtInf->appendChild($ustrd);
        }

        $xmlContent = $xml->saveXML();

        Log::info('Generated SEPA XML for payroll run', [
            'payroll_run_id' => $run->id,
            'message_id' => $messageId,
            'transactions' => $numberOfTransactions,
            'total_amount' => $controlSum,
        ]);

        return $xmlContent;
    }

    /**
     * Generate and save payment file to storage
     *
     * @param mixed $run PayrollRun model instance
     * @return string Path to generated file
     */
    public function generatePaymentFile($run): string
    {
        // Generate SEPA XML
        $xmlContent = $this->generateSepaXml($run);

        // Create filename
        $filename = sprintf(
            'payroll_%s_%s_%s_bank_payment.xml',
            $run->company_id,
            $run->period_year,
            str_pad($run->period_month, 2, '0', STR_PAD_LEFT)
        );

        // Save to storage
        $path = 'payroll/bank-files/' . $filename;
        \Storage::disk('local')->put($path, $xmlContent);

        Log::info('Generated bank payment file', [
            'payroll_run_id' => $run->id,
            'path' => $path,
        ]);

        return $path;
    }

    /**
     * Generate CSV export (fallback format)
     *
     * @param mixed $run PayrollRun model instance
     * @return string CSV content
     */
    public function generateCsv($run): string
    {
        $run->load(['lines.employee', 'company']);

        $csv = [];
        $csv[] = [
            'Employee ID',
            'Employee Name',
            'EMBG',
            'IBAN',
            'Gross Salary',
            'Net Salary',
            'Income Tax',
            'Pension Employee',
            'Health Employee',
            'Unemployment',
            'Additional Contribution',
            'Total Deductions',
        ];

        foreach ($run->lines as $line) {
            $employee = $line->employee;
            $csv[] = [
                $employee->id,
                "{$employee->first_name} {$employee->last_name}",
                $employee->embg,
                $employee->bank_account_iban,
                number_format($line->gross_salary, 2, '.', ''),
                number_format($line->net_salary, 2, '.', ''),
                number_format($line->income_tax_amount, 2, '.', ''),
                number_format($line->pension_contribution_employee, 2, '.', ''),
                number_format($line->health_contribution_employee, 2, '.', ''),
                number_format($line->unemployment_contribution, 2, '.', ''),
                number_format($line->additional_contribution, 2, '.', ''),
                number_format($line->total_deductions, 2, '.', ''),
            ];
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        Log::info('Generated CSV for payroll run', [
            'payroll_run_id' => $run->id,
            'employee_count' => $run->lines->count(),
        ]);

        return $csvContent;
    }

    /**
     * Validate IBANs for all employees in the payroll run
     *
     * @param mixed $run PayrollRun model instance
     * @return array Array of employees with invalid IBANs (empty if all valid)
     */
    public function validateIbans($run): array
    {
        $run->load('lines.employee');
        $invalidEmployees = [];

        foreach ($run->lines as $line) {
            $employee = $line->employee;
            if (! $this->isValidIban($employee->bank_account_iban ?? '')) {
                $invalidEmployees[] = [
                    'id' => $employee->id,
                    'name' => "{$employee->first_name} {$employee->last_name}",
                    'iban' => $employee->bank_account_iban ?? 'MISSING',
                ];
            }
        }

        return $invalidEmployees;
    }

    /**
     * Get payment summary for a payroll run
     *
     * @param mixed $run PayrollRun model instance
     * @return array Payment summary
     */
    public function getPaymentSummary($run): array
    {
        $run->load(['lines.employee', 'company']);

        return [
            'payroll_run_id' => $run->id,
            'payment_date' => $run->period_end,
            'company' => [
                'name' => $run->company->name,
                'iban' => $run->company->iban ?? '',
            ],
            'totals' => [
                'employee_count' => $run->lines->count(),
                'total_gross' => $run->total_gross,
                'total_net' => $run->total_net,
                'total_employer_tax' => $run->total_employer_tax,
            ],
            'employees' => $run->lines->map(function ($line) {
                return [
                    'id' => $line->employee->id,
                    'name' => "{$line->employee->first_name} {$line->employee->last_name}",
                    'iban' => $line->employee->bank_account_iban ?? '',
                    'net_salary' => $line->net_salary,
                ];
            })->toArray(),
        ];
    }

    /**
     * Generate unique message ID for SEPA XML
     */
    private function generateMessageId($run): string
    {
        $timestamp = Carbon::now()->format('YmdHis');

        return "PAYROLL-{$run->company_id}-{$run->id}-{$timestamp}";
    }

    /**
     * Validate IBAN format
     *
     * Basic validation - checks if IBAN has correct format.
     * Macedonian IBANs: MK07 followed by 15 digits
     */
    private function isValidIban(string $iban): bool
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));

        // Must start with 2 letters (country code)
        if (! preg_match('/^[A-Z]{2}/', $iban)) {
            return false;
        }

        // For Macedonian IBANs, must be MK followed by 17 characters total
        if (str_starts_with($iban, 'MK')) {
            return strlen($iban) === 19;
        }

        // For other countries, basic length check (15-34 characters)
        return strlen($iban) >= 15 && strlen($iban) <= 34;
    }

    /**
     * Format IBAN (remove spaces, uppercase)
     */
    private function formatIban(string $iban): string
    {
        return strtoupper(str_replace(' ', '', $iban));
    }

    /**
     * Extract BIC from Macedonian IBAN
     *
     * Macedonian IBANs: MK07 + 3-digit bank code + 10-digit account + 2 check digits
     * Common Macedonian bank BICs:
     * - 210 = Komercijalna Banka (KOBSMK2X)
     * - 250 = NLB Banka (TUTBMK22)
     * - 270 = Halk Banka (HABORSMK)
     * - 300 = Stopanska Banka (STOBMK2X)
     * - 380 = UniBank (UNIBMK22)
     * - 500 = Sparkasse Bank (OABORSMK)
     * - 530 = ProCredit Bank (PCBCMK22)
     */
    private function extractBicFromIban(string $iban): string
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        // Check if it's a Macedonian IBAN
        if (! str_starts_with($iban, 'MK') || strlen($iban) < 7) {
            return 'NOTPROVIDED';
        }

        // Extract bank code (positions 5-7 after MK + 2 check digits)
        $bankCode = substr($iban, 4, 3);

        // Map bank codes to BICs
        $bankBics = [
            '210' => 'KOBSMK2X',  // Komercijalna Banka
            '250' => 'TUTBMK22',  // NLB Banka
            '270' => 'HABORSMK',  // Halk Banka
            '300' => 'STOBMK2X',  // Stopanska Banka
            '380' => 'UNIBMK22',  // UniBank
            '500' => 'OABORSMK',  // Sparkasse Bank
            '530' => 'PCBCMK22',  // ProCredit Bank
            '290' => 'STMKMK22',  // Silk Road Bank
            '320' => 'TTBBMK2X',  // TTK Banka
        ];

        return $bankBics[$bankCode] ?? 'NOTPROVIDED';
    }

    /**
     * Sanitize text for XML output
     */
    private function sanitizeForXml(string $text): string
    {
        // Remove invalid XML characters
        $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);

        // Trim to max 70 characters (SEPA limit)
        return substr($text, 0, 70);
    }
}

// LLM-CHECKPOINT
