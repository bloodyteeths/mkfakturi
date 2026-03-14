<?php

namespace App\Services\Onboarding;

use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    /**
     * Get onboarding progress for a company.
     *
     * Returns completion status for each onboarding step.
     */
    public function getProgress(Company $company): array
    {
        $companyId = $company->id;

        $hasAddress = $company->address()
            ->whereNotNull('address_street_1')
            ->where('address_street_1', '!=', '')
            ->exists();

        $hasTaxId = !empty($company->vat_id) || !empty($company->tax_id);

        $hasLogo = $company->getMedia('logo')->count() > 0;

        $hasInvoices = $company->invoices()->exists();

        $hasBankAccount = $company->bankAccounts()->exists();

        $hasImport = DB::table('import_jobs')
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->exists();

        // Check if journal entries were imported via IFRS
        $hasJournalImport = DB::table('ifrs_transactions')
            ->where('entity_id', $companyId)
            ->where('transaction_type', 'JN')
            ->exists();

        $importDismissed = CompanySetting::getSetting('onboarding_import_dismissed', $companyId) === 'true';

        $dismissed = CompanySetting::getSetting('onboarding_dismissed', $companyId) === 'true';
        $completedAt = CompanySetting::getSetting('onboarding_completed_at', $companyId);
        $source = CompanySetting::getSetting('onboarding_source', $companyId);

        $steps = [
            [
                'key' => 'company_details',
                'completed' => $hasAddress && $hasTaxId,
            ],
            [
                'key' => 'upload_logo',
                'completed' => $hasLogo,
            ],
            [
                'key' => 'import_data',
                'completed' => $hasImport || $hasJournalImport || $importDismissed,
            ],
            [
                'key' => 'first_invoice',
                'completed' => $hasInvoices,
            ],
            [
                'key' => 'bank_account',
                'completed' => $hasBankAccount,
            ],
        ];

        $completedCount = collect($steps)->filter(fn ($s) => $s['completed'])->count();

        return [
            'steps' => $steps,
            'completed_count' => $completedCount,
            'total_count' => count($steps),
            'all_completed' => $completedCount === count($steps),
            'dismissed' => $dismissed,
            'completed_at' => $completedAt,
            'source' => $source,
        ];
    }

    /**
     * Mark onboarding as completed.
     */
    public function markCompleted(Company $company): void
    {
        CompanySetting::setSettings([
            'onboarding_completed_at' => now()->toIso8601String(),
        ], $company->id);
    }

    /**
     * Dismiss the onboarding checklist.
     */
    public function dismiss(Company $company): void
    {
        CompanySetting::setSettings([
            'onboarding_dismissed' => 'true',
        ], $company->id);
    }

    /**
     * Save selected migration source.
     */
    public function saveSource(Company $company, string $source): void
    {
        $validSources = [
            'pantheon', 'zonel', 'ekonomika', 'astral', 'b2b', 'excel', 'fresh',
        ];

        if (!in_array($source, $validSources)) {
            throw new \InvalidArgumentException("Invalid onboarding source: {$source}");
        }

        CompanySetting::setSettings([
            'onboarding_source' => $source,
        ], $company->id);
    }

    /**
     * Check if onboarding should be shown for this company.
     */
    public function shouldShowOnboarding(Company $company): bool
    {
        $dismissed = CompanySetting::getSetting('onboarding_dismissed', $company->id);
        $completedAt = CompanySetting::getSetting('onboarding_completed_at', $company->id);

        return $dismissed !== 'true' && empty($completedAt);
    }

    /**
     * Get migration progress per data type for MigrationHub.
     */
    public function getMigrationProgress(Company $company): array
    {
        $companyId = $company->id;

        // Check completed import_jobs by type
        $completedTypes = DB::table('import_jobs')
            ->where('company_id', $companyId)
            ->where('status', 'completed')
            ->pluck('type')
            ->toArray();

        // Check in-progress import_jobs
        $inProgressTypes = DB::table('import_jobs')
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'parsing', 'mapping', 'validating', 'committing'])
            ->pluck('type')
            ->toArray();

        // Check journal entries (from IFRS journal import)
        $hasJournalEntries = DB::table('ifrs_transactions')
            ->where('entity_id', $companyId)
            ->where('transaction_type', 'JN')
            ->exists();

        // Check chart of accounts
        $hasChartOfAccounts = DB::table('ifrs_accounts')
            ->where('entity_id', $companyId)
            ->exists();

        // Determine status per migration step
        $steps = [
            'customers_suppliers' => $this->resolveStepStatus($completedTypes, $inProgressTypes, ['customers']),
            'products_services' => $this->resolveStepStatus($completedTypes, $inProgressTypes, ['items']),
            'invoices_payments' => $this->resolveStepStatus($completedTypes, $inProgressTypes, ['invoices', 'payments', 'bills']),
            'chart_of_accounts' => $hasChartOfAccounts ? 'completed' : 'not_started',
            'journal_entries' => $hasJournalEntries ? 'completed' : 'not_started',
            'opening_balances' => 'not_started',
            'fixed_assets' => 'not_started',
        ];

        return $steps;
    }

    /**
     * Resolve step status from completed/in-progress import types.
     */
    protected function resolveStepStatus(array $completedTypes, array $inProgressTypes, array $relevantTypes): string
    {
        foreach ($relevantTypes as $type) {
            if (in_array($type, $completedTypes)) {
                return 'completed';
            }
        }
        foreach ($relevantTypes as $type) {
            if (in_array($type, $inProgressTypes)) {
                return 'in_progress';
            }
        }

        return 'not_started';
    }
}
// CLAUDE-CHECKPOINT
