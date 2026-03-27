<?php

namespace App\Services\Tax;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\CreditNote;
use App\Models\Invoice;

/**
 * Invoice Register Service — Книга на влезни/излезни фактури
 *
 * Generates UJP-compliant invoice register data with per-rate VAT breakdown.
 * Used by both company-facing and partner-facing controllers.
 */
class InvoiceRegisterService
{
    /** Standard MK VAT rates */
    private const RATES = [18, 10, 5, 0];

    /**
     * Get output register (Книга на излезни фактури) — sales invoices + credit notes.
     */
    public function getOutputRegister(int $companyId, string $fromDate, string $toDate): array
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['customer', 'taxes.taxType', 'items.taxes.taxType'])
            ->where('invoice_date', '>=', $fromDate)
            ->where('invoice_date', '<=', $toDate)
            ->orderBy('invoice_date')
            ->get();

        $entries = $invoices->map(function ($invoice) {
            return $this->buildOutputEntry($invoice, 'invoice');
        })->values();

        // Credit notes (negative entries)
        $creditNotes = CreditNote::where('company_id', $companyId)
            ->whereNotIn('status', ['DRAFT'])
            ->with(['customer', 'taxes.taxType', 'items.taxes.taxType'])
            ->where('credit_note_date', '>=', $fromDate)
            ->where('credit_note_date', '<=', $toDate)
            ->orderBy('credit_note_date')
            ->get();

        $cnEntries = $creditNotes->map(function ($cn) {
            return $this->buildOutputEntry($cn, 'credit_note');
        })->values();

        return $entries->concat($cnEntries)->sortBy('date')->values()->toArray();
    }

    /**
     * Get input register (Книга на влезни фактури) — purchase bills.
     */
    public function getInputRegister(int $companyId, string $fromDate, string $toDate): array
    {
        $bills = Bill::where('company_id', $companyId)
            ->whereNotIn('status', ['DRAFT'])
            ->without(['company', 'creator'])
            ->with(['supplier', 'taxes.taxType', 'items.taxes.taxType', 'payments'])
            ->where('bill_date', '>=', $fromDate)
            ->where('bill_date', '<=', $toDate)
            ->orderBy('bill_date')
            ->get();

        return $bills->map(function ($bill) {
            $byRate = $this->breakdownByRate($bill);
            $lastPayment = $bill->payments->sortByDesc('payment_date')->first();

            return [
                'id' => $bill->id,
                'doc_type' => 'bill',
                'date' => $this->formatDate($bill->bill_date),
                'number' => $bill->bill_number ?? '',
                'party_name' => $bill->supplier?->name ?? '',
                'party_address' => trim(($bill->supplier?->address_street_1 ?? '') . ' ' . ($bill->supplier?->city ?? '')),
                'party_tax_id' => $bill->supplier?->vat_number ?? $bill->supplier?->tax_id ?? '',
                'by_rate' => $byRate,
                'total' => (int) ($bill->total ?? 0),
                'is_reverse_charge' => (bool) ($bill->is_reverse_charge ?? false),
                'payment_date' => $lastPayment ? $this->formatDate($lastPayment->payment_date) : '',
                'deduction_eligible' => $this->isDeductionEligible($bill),
            ];
        })->values()->toArray();
    }

    /**
     * Build an output register entry from an invoice or credit note.
     */
    private function buildOutputEntry($document, string $docType): array
    {
        $isCreditNote = $docType === 'credit_note';
        $byRate = $this->breakdownByRate($document);

        if ($isCreditNote) {
            // Negate all amounts for credit notes
            foreach ($byRate as &$r) {
                $r['base'] = -abs($r['base']);
                $r['vat'] = -abs($r['vat']);
            }
        }

        $dateField = $isCreditNote ? 'credit_note_date' : 'invoice_date';
        $numberField = $isCreditNote ? 'credit_note_number' : 'invoice_number';

        return [
            'id' => ($isCreditNote ? 'cn_' : '') . $document->id,
            'doc_type' => $docType,
            'date' => $this->formatDate($document->$dateField),
            'number' => $document->$numberField ?? '',
            'party_name' => $document->customer?->name ?? '',
            'party_address' => trim(($document->customer?->address_street_1 ?? '') . ' ' . ($document->customer?->city ?? '')),
            'party_tax_id' => $document->customer?->vat_number ?? $document->customer?->tax_id ?? '',
            'by_rate' => $byRate,
            'total' => $isCreditNote ? -abs((int) ($document->total ?? 0)) : (int) ($document->total ?? 0),
            'is_reverse_charge' => (bool) ($document->is_reverse_charge ?? false),
        ];
    }

    /**
     * Break down a document's tax amounts by VAT rate.
     * Returns: [18 => ['base' => X, 'vat' => Y], 10 => ..., 5 => ..., 0 => ...]
     */
    private function breakdownByRate($document): array
    {
        $result = [];
        foreach (self::RATES as $rate) {
            $result[$rate] = ['base' => 0, 'vat' => 0];
        }

        $taxes = collect();

        // Collect document-level taxes
        if ($document->relationLoaded('taxes') && $document->taxes->isNotEmpty()) {
            $taxes = $taxes->concat($document->taxes);
        }

        // Collect item-level taxes
        if ($document->relationLoaded('items')) {
            foreach ($document->items as $item) {
                if ($item->relationLoaded('taxes') && $item->taxes->isNotEmpty()) {
                    $taxes = $taxes->concat($item->taxes);
                }
            }
        }

        if ($taxes->isNotEmpty()) {
            foreach ($taxes as $tax) {
                $percent = (float) ($tax->percent ?? $tax->taxType?->percent ?? 0);
                $rateKey = $this->snapToRate($percent);
                $result[$rateKey]['vat'] += (int) ($tax->amount ?? 0);
            }

            // Calculate base from total and VAT
            $totalVat = array_sum(array_column($result, 'vat'));
            $subTotal = (int) ($document->sub_total ?? ($document->total - $totalVat));

            // Distribute base proportionally by VAT amount
            if ($totalVat > 0) {
                foreach ($result as $rate => &$data) {
                    if ($data['vat'] > 0) {
                        $expectedBase = round($data['vat'] / ($rate / 100));
                        $data['base'] = (int) $expectedBase;
                    }
                }
            } else {
                // All zero-rated or exempt
                $result[0]['base'] = $subTotal;
            }
        } else {
            // No tax relations — compute from totals
            $tax = (int) ($document->tax ?? 0);
            $subTotal = (int) ($document->sub_total ?? ($document->total - $tax));

            if ($tax > 0 && $subTotal > 0) {
                $computedRate = ($tax / $subTotal) * 100;
                $rateKey = $this->snapToRate($computedRate);
                $result[$rateKey]['base'] = $subTotal;
                $result[$rateKey]['vat'] = $tax;
            } else {
                $result[0]['base'] = $subTotal;
            }
        }

        return $result;
    }

    /**
     * Snap a computed rate to the nearest standard MK rate.
     */
    private function snapToRate(float $rate): int
    {
        if ($rate >= 16 && $rate <= 20) return 18;
        if ($rate >= 8 && $rate <= 12) return 10;
        if ($rate >= 4 && $rate <= 6) return 5;
        return 0;
    }

    /**
     * Determine if input VAT is eligible for deduction.
     */
    private function isDeductionEligible($bill): bool
    {
        // Exempt and non-taxable purchases are not deductible
        if ($bill->is_reverse_charge ?? false) {
            return true; // RC bills have deduction right per Art. 32-а
        }

        $tax = (int) ($bill->tax ?? 0);
        return $tax > 0;
    }

    /**
     * Summarize register entries by rate.
     */
    public function summarizeByRate(array $entries): array
    {
        $summary = [];
        foreach (self::RATES as $rate) {
            $summary[$rate] = ['rate' => $rate, 'count' => 0, 'base' => 0, 'vat' => 0, 'total' => 0];
        }

        foreach ($entries as $entry) {
            foreach ($entry['by_rate'] ?? [] as $rate => $data) {
                $summary[$rate]['base'] += $data['base'];
                $summary[$rate]['vat'] += $data['vat'];
            }
            // Count documents
            $dominantRate = $this->getDominantRate($entry['by_rate'] ?? []);
            $summary[$dominantRate]['count']++;
            $summary[$dominantRate]['total'] += $entry['total'];
        }

        // Sort descending by rate, remove empty
        return collect($summary)
            ->sortByDesc('rate')
            ->values()
            ->toArray();
    }

    private function getDominantRate(array $byRate): int
    {
        $maxVat = 0;
        $dominant = 0;
        foreach ($byRate as $rate => $data) {
            if (abs($data['vat']) > $maxVat || (abs($data['base']) > 0 && $maxVat === 0)) {
                $maxVat = abs($data['vat']);
                $dominant = $rate;
            }
        }
        return $dominant;
    }

    private function formatDate($date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d');
        }
        return substr((string) ($date ?? ''), 0, 10);
    }

    /**
     * Get available rates constant.
     */
    public static function rates(): array
    {
        return self::RATES;
    }
}
