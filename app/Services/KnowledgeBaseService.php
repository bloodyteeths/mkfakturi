<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Knowledge Base Service
 *
 * Loads topic-based knowledge files from resources/knowledge/
 * and returns relevant content for AI chat prompts.
 */
class KnowledgeBaseService
{
    /**
     * Map of help classification categories to knowledge file names
     */
    private const TOPIC_MAP = [
        'help_invoicing' => 'invoicing',
        'help_estimates' => 'estimates',
        'help_recurring' => 'recurring-invoices',
        'help_customers' => 'customers',
        'help_expenses' => 'expenses',
        'help_bills' => 'suppliers-bills',
        'help_banking' => 'banking',
        'help_efaktura' => 'efaktura',
        'help_accounting' => 'accounting',
        'help_payroll' => 'payroll',
        'help_inventory' => 'inventory',
        'help_reports' => 'reports',
        'help_settings' => 'settings',
        'help_partner' => 'partner-accountant',
        'help_fiscal' => 'fiscal-devices',
        'help_woocommerce' => 'woocommerce',
        'help_proforma' => 'proforma-invoices',
        'help_credit_notes' => 'credit-notes',
        'help_projects' => 'projects',
        'help_custom_fields' => 'custom-fields',
        'help_currency' => 'multi-currency',
        'help_client_portal' => 'client-portal',
        'help_import' => 'import-export',
        'help_support' => 'support-tickets',
        'help_receipt_scanner' => 'receipt-scanner',
        'help_ai' => 'ai-assistant',
        'help_deadlines' => 'deadlines',
        'help_payments' => 'payments',
        'help_purchase_orders' => 'purchase-orders',
        'help_payment_orders' => 'payment-orders',
        'help_budgets' => 'budgets',
        'help_cost_centers' => 'cost-centers',
        'help_travel_orders' => 'travel-orders',
        'help_compensations' => 'compensations',
        'help_collections' => 'collections',
        'help_interest' => 'interest',
        'help_consolidation' => 'consolidation',
        'help_batch_operations' => 'batch-operations',
        'help_bi_dashboard' => 'bi-dashboard',
        'help_manufacturing' => 'manufacturing',
        'help_pos' => 'pos',
    ];

    private string $knowledgePath;

    public function __construct()
    {
        $this->knowledgePath = resource_path('knowledge');
    }

    /**
     * Get knowledge content for the given classified topics
     *
     * @param  array<string>  $topics  Classified help topic categories (e.g. ['help_invoicing', 'help_banking'])
     * @param  string  $userRole  'company' or 'accountant'
     * @return string Combined knowledge text for prompt injection
     */
    public function getKnowledge(array $topics, string $userRole = 'company'): string
    {
        $helpTopics = array_filter($topics, fn ($t) => str_starts_with($t, 'help_'));

        if (empty($helpTopics)) {
            return '';
        }

        $sections = [];

        foreach ($helpTopics as $topic) {
            $fileName = self::TOPIC_MAP[$topic] ?? null;
            if (! $fileName) {
                continue;
            }

            $content = $this->loadFile($fileName);
            if (! $content) {
                continue;
            }

            $filtered = $this->filterByRole($content, $userRole);
            if (! empty($filtered)) {
                $sections[] = $filtered;
            }
        }

        if (empty($sections)) {
            return '';
        }

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * Load ALL knowledge files at once, filtered by role.
     *
     * Total ~50KB / ~13K tokens — fits easily in Gemini's 1M context.
     * This eliminates misclassification: the AI always has every feature's
     * documentation and can answer any question correctly.
     *
     * @param  string  $userRole  'company' or 'accountant'
     * @return string All knowledge content concatenated
     */
    public function getAllKnowledge(string $userRole = 'company'): string
    {
        $cacheKey = "knowledge:all:{$userRole}";

        return Cache::remember($cacheKey, 3600, function () use ($userRole) {
            $sections = [];

            foreach (self::TOPIC_MAP as $fileName) {
                $content = $this->loadFile($fileName);
                if (! $content) {
                    continue;
                }

                $filtered = $this->filterByRole($content, $userRole);
                if (! empty($filtered)) {
                    $sections[] = $filtered;
                }
            }

            Log::info('[KnowledgeBaseService] All knowledge loaded', [
                'user_role' => $userRole,
                'files_loaded' => count($sections),
                'total_length' => array_sum(array_map('strlen', $sections)),
            ]);

            return implode("\n\n---\n\n", $sections);
        });
    }

    /**
     * Get list of all available help topics for the classification prompt
     *
     * @return array<string, string> Map of category => description
     */
    public function getTopicsList(): array
    {
        return [
            'help_invoicing' => 'Questions about creating/editing/sending invoices, proforma invoices, credit notes, PDF generation',
            'help_estimates' => 'Questions about creating estimates/quotes, converting to invoices',
            'help_recurring' => 'Questions about recurring/automatic invoices, billing schedules',
            'help_customers' => 'Questions about adding/managing customers, customer portal',
            'help_expenses' => 'Questions about recording expenses, expense categories, receipt scanning',
            'help_bills' => 'Questions about suppliers, bills from suppliers, accounts payable',
            'help_banking' => 'Questions about connecting bank accounts, PSD2, transaction import, reconciliation',
            'help_efaktura' => 'Questions about e-faktura, electronic invoicing, UJP, QES digital signing',
            'help_accounting' => 'Questions about chart of accounts, journal entries, trial balance, daily closing, year-end',
            'help_payroll' => 'Questions about employees, payroll, salaries, payslips, leave management',
            'help_inventory' => 'Questions about stock, warehouses, inventory documents, item cards',
            'help_reports' => 'Questions about reports: sales, expenses, P&L, tax, general ledger',
            'help_settings' => 'Questions about settings: company info, users, roles, taxes, templates, billing',
            'help_partner' => 'Questions about partner/accountant portal, managing client companies, commissions',
            'help_manufacturing' => 'Questions about manufacturing, BOM, production orders, shop floor, work centers',
            'help_pos' => 'Questions about POS, cash register, fiscal devices, fiscal receipts, kitchen display',
        ];
    }

    /**
     * Load a knowledge file from storage, with caching
     *
     * @param  string  $fileName  File name without extension
     * @return string|null File content or null if not found
     */
    private function loadFile(string $fileName): ?string
    {
        $cacheKey = "knowledge:{$fileName}";

        return Cache::remember($cacheKey, 3600, function () use ($fileName) {
            $filePath = $this->knowledgePath . '/' . $fileName . '.md';

            if (! file_exists($filePath)) {
                Log::warning('[KnowledgeBaseService] Knowledge file not found', [
                    'file' => $filePath,
                ]);

                return null;
            }

            $content = file_get_contents($filePath);

            Log::info('[KnowledgeBaseService] Knowledge file loaded', [
                'file' => $fileName,
                'length' => strlen($content),
            ]);

            return $content;
        });
    }

    /**
     * Filter knowledge content by user role
     *
     * Files marked "## For: accountant" are excluded for company users.
     * Files marked "## For: company" are excluded for accountant users.
     * Files marked "## For: both" are included for everyone.
     *
     * @param  string  $content  Raw knowledge file content
     * @param  string  $userRole  'company' or 'accountant'
     * @return string Filtered content (empty string if role doesn't match)
     */
    private function filterByRole(string $content, string $userRole): string
    {
        // Extract the "## For:" line
        if (preg_match('/^## For:\s*(.+)$/m', $content, $matches)) {
            $forRole = trim(strtolower($matches[1]));

            if ($forRole === 'both') {
                return $content;
            }

            if ($forRole !== $userRole) {
                return '';
            }
        }

        return $content;
    }
}
// CLAUDE-CHECKPOINT
