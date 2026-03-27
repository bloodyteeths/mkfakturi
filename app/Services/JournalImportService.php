<?php

namespace App\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use IFRS\Models\Account as IfrsAccount;
use IFRS\Models\Transaction;

/**
 * Journal Import Service
 *
 * Parses journal entries from Pantheon .txt (cp1251) and CSV formats,
 * validates them, and imports via IfrsAdapter::postJournalEntry().
 */
class JournalImportService
{
    /**
     * Macedonian chart of accounts — first digit → IFRS account type + app account type.
     */
    protected const ACCOUNT_TYPE_MAP = [
        0 => ['ifrs' => IfrsAccount::NON_CURRENT_ASSET, 'app' => Account::TYPE_ASSET],
        1 => ['ifrs' => IfrsAccount::CURRENT_ASSET, 'app' => Account::TYPE_ASSET],
        2 => ['ifrs' => IfrsAccount::CURRENT_LIABILITY, 'app' => Account::TYPE_LIABILITY],
        3 => ['ifrs' => IfrsAccount::INVENTORY, 'app' => Account::TYPE_ASSET],  // Залихи на суровини, материјали, ситен инвентар
        4 => ['ifrs' => IfrsAccount::OPERATING_EXPENSE, 'app' => Account::TYPE_EXPENSE],
        5 => ['ifrs' => IfrsAccount::DIRECT_EXPENSE, 'app' => Account::TYPE_EXPENSE],
        6 => ['ifrs' => IfrsAccount::INVENTORY, 'app' => Account::TYPE_ASSET],  // Залихи на производство, готови производи, стоки
        7 => ['ifrs' => IfrsAccount::OPERATING_REVENUE, 'app' => Account::TYPE_REVENUE],
        8 => ['ifrs' => IfrsAccount::OPERATING_REVENUE, 'app' => Account::TYPE_REVENUE],
        9 => ['ifrs' => IfrsAccount::EQUITY, 'app' => Account::TYPE_EQUITY],
    ];

    /**
     * More specific IFRS type mapping based on account code prefix.
     */
    protected const SPECIFIC_IFRS_TYPES = [
        '0192' => IfrsAccount::CONTRA_ASSET,  // Исправка на вредност (depreciation)
        '019'  => IfrsAccount::CONTRA_ASSET,
        '100'  => IfrsAccount::BANK,           // Жиро-сметка (Bank account)
        '120'  => IfrsAccount::RECEIVABLE,     // Побарувања од купувачи
        '130'  => IfrsAccount::RECEIVABLE,     // ДДВ претходен данок
        '162'  => IfrsAccount::RECEIVABLE,     // Дадени заеми
        '220'  => IfrsAccount::PAYABLE,        // Обврски кон добавувачи
        '230'  => IfrsAccount::CURRENT_LIABILITY, // Краткорочни заеми
        '234'  => IfrsAccount::CURRENT_LIABILITY, // ПИО, здравствено
        '240'  => IfrsAccount::CURRENT_LIABILITY, // Обврски за плати
        '242'  => IfrsAccount::CURRENT_LIABILITY, // Обврски за придонеси
        '351'  => IfrsAccount::INVENTORY,  // Ситен инвентар во употреба
    ];

    /**
     * Firms map: firma_id => firma_name for counterparty resolution.
     */
    protected array $firmsMap = [];

    /**
     * Set the firms map for resolving counterparty names during import.
     */
    public function setFirmsMap(array $firmsMap): self
    {
        $this->firmsMap = $firmsMap;
        return $this;
    }

    /**
     * Nalog type names (Macedonian).
     */
    protected const NALOG_TYPES = [
        '00' => 'Почетно салдо',
        '10' => 'Тековна сметка - Банка',
        '11' => 'Каса / Благајна',
        '20' => 'Влезни фактури',
        '21' => 'Излезни фактури - Примена',
        '30' => 'Излезни фактури - Продажба',
        '40' => 'Плати',
    ];

    /**
     * Parse an uploaded file (Pantheon .txt or CSV).
     *
     * @return array ['nalozi' => [...], 'accounts' => [...], 'firms' => [...], 'format' => string]
     */
    public function parseFile(string $content, string $filename): array
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        // Detect and convert encoding
        $content = $this->normalizeEncoding($content);

        if ($ext === 'txt') {
            return $this->parsePantheonFormat($content);
        }

        return $this->parseCsvFormat($content);
    }

    /**
     * Detect encoding (cp1251/utf-8/iso-8859-5) and convert to UTF-8.
     */
    protected function normalizeEncoding(string $content): string
    {
        if (mb_check_encoding($content, 'UTF-8') && preg_match('/[\x{0400}-\x{04FF}]/u', $content)) {
            return $content; // Already valid UTF-8 with Cyrillic
        }

        // Try cp1251 first (most common for Pantheon exports)
        $converted = mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $converted)) {
            return $converted;
        }

        // Try ISO-8859-5
        $converted = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-5');
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $converted)) {
            return $converted;
        }

        // Fallback: force cp1251
        return mb_convert_encoding($content, 'UTF-8', 'Windows-1251');
    }

    /**
     * Parse Pantheon .txt format.
     *
     * Format: key:type:value pairs, grouped by nalog.
     * nalog:s:00-0001 / konto:s:01203 / dolguva:f:694158 / pobaruva:f:0
     */
    protected function parsePantheonFormat(string $content): array
    {
        $lines = preg_split('/\r?\n/', $content);
        $nalozi = [];
        $accounts = [];
        $firms = [];
        $parseWarnings = [];
        $currentNalog = null;
        $currentItems = [];
        $currentItem = [];

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // Parse key:type:value
            if (!preg_match('/^([a-z_]+):([a-z]):(.*)$/i', $line, $m)) {
                continue;
            }

            $key = strtolower($m[1]);
            $type = strtolower($m[2]);
            $value = $m[3];

            // Cast based on type
            if ($type === 'f') {
                $value = (float) $value;
            } elseif ($type === 'i') {
                $value = (int) $value;
            }

            if ($key === 'nalog') {
                // Save previous nalog if exists
                if ($currentNalog !== null) {
                    if (!empty($currentItem)) {
                        $currentItems[] = $this->finalizeItem($currentItem);
                    }
                    $nalozi[] = $this->buildNalog($currentNalog, $currentItems, $this->firmsMap);
                }
                $currentNalog = ['id' => $value];
                $currentItems = [];
                $currentItem = [];
            } elseif ($key === 'data_kn') {
                if ($currentNalog) {
                    $currentNalog['date'] = $this->parseDate($value);
                }
            } elseif ($key === 'konto') {
                // New line item — save previous if exists
                if (!empty($currentItem) && isset($currentItem['konto'])) {
                    $currentItems[] = $this->finalizeItem($currentItem);
                }
                // Warn about empty konto
                if (trim((string) $value) === '') {
                    $nalogId = $currentNalog['id'] ?? '?';
                    $parseWarnings[] = [
                        'type' => 'empty_konto',
                        'nalog' => $nalogId,
                        'line' => $lineNum + 1,
                        'message' => "Налог {$nalogId}: празно конто на линија " . ($lineNum + 1),
                    ];
                }
                $currentItem = ['konto' => $value];
            } elseif ($key === 'data') {
                $currentItem['date'] = $this->parseDate($value);
            } elseif ($key === 'firma') {
                $currentItem['firma'] = (int) $value;
            } elseif ($key === 'opis') {
                $currentItem['opis'] = $value;
            } elseif ($key === 'dolguva') {
                $currentItem['dolguva'] = $value;
            } elseif ($key === 'pobaruva') {
                $currentItem['pobaruva'] = $value;
            } elseif ($key === 'vvrska') {
                $currentItem['vvrska'] = $value;
            }

            // Track unique accounts (skip empty codes)
            if ($key === 'konto' && trim((string) $value) !== '' && !isset($accounts[$value])) {
                $accounts[$value] = $this->guessAccountName($value);
            }

            // Track firms
            if ($key === 'firma' && (int) $value > 0) {
                $firms[(int) $value] = true;
            }
        }

        // Save last nalog
        if ($currentNalog !== null) {
            if (!empty($currentItem)) {
                $currentItems[] = $this->finalizeItem($currentItem);
            }
            $nalozi[] = $this->buildNalog($currentNalog, $currentItems, $this->firmsMap);
        }

        return [
            'nalozi' => $nalozi,
            'accounts' => $accounts,
            'firms' => array_keys($firms),
            'parse_warnings' => $parseWarnings,
            'format' => 'pantheon_txt',
        ];
    }

    /**
     * Parse CSV format.
     *
     * Expected columns: nalog_id, date, account_code, account_name, description, debit, credit, reference
     */
    protected function parseCsvFormat(string $content): array
    {
        $lines = preg_split('/\r?\n/', $content);
        if (count($lines) < 2) {
            return ['nalozi' => [], 'accounts' => [], 'firms' => [], 'format' => 'csv'];
        }

        // Detect delimiter
        $delimiter = str_contains($lines[0], ';') ? ';' : ',';

        $header = str_getcsv(array_shift($lines), $delimiter);
        $header = array_map(fn($h) => strtolower(trim($h)), $header);

        $nalogGroups = [];
        $accounts = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $row = str_getcsv($line, $delimiter);
            $data = [];
            foreach ($header as $i => $col) {
                $data[$col] = $row[$i] ?? '';
            }

            $nalogId = $data['nalog_id'] ?? $data['nalog'] ?? $data['id'] ?? 'unknown';
            $accountCode = $data['account_code'] ?? $data['konto'] ?? $data['code'] ?? '';
            $accountName = $data['account_name'] ?? $data['name'] ?? $data['opis'] ?? '';
            $description = $data['description'] ?? $data['opis'] ?? '';
            $debit = (float) str_replace([',', ' '], ['.', ''], $data['debit'] ?? $data['dolguva'] ?? '0');
            $credit = (float) str_replace([',', ' '], ['.', ''], $data['credit'] ?? $data['pobaruva'] ?? '0');
            $date = $data['date'] ?? $data['datum'] ?? $data['data'] ?? '';
            $reference = $data['reference'] ?? $data['vvrska'] ?? '';

            if (!isset($nalogGroups[$nalogId])) {
                $nalogGroups[$nalogId] = [
                    'id' => $nalogId,
                    'date' => $this->parseDate($date),
                    'items' => [],
                ];
            }

            $nalogGroups[$nalogId]['items'][] = [
                'account_code' => $accountCode,
                'account_name' => $accountName ?: $this->guessAccountName($accountCode),
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
                'reference' => $reference,
            ];

            if ($accountCode && !isset($accounts[$accountCode])) {
                $accounts[$accountCode] = $accountName ?: $this->guessAccountName($accountCode);
            }
        }

        // Convert groups to nalozi format
        $nalozi = [];
        foreach ($nalogGroups as $group) {
            $lineItems = [];
            foreach ($group['items'] as $item) {
                if ($item['debit'] > 0) {
                    $lineItems[] = [
                        'account_code' => $item['account_code'],
                        'account_name' => $item['account_name'],
                        'amount' => $item['debit'],
                        'credited' => false,
                        'description' => $item['description'],
                        'reference' => $item['reference'],
                    ];
                }
                if ($item['credit'] > 0) {
                    $lineItems[] = [
                        'account_code' => $item['account_code'],
                        'account_name' => $item['account_name'],
                        'amount' => $item['credit'],
                        'credited' => true,
                        'description' => $item['description'],
                        'reference' => $item['reference'],
                    ];
                }
            }

            $typeCode = explode('-', $group['id'])[0] ?? '';
            $typeName = self::NALOG_TYPES[$typeCode] ?? 'Книжење';

            $totalDebit = array_sum(array_column(array_filter($lineItems, fn($i) => !$i['credited']), 'amount'));
            $totalCredit = array_sum(array_column(array_filter($lineItems, fn($i) => $i['credited']), 'amount'));

            $nalozi[] = [
                'nalog_id' => $group['id'],
                'date' => $group['date'],
                'type' => $typeName,
                'line_items' => $lineItems,
                'total_debit' => round($totalDebit, 2),
                'total_credit' => round($totalCredit, 2),
                'balanced' => abs($totalDebit - $totalCredit) < 0.01,
                'line_count' => count($lineItems),
            ];
        }

        return [
            'nalozi' => $nalozi,
            'accounts' => $accounts,
            'firms' => [],
            'format' => 'csv',
        ];
    }

    /**
     * Validate nalozi against a company's existing accounts.
     *
     * @return array ['valid' => bool, 'warnings' => [...], 'missing_accounts' => [...]]
     */
    public function validateNalozi(array $nalozi, int $companyId): array
    {
        $warnings = [];
        $missingAccounts = [];
        $existingCodes = Account::where('company_id', $companyId)
            ->pluck('code')
            ->toArray();

        // Collect all imported account codes for similar-code detection
        $importedCodes = [];
        foreach ($nalozi as $nalog) {
            foreach ($nalog['line_items'] as $item) {
                $code = $item['account_code'];
                if (trim($code) !== '') {
                    $importedCodes[$code] = true;
                }
            }
        }

        // Check for duplicate nalozi (already imported to this company's IFRS entity)
        $existingReferences = $this->getExistingNalogReferences($companyId);

        foreach ($nalozi as $i => $nalog) {
            if (!$nalog['balanced']) {
                $diff = abs($nalog['total_debit'] - $nalog['total_credit']);
                $warnings[] = [
                    'nalog' => $nalog['nalog_id'],
                    'type' => 'unbalanced',
                    'message' => "Налог {$nalog['nalog_id']} не е балансиран (разлика: {$diff})",
                ];
            }

            // Duplicate detection
            if (in_array($nalog['nalog_id'], $existingReferences)) {
                $warnings[] = [
                    'nalog' => $nalog['nalog_id'],
                    'type' => 'duplicate',
                    'message' => "Налог {$nalog['nalog_id']} веќе е внесен",
                ];
            }

            foreach ($nalog['line_items'] as $item) {
                $code = $item['account_code'];

                // Skip empty codes (already warned in parser)
                if (trim($code) === '') {
                    continue;
                }

                if (!in_array($code, $existingCodes) && !isset($missingAccounts[$code])) {
                    $missingAccounts[$code] = $item['account_name'];

                    // Similar code detection — warn if a close match exists
                    $similar = $this->findSimilarCodes($code, $existingCodes, array_keys($importedCodes));
                    if ($similar) {
                        $warnings[] = [
                            'nalog' => $nalog['nalog_id'],
                            'type' => 'similar_code',
                            'message' => "Конто {$code} не постои — дали мислевте на {$similar}?",
                            'account_code' => $code,
                            'similar_to' => $similar,
                        ];
                    }
                }
            }
        }

        return [
            'valid' => empty(array_filter($warnings, fn($w) => $w['type'] === 'unbalanced')),
            'warnings' => $warnings,
            'missing_accounts' => $missingAccounts,
        ];
    }

    /**
     * Get existing nalog references (transaction references) for a company's IFRS entity.
     */
    protected function getExistingNalogReferences(int $companyId): array
    {
        $entityId = DB::table('companies')
            ->where('id', $companyId)
            ->value('ifrs_entity_id');

        if (!$entityId) {
            return [];
        }

        return Transaction::where('entity_id', $entityId)
            ->where('transaction_type', Transaction::JN)
            ->whereNotNull('reference')
            ->pluck('reference')
            ->toArray();
    }

    /**
     * Find similar account codes using prefix matching and Levenshtein distance.
     */
    protected function findSimilarCodes(string $code, array $existingCodes, array $importedCodes): ?string
    {
        $allCodes = array_unique(array_merge($existingCodes, $importedCodes));
        $bestMatch = null;
        $bestDistance = PHP_INT_MAX;

        foreach ($allCodes as $candidate) {
            if ($candidate === $code || trim($candidate) === '') {
                continue;
            }

            // Check Levenshtein distance (catches typos like 740001 vs 74010)
            $distance = levenshtein($code, (string) $candidate);
            if ($distance <= 2 && $distance < $bestDistance) {
                $bestMatch = $candidate;
                $bestDistance = $distance;
            }

            // Check if one is a prefix of the other (catches truncation errors)
            if (str_starts_with($code, (string) $candidate) || str_starts_with((string) $candidate, $code)) {
                $lenDiff = abs(strlen($code) - strlen((string) $candidate));
                if ($lenDiff <= 2 && $lenDiff < $bestDistance) {
                    $bestMatch = $candidate;
                    $bestDistance = $lenDiff;
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Import nalozi into the IFRS ledger.
     *
     * @return array ['imported' => int, 'errors' => [...], 'transaction_ids' => [...]]
     */
    public function importNalozi(array $nalozi, Company $company, bool $autoCreateAccounts = true): array
    {
        $adapter = app(IfrsAdapter::class);
        $imported = 0;
        $errors = [];
        $transactionIds = [];
        $accountsCreated = 0;

        DB::beginTransaction();

        try {
            // Auto-create missing app-level accounts
            if ($autoCreateAccounts) {
                $accountsCreated = $this->createMissingAppAccounts($nalozi, $company->id);
            }

            foreach ($nalozi as $nalog) {
                // Skip unbalanced entries
                if (!$nalog['balanced']) {
                    $errors[] = [
                        'nalog' => $nalog['nalog_id'],
                        'error' => 'Не е балансиран — прескокнат',
                    ];
                    continue;
                }

                $typeCode = explode('-', $nalog['nalog_id'])[0] ?? '';
                $typeName = self::NALOG_TYPES[$typeCode] ?? 'Книжење';

                $entry = [
                    'date' => $nalog['date'],
                    'narration' => "{$typeName} - Налог {$nalog['nalog_id']}",
                    'reference' => $nalog['nalog_id'],
                    'line_items' => $nalog['line_items'],
                ];

                $txId = $adapter->postJournalEntry($company, $entry);

                if ($txId) {
                    $transactionIds[] = $txId;
                    $imported++;
                } else {
                    $errors[] = [
                        'nalog' => $nalog['nalog_id'],
                        'error' => 'Не успеа внесувањето',
                    ];
                }
            }

            DB::commit();

            return [
                'imported' => $imported,
                'errors' => $errors,
                'transaction_ids' => $transactionIds,
                'accounts_created' => $accountsCreated,
                'total_line_items' => array_sum(array_column($nalozi, 'line_count')),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Journal import failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'imported' => 0,
                'errors' => [['nalog' => 'all', 'error' => $e->getMessage()]],
                'transaction_ids' => [],
                'accounts_created' => 0,
                'total_line_items' => 0,
            ];
        }
    }

    /**
     * Create missing app-level accounts before IFRS import.
     */
    protected function createMissingAppAccounts(array $nalozi, int $companyId): int
    {
        $existingCodes = Account::where('company_id', $companyId)
            ->pluck('code')
            ->toArray();

        $created = 0;
        $seen = [];

        foreach ($nalozi as $nalog) {
            foreach ($nalog['line_items'] as $item) {
                $code = $item['account_code'];
                if (in_array($code, $existingCodes) || isset($seen[$code])) {
                    continue;
                }

                $types = $this->mapCodeToTypes($code);

                Account::create([
                    'company_id' => $companyId,
                    'code' => $code,
                    'name' => $item['account_name'],
                    'type' => $types['app'],
                    'is_active' => true,
                    'system_defined' => false,
                ]);

                $seen[$code] = true;
                $created++;
            }
        }

        return $created;
    }

    /**
     * Map a Macedonian account code to IFRS + app account types.
     */
    public function mapCodeToTypes(string $code): array
    {
        // Check specific prefix matches (longest prefix first for correct matching)
        $prefixes = self::SPECIFIC_IFRS_TYPES;
        uksort($prefixes, fn($a, $b) => strlen($b) - strlen($a));
        foreach ($prefixes as $prefix => $ifrsType) {
            if (str_starts_with($code, $prefix)) {
                $digit = (int) substr($code, 0, 1);
                $appType = self::ACCOUNT_TYPE_MAP[$digit]['app'] ?? Account::TYPE_EXPENSE;
                return ['ifrs' => $ifrsType, 'app' => $appType];
            }
        }

        // Fallback to first-digit mapping
        $digit = (int) substr($code, 0, 1);
        return self::ACCOUNT_TYPE_MAP[$digit] ?? [
            'ifrs' => IfrsAccount::OPERATING_EXPENSE,
            'app' => Account::TYPE_EXPENSE,
        ];
    }

    /**
     * Get supported import formats.
     */
    public function getSupportedFormats(): array
    {
        return [
            [
                'id' => 'pantheon_txt',
                'name' => 'Пантеон (.txt)',
                'description' => 'Извоз од Пантеон сметководство (cp1251 кодирање)',
                'extensions' => ['txt'],
            ],
            [
                'id' => 'csv',
                'name' => 'CSV (.csv)',
                'description' => 'CSV со колони: nalog_id, date, account_code, account_name, description, debit, credit',
                'extensions' => ['csv'],
            ],
        ];
    }

    /**
     * Parse a Pantheon firms file (firmi.txt) to build firma_id => name map.
     *
     * Format: key:type:value pairs. firma:i:ID / ime:s:Name
     */
    public function parseFirmsFile(string $content): array
    {
        $content = $this->normalizeEncoding($content);
        $lines = preg_split('/\r?\n/', $content);
        $firms = [];
        $currentId = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (!preg_match('/^([a-z_]+):([a-z]):(.*)$/i', $line, $m)) {
                continue;
            }

            $key = strtolower($m[1]);
            $value = $m[3];

            if ($key === 'firma') {
                $currentId = (int) $value;
            } elseif ($key === 'ime' && $currentId > 0) {
                $firms[$currentId] = trim($value);
                $currentId = null;
            }
        }

        return $firms;
    }

    // ===================================================================
    // INTERNAL HELPERS
    // ===================================================================

    /**
     * Finalize a Pantheon line item: handle compound entries (both debit + credit).
     */
    protected function finalizeItem(array $item): array
    {
        return $item;
    }

    /**
     * Build a nalog structure from parsed data.
     */
    protected function buildNalog(array $nalogMeta, array $items, array $firmsMap = []): array
    {
        $lineItems = [];

        foreach ($items as $item) {
            $dolguva = $item['dolguva'] ?? 0;
            $pobaruva = $item['pobaruva'] ?? 0;
            $code = $item['konto'] ?? '';

            // Skip items with empty konto (already warned in parser)
            if (trim($code) === '') {
                continue;
            }

            $name = $this->guessAccountName($code);
            $description = $item['opis'] ?? '';
            $reference = $item['vvrska'] ?? '';
            $firmaId = $item['firma'] ?? 0;
            $counterpartyName = null;
            if ($firmaId > 0 && isset($firmsMap[$firmaId])) {
                $counterpartyName = $firmsMap[$firmaId];
            }

            $base = [
                'account_code' => $code,
                'account_name' => $name,
                'description' => $description,
                'reference' => $reference,
                'counterparty_name' => $counterpartyName,
                'firma_id' => $firmaId > 0 ? $firmaId : null,
            ];

            // A line can have both debit AND credit (compound entry)
            if ($dolguva > 0) {
                $lineItems[] = array_merge($base, [
                    'amount' => $dolguva,
                    'credited' => false,
                ]);
            }
            if ($pobaruva > 0) {
                $lineItems[] = array_merge($base, [
                    'amount' => $pobaruva,
                    'credited' => true,
                ]);
            }
        }

        $typeCode = explode('-', $nalogMeta['id'])[0] ?? '';
        $typeName = self::NALOG_TYPES[$typeCode] ?? 'Книжење';

        $totalDebit = array_sum(array_column(array_filter($lineItems, fn($i) => !$i['credited']), 'amount'));
        $totalCredit = array_sum(array_column(array_filter($lineItems, fn($i) => $i['credited']), 'amount'));

        return [
            'nalog_id' => $nalogMeta['id'],
            'date' => $nalogMeta['date'] ?? date('Y-m-d'),
            'type' => $typeName,
            'line_items' => $lineItems,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'balanced' => abs($totalDebit - $totalCredit) < 0.01,
            'line_count' => count($lineItems),
        ];
    }

    /**
     * Parse date from various formats to Y-m-d.
     */
    protected function parseDate(string $date): string
    {
        $date = trim($date);
        if (empty($date)) {
            return date('Y-m-d');
        }

        // DD-MM-YYYY or DD/MM/YYYY
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // YYYY-MM-DD (already correct)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // DD.MM.YYYY
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $date, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        return date('Y-m-d');
    }

    /**
     * Guess account name from code using Macedonian chart of accounts conventions.
     */
    protected function guessAccountName(string $code): string
    {
        $prefix = substr($code, 0, 2);

        $names = [
            '01' => 'Основни средства',
            '02' => 'Нематеријални средства',
            '03' => 'Долгорочни финансиски средства',
            '10' => 'Парични средства',
            '12' => 'Побарувања од купувачи',
            '13' => 'ДДВ и даночни побарувања',
            '14' => 'Краткорочни финансиски средства',
            '15' => 'Залихи',
            '16' => 'Дадени краткорочни заеми',
            '22' => 'Обврски кон добавувачи',
            '23' => 'Краткорочни обврски',
            '24' => 'Обврски за плати',
            '25' => 'Други краткорочни обврски',
            '28' => 'Одложени приходи',
            '30' => 'Приходи од дејност',
            '31' => 'Приходи од продажба',
            '35' => 'Финансиски приходи',
            '40' => 'Расходи од работење',
            '41' => 'Материјални трошоци',
            '42' => 'Трошоци за вработени',
            '43' => 'Амортизација',
            '44' => 'Услуги и провизии',
            '45' => 'Други расходи',
            '66' => 'Вонредни расходи',
            '70' => 'Приходи од услуги',
            '74' => 'Приходи од услуги',
            '90' => 'Главнина',
            '94' => 'Резерви',
            '95' => 'Акумулирана добивка',
        ];

        return $names[$prefix] ?? "Сметка {$code}";
    }
}

// CLAUDE-CHECKPOINT
