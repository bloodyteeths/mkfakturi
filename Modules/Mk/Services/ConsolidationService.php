<?php

namespace Modules\Mk\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\ConsolidationGroup;
use Modules\Mk\Models\ConsolidationMember;

class ConsolidationService
{
    /**
     * IFRS account type labels for Macedonian locale.
     */
    protected array $accountTypeLabels = [
        'OPERATING_REVENUE' => 'Оперативни приходи',
        'OPERATING_EXPENSE' => 'Оперативни расходи',
        'NON_OPERATING_REVENUE' => 'Неоперативни приходи',
        'NON_OPERATING_EXPENSE' => 'Неоперативни расходи',
        'DIRECT_EXPENSE' => 'Директни трошоци',
        'OVERHEAD_EXPENSE' => 'Општи трошоци',
        'CURRENT_ASSET' => 'Тековни средства',
        'NON_CURRENT_ASSET' => 'Нетековни средства',
        'CURRENT_LIABILITY' => 'Тековни обврски',
        'NON_CURRENT_LIABILITY' => 'Нетековни обврски',
        'EQUITY' => 'Капитал',
        'CONTRA_ASSET' => 'Контра средства',
        'CONTRA_LIABILITY' => 'Контра обврски',
        'CONTRA_EQUITY' => 'Контра капитал',
        'RECEIVABLE' => 'Побарувања',
        'PAYABLE' => 'Обврски',
        'BANK' => 'Банка',
        'INVENTORY' => 'Залихи',
        'RECONCILIATION' => 'Усогласување',
    ];

    public function __construct(
        private IfrsAdapter $ifrsAdapter
    ) {}

    /**
     * List consolidation groups for a partner.
     *
     * @return array
     */
    public function listGroups(int $partnerId): array
    {
        $groups = ConsolidationGroup::forPartner($partnerId)
            ->with(['parentCompany:id,name', 'members.company:id,name'])
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->get();

        return $groups->map(fn (ConsolidationGroup $g) => $this->formatGroup($g))->toArray();
    }

    /**
     * Create a consolidation group with members.
     *
     * @param  array  $data  {name, partner_id, parent_company_id, currency_code, notes, members: [{company_id, ownership_pct, is_parent}]}
     */
    public function createGroup(array $data): ConsolidationGroup
    {
        return DB::transaction(function () use ($data) {
            $group = ConsolidationGroup::create([
                'partner_id' => $data['partner_id'],
                'name' => $data['name'],
                'parent_company_id' => $data['parent_company_id'],
                'currency_code' => $data['currency_code'] ?? 'MKD',
                'notes' => $data['notes'] ?? null,
            ]);

            // Always add parent company as a member with is_parent = true
            $parentAdded = false;

            if (! empty($data['members']) && is_array($data['members'])) {
                foreach ($data['members'] as $member) {
                    $isParent = (int) $member['company_id'] === (int) $data['parent_company_id'];
                    if ($isParent) {
                        $parentAdded = true;
                    }

                    ConsolidationMember::create([
                        'group_id' => $group->id,
                        'company_id' => $member['company_id'],
                        'ownership_pct' => $member['ownership_pct'] ?? 100.00,
                        'is_parent' => $isParent || ($member['is_parent'] ?? false),
                    ]);
                }
            }

            // If parent company wasn't in the members list, add it
            if (! $parentAdded) {
                ConsolidationMember::create([
                    'group_id' => $group->id,
                    'company_id' => $data['parent_company_id'],
                    'ownership_pct' => 100.00,
                    'is_parent' => true,
                ]);
            }

            Log::info('Consolidation group created', [
                'group_id' => $group->id,
                'partner_id' => $data['partner_id'],
                'name' => $group->name,
                'members_count' => $group->members()->count(),
            ]);

            return $group->load(['members.company:id,name', 'parentCompany:id,name']);
        });
    }

    /**
     * Update a consolidation group and its members.
     */
    public function updateGroup(int $id, array $data): ConsolidationGroup
    {
        $group = ConsolidationGroup::findOrFail($id);

        return DB::transaction(function () use ($group, $data) {
            $group->update(array_filter([
                'name' => $data['name'] ?? null,
                'parent_company_id' => $data['parent_company_id'] ?? null,
                'currency_code' => $data['currency_code'] ?? null,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
            ], fn ($v) => $v !== null));

            if (isset($data['members']) && is_array($data['members'])) {
                // Replace all members
                $group->members()->delete();

                $parentCompanyId = $data['parent_company_id'] ?? $group->parent_company_id;
                $parentAdded = false;

                foreach ($data['members'] as $member) {
                    $isParent = (int) $member['company_id'] === (int) $parentCompanyId;
                    if ($isParent) {
                        $parentAdded = true;
                    }

                    ConsolidationMember::create([
                        'group_id' => $group->id,
                        'company_id' => $member['company_id'],
                        'ownership_pct' => $member['ownership_pct'] ?? 100.00,
                        'is_parent' => $isParent || ($member['is_parent'] ?? false),
                    ]);
                }

                if (! $parentAdded) {
                    ConsolidationMember::create([
                        'group_id' => $group->id,
                        'company_id' => $parentCompanyId,
                        'ownership_pct' => 100.00,
                        'is_parent' => true,
                    ]);
                }
            }

            Log::info('Consolidation group updated', [
                'group_id' => $group->id,
            ]);

            return $group->fresh()->load(['members.company:id,name', 'parentCompany:id,name']);
        });
    }

    /**
     * Soft delete a consolidation group.
     */
    public function deleteGroup(int $id): void
    {
        $group = ConsolidationGroup::findOrFail($id);
        $group->delete();

        Log::info('Consolidation group deleted', [
            'group_id' => $id,
        ]);
    }

    /**
     * Get a single group with members.
     */
    public function getGroup(int $id): ConsolidationGroup
    {
        return ConsolidationGroup::with(['members.company:id,name', 'parentCompany:id,name'])
            ->withCount('members')
            ->findOrFail($id);
    }

    /**
     * Detect intercompany transactions between group members.
     *
     * Looks for invoices where the customer belongs to another member company,
     * and bills where the vendor belongs to another member company.
     *
     * @return array List of intercompany transactions
     */
    public function detectIntercompany(int $groupId, string $startDate, string $endDate): array
    {
        $group = ConsolidationGroup::with('members.company')->findOrFail($groupId);
        $memberCompanyIds = $group->members->pluck('company_id')->toArray();

        if (count($memberCompanyIds) < 2) {
            return [];
        }

        $intercompanyTransactions = [];

        // For each member company, find invoices where customer matches another member
        // We match by comparing customer.name / customer.email with company.name
        foreach ($group->members as $sellerMember) {
            $sellerId = $sellerMember->company_id;
            $sellerCompany = $sellerMember->company;

            if (! $sellerCompany) {
                continue;
            }

            $otherCompanyIds = array_filter($memberCompanyIds, fn ($id) => $id !== $sellerId);

            if (empty($otherCompanyIds)) {
                continue;
            }

            // Get names of other member companies for matching
            $otherCompanies = Company::whereIn('id', $otherCompanyIds)->get();
            $otherCompanyNames = $otherCompanies->pluck('name')->toArray();
            $otherCompanyNameMap = $otherCompanies->pluck('name', 'id')->toArray();

            if (empty($otherCompanyNames)) {
                continue;
            }

            // Find invoices from this seller to customers whose names match other member companies
            $invoices = Invoice::where('company_id', $sellerId)
                ->whereHas('customer', function ($q) use ($otherCompanyNames) {
                    $q->where(function ($sub) use ($otherCompanyNames) {
                        foreach ($otherCompanyNames as $name) {
                            $sub->orWhere('name', 'LIKE', '%' . $name . '%');
                        }
                    });
                })
                ->where('invoice_date', '>=', $startDate)
                ->where('invoice_date', '<=', $endDate)
                ->whereNull('deleted_at')
                ->with('customer:id,name')
                ->get();

            foreach ($invoices as $invoice) {
                $buyerName = $invoice->customer->name ?? '';
                $buyerCompanyId = null;
                $buyerCompanyName = '';

                // Find which member company this customer matches
                foreach ($otherCompanyNameMap as $compId => $compName) {
                    if (stripos($buyerName, $compName) !== false || stripos($compName, $buyerName) !== false) {
                        $buyerCompanyId = $compId;
                        $buyerCompanyName = $compName;
                        break;
                    }
                }

                if (! $buyerCompanyId) {
                    continue;
                }

                $intercompanyTransactions[] = [
                    'type' => 'invoice',
                    'document_id' => $invoice->id,
                    'document_number' => $invoice->invoice_number,
                    'date' => $invoice->invoice_date instanceof \DateTimeInterface
                        ? $invoice->invoice_date->format('Y-m-d')
                        : (string) $invoice->invoice_date,
                    'seller_company_id' => $sellerId,
                    'seller_company_name' => $sellerCompany->name,
                    'buyer_company_id' => $buyerCompanyId,
                    'buyer_company_name' => $buyerCompanyName,
                    'amount' => round(($invoice->total ?? 0) / 100, 2), // cents to currency
                    'currency' => $invoice->currency?->code ?? $group->currency_code,
                    'customer_name' => $buyerName,
                ];
            }
        }

        // Sort by date descending
        usort($intercompanyTransactions, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return $intercompanyTransactions;
    }

    /**
     * Generate elimination entries for intercompany transactions.
     *
     * For each intercompany transaction:
     * - Debit intercompany payable (eliminate liability on buyer side)
     * - Credit intercompany receivable (eliminate asset on seller side)
     * - Apply ownership_pct for minority interest adjustments
     *
     * @return array Elimination entries
     */
    public function generateEliminations(int $groupId, string $startDate, string $endDate): array
    {
        $group = ConsolidationGroup::with('members')->findOrFail($groupId);
        $intercompany = $this->detectIntercompany($groupId, $startDate, $endDate);

        if (empty($intercompany)) {
            return [
                'eliminations' => [],
                'total_eliminated' => 0,
                'transaction_count' => 0,
            ];
        }

        // Build ownership lookup
        $ownershipMap = [];
        foreach ($group->members as $member) {
            $ownershipMap[$member->company_id] = (float) $member->ownership_pct;
        }

        $eliminations = [];
        $totalEliminated = 0;

        foreach ($intercompany as $txn) {
            $sellerOwnership = ($ownershipMap[$txn['seller_company_id']] ?? 100) / 100;
            $buyerOwnership = ($ownershipMap[$txn['buyer_company_id']] ?? 100) / 100;

            // Elimination amount is the lesser ownership percentage applied to the transaction
            $effectiveRate = min($sellerOwnership, $buyerOwnership);
            $eliminationAmount = round($txn['amount'] * $effectiveRate, 2);
            $minorityInterest = round($txn['amount'] - $eliminationAmount, 2);

            $eliminations[] = [
                'document_number' => $txn['document_number'],
                'date' => $txn['date'],
                'seller_company_id' => $txn['seller_company_id'],
                'seller_company_name' => $txn['seller_company_name'],
                'buyer_company_id' => $txn['buyer_company_id'],
                'buyer_company_name' => $txn['buyer_company_name'],
                'original_amount' => $txn['amount'],
                'elimination_amount' => $eliminationAmount,
                'minority_interest' => $minorityInterest,
                'effective_rate' => round($effectiveRate * 100, 2),
                'entries' => [
                    [
                        'description' => "Eliminate intercompany receivable - {$txn['document_number']}",
                        'account_type' => 'RECEIVABLE',
                        'debit' => 0,
                        'credit' => $eliminationAmount,
                        'company_id' => $txn['seller_company_id'],
                    ],
                    [
                        'description' => "Eliminate intercompany payable - {$txn['document_number']}",
                        'account_type' => 'PAYABLE',
                        'debit' => $eliminationAmount,
                        'credit' => 0,
                        'company_id' => $txn['buyer_company_id'],
                    ],
                ],
            ];

            $totalEliminated += $eliminationAmount;
        }

        return [
            'eliminations' => $eliminations,
            'total_eliminated' => round($totalEliminated, 2),
            'transaction_count' => count($eliminations),
        ];
    }

    /**
     * Generate consolidated trial balance across group member companies.
     *
     * Aggregates trial balances weighted by ownership_pct, then subtracts elimination entries.
     *
     * @return array Consolidated trial balance data
     */
    public function consolidatedTrialBalance(int $groupId, string $startDate, string $endDate): array
    {
        $group = ConsolidationGroup::with('members.company')->findOrFail($groupId);

        // Collect per-account-type totals across all members
        $consolidated = [];
        $companyBreakdown = [];

        foreach ($group->members as $member) {
            $company = $member->company;

            if (! $company) {
                continue;
            }

            $ownershipRate = (float) $member->ownership_pct / 100;

            // Get 6-column trial balance for this company
            $tb = $this->ifrsAdapter->getTrialBalanceSixColumn($company, $startDate, $endDate);

            $companyData = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'ownership_pct' => (float) $member->ownership_pct,
                'is_parent' => $member->is_parent,
                'accounts' => [],
            ];

            if (isset($tb['error'])) {
                $companyData['error'] = $tb['error'];
                $companyBreakdown[] = $companyData;
                continue;
            }

            foreach ($tb['accounts'] ?? [] as $account) {
                $accountType = $account['account_type'] ?? 'UNKNOWN';

                // Weight by ownership percentage
                $closingDebit = round(($account['closing_debit'] ?? 0) * $ownershipRate, 2);
                $closingCredit = round(($account['closing_credit'] ?? 0) * $ownershipRate, 2);
                $periodDebit = round(($account['period_debit'] ?? 0) * $ownershipRate, 2);
                $periodCredit = round(($account['period_credit'] ?? 0) * $ownershipRate, 2);

                if (! isset($consolidated[$accountType])) {
                    $consolidated[$accountType] = [
                        'account_type' => $accountType,
                        'account_type_label' => $this->accountTypeLabels[$accountType] ?? $accountType,
                        'closing_debit' => 0,
                        'closing_credit' => 0,
                        'period_debit' => 0,
                        'period_credit' => 0,
                        'elimination_debit' => 0,
                        'elimination_credit' => 0,
                        'net_debit' => 0,
                        'net_credit' => 0,
                    ];
                }

                $consolidated[$accountType]['closing_debit'] += $closingDebit;
                $consolidated[$accountType]['closing_credit'] += $closingCredit;
                $consolidated[$accountType]['period_debit'] += $periodDebit;
                $consolidated[$accountType]['period_credit'] += $periodCredit;

                $companyData['accounts'][] = [
                    'account_type' => $accountType,
                    'closing_debit' => $closingDebit,
                    'closing_credit' => $closingCredit,
                ];
            }

            $companyBreakdown[] = $companyData;
        }

        // Apply eliminations
        $eliminationData = $this->generateEliminations($groupId, $startDate, $endDate);

        foreach ($eliminationData['eliminations'] as $elim) {
            foreach ($elim['entries'] as $entry) {
                $accountType = $entry['account_type'];

                if (! isset($consolidated[$accountType])) {
                    $consolidated[$accountType] = [
                        'account_type' => $accountType,
                        'account_type_label' => $this->accountTypeLabels[$accountType] ?? $accountType,
                        'closing_debit' => 0,
                        'closing_credit' => 0,
                        'period_debit' => 0,
                        'period_credit' => 0,
                        'elimination_debit' => 0,
                        'elimination_credit' => 0,
                        'net_debit' => 0,
                        'net_credit' => 0,
                    ];
                }

                $consolidated[$accountType]['elimination_debit'] += $entry['debit'];
                $consolidated[$accountType]['elimination_credit'] += $entry['credit'];
            }
        }

        // Calculate net (consolidated = raw - eliminations)
        $totalNetDebit = 0;
        $totalNetCredit = 0;

        foreach ($consolidated as &$row) {
            $row['closing_debit'] = round($row['closing_debit'], 2);
            $row['closing_credit'] = round($row['closing_credit'], 2);
            $row['elimination_debit'] = round($row['elimination_debit'], 2);
            $row['elimination_credit'] = round($row['elimination_credit'], 2);

            // Net = closing + elimination adjustments
            // Elimination debits increase the debit side, elimination credits increase the credit side
            $row['net_debit'] = round($row['closing_debit'] + $row['elimination_debit'], 2);
            $row['net_credit'] = round($row['closing_credit'] + $row['elimination_credit'], 2);

            $totalNetDebit += $row['net_debit'];
            $totalNetCredit += $row['net_credit'];
        }
        unset($row);

        // Sort by account type
        $rows = array_values($consolidated);
        usort($rows, fn ($a, $b) => strcmp($a['account_type'], $b['account_type']));

        return [
            'group_name' => $group->name,
            'from_date' => $startDate,
            'to_date' => $endDate,
            'accounts' => $rows,
            'totals' => [
                'net_debit' => round($totalNetDebit, 2),
                'net_credit' => round($totalNetCredit, 2),
                'is_balanced' => abs($totalNetDebit - $totalNetCredit) < 0.01,
            ],
            'eliminations' => $eliminationData,
            'companies' => $companyBreakdown,
        ];
    }

    /**
     * Generate consolidated income statement (P&L).
     *
     * @return array Consolidated P&L with revenue, expenses, minority interest
     */
    public function consolidatedIncomeStatement(int $groupId, string $startDate, string $endDate): array
    {
        $group = ConsolidationGroup::with('members.company')->findOrFail($groupId);

        $totalRevenue = 0;
        $totalExpenses = 0;
        $companyBreakdown = [];

        foreach ($group->members as $member) {
            $company = $member->company;

            if (! $company) {
                continue;
            }

            $ownershipRate = (float) $member->ownership_pct / 100;

            $is = $this->ifrsAdapter->getIncomeStatement($company, $startDate, $endDate);

            $companyRevenue = 0;
            $companyExpenses = 0;

            if (! isset($is['error'])) {
                $isTotals = $is['income_statement']['totals'] ?? [];
                $companyRevenue = round(($isTotals['revenue'] ?? 0) * $ownershipRate, 2);
                $companyExpenses = round(($isTotals['expenses'] ?? 0) * $ownershipRate, 2);
            }

            $totalRevenue += $companyRevenue;
            $totalExpenses += $companyExpenses;

            $companyBreakdown[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'ownership_pct' => (float) $member->ownership_pct,
                'is_parent' => $member->is_parent,
                'revenue' => $companyRevenue,
                'expenses' => $companyExpenses,
                'net_income' => round($companyRevenue - $companyExpenses, 2),
                'error' => $is['error'] ?? null,
            ];
        }

        // Apply intercompany revenue/expense eliminations
        $eliminationData = $this->generateEliminations($groupId, $startDate, $endDate);
        $revenueElimination = $eliminationData['total_eliminated'];

        $netRevenue = round($totalRevenue - $revenueElimination, 2);
        $netIncome = round($netRevenue - $totalExpenses, 2);

        // Calculate minority interest
        $minorityInterest = 0;
        foreach ($group->members as $member) {
            if ($member->is_parent) {
                continue;
            }
            $minorityRate = 1 - ((float) $member->ownership_pct / 100);
            if ($minorityRate > 0) {
                $memberIncome = 0;
                foreach ($companyBreakdown as $cb) {
                    if ($cb['company_id'] === $member->company_id) {
                        // Use the un-weighted income to compute minority share
                        $memberIncome = $cb['net_income'];
                        break;
                    }
                }
                $minorityInterest += round($memberIncome * $minorityRate / ((float) $member->ownership_pct / 100), 2);
            }
        }

        return [
            'group_name' => $group->name,
            'from_date' => $startDate,
            'to_date' => $endDate,
            'consolidated' => [
                'total_revenue' => round($totalRevenue, 2),
                'revenue_elimination' => $revenueElimination,
                'net_revenue' => $netRevenue,
                'total_expenses' => round($totalExpenses, 2),
                'net_income_before_minority' => $netIncome,
                'minority_interest' => round($minorityInterest, 2),
                'net_income' => round($netIncome - $minorityInterest, 2),
            ],
            'companies' => $companyBreakdown,
            'eliminations' => $eliminationData,
        ];
    }

    /**
     * Generate consolidated balance sheet.
     *
     * @param  string  $date  As-of date (Y-m-d)
     * @return array Consolidated balance sheet
     */
    public function consolidatedBalanceSheet(int $groupId, string $date): array
    {
        $group = ConsolidationGroup::with('members.company')->findOrFail($groupId);

        $totalAssets = 0;
        $totalLiabilities = 0;
        $totalEquity = 0;
        $companyBreakdown = [];

        foreach ($group->members as $member) {
            $company = $member->company;

            if (! $company) {
                continue;
            }

            $ownershipRate = (float) $member->ownership_pct / 100;

            $bs = $this->ifrsAdapter->getBalanceSheet($company, $date);

            $companyAssets = 0;
            $companyLiabilities = 0;
            $companyEquity = 0;

            if (! isset($bs['error'])) {
                $bsTotals = $bs['balance_sheet']['totals'] ?? [];
                $companyAssets = round(($bsTotals['assets'] ?? 0) * $ownershipRate, 2);
                $companyLiabilities = round(($bsTotals['liabilities'] ?? 0) * $ownershipRate, 2);
                $companyEquity = round(($bsTotals['equity'] ?? 0) * $ownershipRate, 2);
            }

            $totalAssets += $companyAssets;
            $totalLiabilities += $companyLiabilities;
            $totalEquity += $companyEquity;

            $companyBreakdown[] = [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'ownership_pct' => (float) $member->ownership_pct,
                'is_parent' => $member->is_parent,
                'assets' => $companyAssets,
                'liabilities' => $companyLiabilities,
                'equity' => $companyEquity,
                'error' => $bs['error'] ?? null,
            ];
        }

        // Apply intercompany eliminations to receivables/payables
        // Use the end of the period as the date range for intercompany detection
        $yearStart = Carbon::parse($date)->startOfYear()->toDateString();
        $eliminationData = $this->generateEliminations($groupId, $yearStart, $date);
        $totalEliminated = $eliminationData['total_eliminated'];

        // Eliminate intercompany receivables from assets and intercompany payables from liabilities
        $netAssets = round($totalAssets - $totalEliminated, 2);
        $netLiabilities = round($totalLiabilities - $totalEliminated, 2);

        // Calculate minority interest in equity
        $minorityInterest = 0;
        foreach ($group->members as $member) {
            if ($member->is_parent) {
                continue;
            }
            $minorityRate = 1 - ((float) $member->ownership_pct / 100);
            if ($minorityRate > 0) {
                foreach ($companyBreakdown as $cb) {
                    if ($cb['company_id'] === $member->company_id) {
                        $minorityInterest += round($cb['equity'] * $minorityRate / ((float) $member->ownership_pct / 100), 2);
                        break;
                    }
                }
            }
        }

        return [
            'group_name' => $group->name,
            'date' => $date,
            'consolidated' => [
                'total_assets' => round($totalAssets, 2),
                'asset_elimination' => $totalEliminated,
                'net_assets' => $netAssets,
                'total_liabilities' => round($totalLiabilities, 2),
                'liability_elimination' => $totalEliminated,
                'net_liabilities' => $netLiabilities,
                'total_equity' => round($totalEquity, 2),
                'minority_interest' => round($minorityInterest, 2),
                'net_equity' => round($totalEquity - $minorityInterest, 2),
            ],
            'companies' => $companyBreakdown,
            'eliminations' => $eliminationData,
        ];
    }

    /**
     * Get summary overview for a consolidation group.
     *
     * @return array Summary with total assets, revenue, member count, intercompany count
     */
    public function summary(int $groupId): array
    {
        $group = ConsolidationGroup::with(['members.company:id,name', 'parentCompany:id,name'])
            ->withCount('members')
            ->findOrFail($groupId);

        $today = Carbon::now()->toDateString();
        $yearStart = Carbon::now()->startOfYear()->toDateString();

        // Quick aggregation of total assets and revenue
        $totalAssets = 0;
        $totalRevenue = 0;

        foreach ($group->members as $member) {
            $company = $member->company;
            if (! $company) {
                continue;
            }

            $ownershipRate = (float) $member->ownership_pct / 100;

            $bs = $this->ifrsAdapter->getBalanceSheet($company, $today);
            if (! isset($bs['error'])) {
                $totalAssets += round(($bs['balance_sheet']['totals']['assets'] ?? 0) * $ownershipRate, 2);
            }

            $is = $this->ifrsAdapter->getIncomeStatement($company, $yearStart, $today);
            if (! isset($is['error'])) {
                $totalRevenue += round(($is['income_statement']['totals']['revenue'] ?? 0) * $ownershipRate, 2);
            }
        }

        // Count intercompany transactions for current year
        $intercompany = $this->detectIntercompany($groupId, $yearStart, $today);

        return [
            'group' => $this->formatGroup($group),
            'total_assets' => round($totalAssets, 2),
            'total_revenue' => round($totalRevenue, 2),
            'member_count' => $group->members_count,
            'intercompany_count' => count($intercompany),
            'intercompany_total' => round(array_sum(array_column($intercompany, 'amount')), 2),
        ];
    }

    /**
     * Format a group for API response.
     */
    protected function formatGroup(ConsolidationGroup $group): array
    {
        return [
            'id' => $group->id,
            'partner_id' => $group->partner_id,
            'name' => $group->name,
            'parent_company_id' => $group->parent_company_id,
            'parent_company' => $group->parentCompany ? [
                'id' => $group->parentCompany->id,
                'name' => $group->parentCompany->name,
            ] : null,
            'currency_code' => $group->currency_code,
            'notes' => $group->notes,
            'members_count' => $group->members_count ?? $group->members()->count(),
            'members' => $group->relationLoaded('members')
                ? $group->members->map(fn (ConsolidationMember $m) => [
                    'id' => $m->id,
                    'company_id' => $m->company_id,
                    'company_name' => $m->company->name ?? '',
                    'ownership_pct' => (float) $m->ownership_pct,
                    'is_parent' => $m->is_parent,
                ])
                : [],
            'created_at' => $group->created_at?->toIso8601String(),
            'updated_at' => $group->updated_at?->toIso8601String(),
        ];
    }
}

// CLAUDE-CHECKPOINT
