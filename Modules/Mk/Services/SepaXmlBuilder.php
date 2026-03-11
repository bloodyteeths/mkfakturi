<?php

namespace Modules\Mk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\PaymentBatch;

/**
 * SEPA XML Builder
 *
 * Generates SEPA Credit Transfer (pain.001.001.03) XML files for payment orders.
 * Extracted from BankPaymentFileService to be reusable across payroll and AP payments.
 *
 * XML structure:
 * - Document > CstmrCdtTrfInitn
 *   - GrpHdr: MsgId, CreDtTm, NbOfTxs, CtrlSum, InitgPty
 *   - PmtInf: PmtInfId, PmtMtd=TRF, BtchBookg, ReqdExctnDt, Dbtr, DbtrAcct, DbtrAgt, ChrgBr
 *     - CdtTrfTxInf per item: EndToEndId, Amt, CdtrAgt, Cdtr, CdtrAcct, RmtInf
 */
class SepaXmlBuilder
{
    /**
     * Macedonian bank BIC mapping.
     * Bank code (IBAN positions 5-7) -> BIC (SWIFT code).
     */
    private const MK_BANK_BICS = [
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

    /**
     * Build SEPA pain.001.001.03 XML from a PaymentBatch.
     *
     * @param PaymentBatch $batch
     * @return string XML content
     */
    public function build(PaymentBatch $batch): string
    {
        $batch->load(['items', 'company', 'bankAccount']);

        $company = $batch->company;
        $bankAccount = $batch->bankAccount;
        $messageId = $this->generateMessageId($batch);
        $paymentDate = $batch->batch_date->format('Y-m-d');
        $creationDateTime = Carbon::now()->format('Y-m-d\TH:i:s');

        $items = $batch->items;
        $numberOfTransactions = $items->count();
        // CtrlSum in SEPA is decimal with 2 places (amount is in cents)
        $controlSum = $batch->total_amount / 100;

        // Build XML
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Root: Document
        $document = $xml->createElement('Document');
        $document->setAttribute('xmlns', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03');
        $document->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $xml->appendChild($document);

        // CstmrCdtTrfInitn
        $cstmrCdtTrfInitn = $xml->createElement('CstmrCdtTrfInitn');
        $document->appendChild($cstmrCdtTrfInitn);

        // ----- Group Header -----
        $grpHdr = $xml->createElement('GrpHdr');
        $cstmrCdtTrfInitn->appendChild($grpHdr);

        $grpHdr->appendChild($xml->createElement('MsgId', $messageId));
        $grpHdr->appendChild($xml->createElement('CreDtTm', $creationDateTime));
        $grpHdr->appendChild($xml->createElement('NbOfTxs', $numberOfTransactions));
        $grpHdr->appendChild($xml->createElement('CtrlSum', number_format($controlSum, 2, '.', '')));

        // Initiating Party
        $initgPty = $xml->createElement('InitgPty');
        $grpHdr->appendChild($initgPty);
        $initgPty->appendChild($xml->createElement('Nm', $this->sanitizeForXml($company->name ?? 'Company')));

        // ----- Payment Information -----
        $pmtInf = $xml->createElement('PmtInf');
        $cstmrCdtTrfInitn->appendChild($pmtInf);

        $pmtInfId = "PO-{$batch->id}";
        $pmtInf->appendChild($xml->createElement('PmtInfId', $pmtInfId));
        $pmtInf->appendChild($xml->createElement('PmtMtd', 'TRF'));
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
        $dbtr->appendChild($xml->createElement('Nm', $this->sanitizeForXml($company->name ?? 'Company')));

        // Debtor Account
        $dbtrAcct = $xml->createElement('DbtrAcct');
        $pmtInf->appendChild($dbtrAcct);
        $dbtrId = $xml->createElement('Id');
        $dbtrAcct->appendChild($dbtrId);
        $debtorIban = $this->formatIban($bankAccount->iban ?? ($company->iban ?? ''));
        $dbtrId->appendChild($xml->createElement('IBAN', $debtorIban));

        // Debtor Agent (Bank)
        $dbtrAgt = $xml->createElement('DbtrAgt');
        $pmtInf->appendChild($dbtrAgt);
        $finInstnId = $xml->createElement('FinInstnId');
        $dbtrAgt->appendChild($finInstnId);
        $debtorBic = $bankAccount->swift_code ?? ($company->bic ?? $this->extractBicFromIban($debtorIban));
        $finInstnId->appendChild($xml->createElement('BIC', $debtorBic));

        // Charge Bearer
        $pmtInf->appendChild($xml->createElement('ChrgBr', 'SLEV'));

        // ----- Credit Transfer Transactions -----
        foreach ($items as $item) {
            $cdtTrfTxInf = $xml->createElement('CdtTrfTxInf');
            $pmtInf->appendChild($cdtTrfTxInf);

            // Payment ID
            $pmtId = $xml->createElement('PmtId');
            $cdtTrfTxInf->appendChild($pmtId);
            $endToEndId = "PO-{$batch->id}-ITEM-{$item->id}";
            $pmtId->appendChild($xml->createElement('EndToEndId', $endToEndId));

            // Amount
            $amt = $xml->createElement('Amt');
            $cdtTrfTxInf->appendChild($amt);
            $instdAmt = $xml->createElement('InstdAmt', number_format($item->amount / 100, 2, '.', ''));
            $instdAmt->setAttribute('Ccy', $item->currency_code ?? 'MKD');
            $amt->appendChild($instdAmt);

            // Creditor Agent (Bank)
            $cdtrAgt = $xml->createElement('CdtrAgt');
            $cdtTrfTxInf->appendChild($cdtrAgt);
            $cdtrFinInstnId = $xml->createElement('FinInstnId');
            $cdtrAgt->appendChild($cdtrFinInstnId);
            $creditorBic = $item->creditor_bic ?? $this->extractBicFromIban($item->creditor_iban ?? '');
            $cdtrFinInstnId->appendChild($xml->createElement('BIC', $creditorBic));

            // Creditor
            $cdtr = $xml->createElement('Cdtr');
            $cdtTrfTxInf->appendChild($cdtr);
            $cdtr->appendChild($xml->createElement('Nm', $this->sanitizeForXml($item->creditor_name)));

            // Creditor Account
            $cdtrAcct = $xml->createElement('CdtrAcct');
            $cdtTrfTxInf->appendChild($cdtrAcct);
            $cdtrId = $xml->createElement('Id');
            $cdtrAcct->appendChild($cdtrId);
            $cdtrId->appendChild($xml->createElement('IBAN', $this->formatIban($item->creditor_iban ?? '')));

            // Remittance Information
            $rmtInf = $xml->createElement('RmtInf');
            $cdtTrfTxInf->appendChild($rmtInf);
            $description = $item->description ?? "Payment {$batch->batch_number}";
            $rmtInf->appendChild($xml->createElement('Ustrd', $this->sanitizeForXml($description)));
        }

        $xmlContent = $xml->saveXML();

        Log::info('Generated SEPA XML for payment batch', [
            'batch_id' => $batch->id,
            'message_id' => $messageId,
            'transactions' => $numberOfTransactions,
            'total_amount' => $controlSum,
        ]);

        return $xmlContent;
    }

    /**
     * Generate unique message ID for SEPA XML.
     */
    private function generateMessageId(PaymentBatch $batch): string
    {
        $timestamp = Carbon::now()->format('YmdHis');

        return "PO-{$batch->company_id}-{$batch->id}-{$timestamp}";
    }

    /**
     * Extract BIC from Macedonian IBAN.
     */
    private function extractBicFromIban(string $iban): string
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        if (! str_starts_with($iban, 'MK') || strlen($iban) < 7) {
            return 'NOTPROVIDED';
        }

        $bankCode = substr($iban, 4, 3);

        return self::MK_BANK_BICS[$bankCode] ?? 'NOTPROVIDED';
    }

    /**
     * Format IBAN: remove spaces, uppercase.
     */
    private function formatIban(string $iban): string
    {
        return strtoupper(str_replace(' ', '', $iban));
    }

    /**
     * Sanitize text for XML output.
     * Preserves Cyrillic and Latin characters, removes only truly invalid XML chars.
     * Trims to SEPA 70-char limit.
     */
    private function sanitizeForXml(string $text): string
    {
        // Remove only invalid XML characters (preserves Cyrillic U+0400-U+04FF and all valid Unicode)
        $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);

        // Escape XML special characters (&, <, >, ", ')
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return mb_substr($text, 0, 70, 'UTF-8');
    }
}

// CLAUDE-CHECKPOINT
