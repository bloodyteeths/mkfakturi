<?php

namespace App\Services\Onboarding;

class BankDataAnalyzer
{
    /**
     * Analyze bank transactions to extract unique counterparties
     * and classify them as customers or suppliers.
     *
     * @param  array  $transactions  Array of transaction records with keys:
     *   counterparty_name, amount, description
     * @return array{suggested_suppliers: array, suggested_customers: array}
     */
    public function analyzeTransactions(array $transactions): array
    {
        $counterparties = [];

        foreach ($transactions as $tx) {
            $name = trim($tx['counterparty_name'] ?? '');
            if (empty($name)) {
                // Try to extract from description
                $name = $this->extractNameFromDescription($tx['description'] ?? '');
            }
            if (empty($name)) {
                continue;
            }

            $normalizedName = $this->normalizeName($name);
            if (empty($normalizedName)) {
                continue;
            }

            if (!isset($counterparties[$normalizedName])) {
                $counterparties[$normalizedName] = [
                    'name' => $name, // Keep original casing from first occurrence
                    'debit_count' => 0,
                    'credit_count' => 0,
                    'total_debit' => 0,
                    'total_credit' => 0,
                ];
            }

            $amount = (float) ($tx['amount'] ?? 0);

            if ($amount < 0) {
                // Money out = payment to supplier
                $counterparties[$normalizedName]['debit_count']++;
                $counterparties[$normalizedName]['total_debit'] += abs($amount);
            } else {
                // Money in = receipt from customer
                $counterparties[$normalizedName]['credit_count']++;
                $counterparties[$normalizedName]['total_credit'] += $amount;
            }
        }

        $suppliers = [];
        $customers = [];

        foreach ($counterparties as $key => $data) {
            $totalTx = $data['debit_count'] + $data['credit_count'];

            // Skip if only 1 transaction (likely noise)
            if ($totalTx < 1) {
                continue;
            }

            // Skip common non-entity names
            if ($this->isExcludedName($data['name'])) {
                continue;
            }

            $entry = [
                'name' => $data['name'],
                'transaction_count' => $totalTx,
                'total_amount' => round($data['total_debit'] + $data['total_credit'], 2),
            ];

            // Classify by dominant direction
            if ($data['debit_count'] > $data['credit_count']) {
                $entry['total_amount'] = round($data['total_debit'], 2);
                $suppliers[] = $entry;
            } else {
                $entry['total_amount'] = round($data['total_credit'], 2);
                $customers[] = $entry;
            }
        }

        // Sort by transaction count descending
        usort($suppliers, fn ($a, $b) => $b['transaction_count'] <=> $a['transaction_count']);
        usort($customers, fn ($a, $b) => $b['transaction_count'] <=> $a['transaction_count']);

        return [
            'suggested_suppliers' => $suppliers,
            'suggested_customers' => $customers,
        ];
    }

    /**
     * Normalize a counterparty name for deduplication.
     */
    protected function normalizeName(string $name): string
    {
        // Lowercase, trim, collapse whitespace
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/u', ' ', $name);

        // Remove common suffixes (DOOEL, DOO, AD, etc.)
        $suffixes = ['дооел', 'доо', 'ад', 'тп', 'dooel', 'doo', 'ad', 'tp', 'ltd', 'llc', 'gmbh'];
        foreach ($suffixes as $suffix) {
            $name = preg_replace('/\s+' . preg_quote($suffix, '/') . '$/u', '', $name);
        }

        return trim($name);
    }

    /**
     * Try to extract a counterparty name from transaction description.
     */
    protected function extractNameFromDescription(string $description): string
    {
        // Common patterns in MK bank descriptions:
        // "Уплата од КОМПАНИЈА ДООЕЛ" or "Исплата на КОМПАНИЈА ДООЕЛ"
        if (preg_match('/(?:уплата|исплата|трансфер|дознака)\s+(?:од|на|кон)\s+(.+)/iu', $description, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    /**
     * Check if a name should be excluded (banks, tax authority, etc.)
     */
    protected function isExcludedName(string $name): bool
    {
        $excluded = [
            'ујп', 'ujp', 'управа за јавни приходи',
            'фонд за здравство', 'фонд за здравствено', 'фонд за пензиско',
            'аврм', 'агенција за вработување',
            'нбрм', 'народна банка',
            'провизија', 'камата', 'такса',
            'commission', 'fee', 'interest',
        ];

        $lower = mb_strtolower($name);
        foreach ($excluded as $term) {
            if (str_contains($lower, $term)) {
                return true;
            }
        }

        return false;
    }
}
// CLAUDE-CHECKPOINT
