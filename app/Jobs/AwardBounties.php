<?php

namespace App\Jobs;

use App\Models\Partner;
use App\Models\Company;
use App\Models\AffiliateEvent;
use App\Services\CommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AwardBounties implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CommissionService $commissionService): void
    {
        Log::info('AwardBounties job started');

        $accountantBountiesAwarded = 0;
        $companyBountiesAwarded = 0;

        // Award accountant bounties (€300)
        $accountantBountiesAwarded = $this->awardAccountantBounties($commissionService);

        // Award company bounties (€50)
        $companyBountiesAwarded = $this->awardCompanyBounties($commissionService);

        Log::info('AwardBounties job completed', [
            'accountant_bounties' => $accountantBountiesAwarded,
            'company_bounties' => $companyBountiesAwarded,
        ]);
    }

    /**
     * Award accountant activation bounties
     *
     * @param CommissionService $commissionService
     * @return int Number of bounties awarded
     */
    protected function awardAccountantBounties(CommissionService $commissionService): int
    {
        $bountyAmount = config('affiliate.partner_bounty', 300.00);
        $awarded = 0;

        // Get all active partners with verified KYC who haven't received bounty yet
        $eligiblePartners = Partner::active()
            ->where('kyc_status', 'verified')
            ->whereDoesntHave('affiliateEvents', function ($query) {
                $query->where('event_type', 'partner_bounty');
            })
            ->get();

        foreach ($eligiblePartners as $partner) {
            // Check eligibility criteria:
            // 1. Has 3+ active paying companies OR
            // 2. Registered 30+ days ago

            $activePayingCompaniesCount = $this->getActivePayingCompaniesCount($partner);
            $daysSinceRegistration = now()->diffInDays($partner->created_at);

            $meetsCompanyRequirement = $activePayingCompaniesCount >= config('affiliate.bounty_min_companies', 3);
            $meetsDaysRequirement = $daysSinceRegistration >= config('affiliate.bounty_min_days', 30);

            if ($meetsCompanyRequirement || $meetsDaysRequirement) {
                // Award the bounty
                $result = $commissionService->recordPartnerBounty($partner);

                if ($result['success']) {
                    $awarded++;

                    Log::info('Accountant bounty awarded', [
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'amount' => $bountyAmount,
                        'active_companies' => $activePayingCompaniesCount,
                        'days_since_registration' => $daysSinceRegistration,
                        'met_companies_requirement' => $meetsCompanyRequirement,
                        'met_days_requirement' => $meetsDaysRequirement,
                    ]);
                } else {
                    Log::warning('Failed to award accountant bounty', [
                        'partner_id' => $partner->id,
                        'reason' => $result['message'] ?? 'Unknown error',
                    ]);
                }
            }
        }

        return $awarded;
    }

    /**
     * Award company signup bounties
     *
     * @param CommissionService $commissionService
     * @return int Number of bounties awarded
     */
    protected function awardCompanyBounties(CommissionService $commissionService): int
    {
        $bountyAmount = config('affiliate.company_bounty', 50.00);
        $awarded = 0;

        // Get all active partners
        $partners = Partner::active()->get();

        foreach ($partners as $partner) {
            // Check if this partner has already received a company bounty
            $hasReceivedCompanyBounty = AffiliateEvent::where('affiliate_partner_id', $partner->id)
                ->where('event_type', 'company_bounty')
                ->exists();

            if ($hasReceivedCompanyBounty) {
                continue; // Skip if already received
            }

            // Find the first paying company brought by this partner
            $firstPayingCompany = $this->getFirstPayingCompany($partner);

            if ($firstPayingCompany) {
                // Award the bounty
                $result = $commissionService->recordCompanyBounty($firstPayingCompany);

                if ($result['success']) {
                    $awarded++;

                    Log::info('Company bounty awarded', [
                        'partner_id' => $partner->id,
                        'partner_name' => $partner->name,
                        'company_id' => $firstPayingCompany->id,
                        'company_name' => $firstPayingCompany->name,
                        'amount' => $bountyAmount,
                    ]);
                } else {
                    Log::warning('Failed to award company bounty', [
                        'partner_id' => $partner->id,
                        'company_id' => $firstPayingCompany->id,
                        'reason' => $result['message'] ?? 'Unknown error',
                    ]);
                }
            }
        }

        return $awarded;
    }

    /**
     * Get count of active paying companies for a partner
     *
     * @param Partner $partner
     * @return int
     */
    protected function getActivePayingCompaniesCount(Partner $partner): int
    {
        // Query partner_company_links to find companies
        return DB::table('partner_company_links')
            ->join('companies', 'partner_company_links.company_id', '=', 'companies.id')
            ->join('company_subscriptions', 'companies.id', '=', 'company_subscriptions.company_id')
            ->where('partner_company_links.partner_id', $partner->id)
            ->where('partner_company_links.is_active', true)
            ->where('company_subscriptions.status', 'active') // Subscription must be active (not trial)
            ->distinct('companies.id')
            ->count('companies.id');
    }

    /**
     * Get the first paying company brought by this partner
     *
     * @param Partner $partner
     * @return Company|null
     */
    protected function getFirstPayingCompany(Partner $partner): ?Company
    {
        // Find companies linked to this partner with active subscriptions
        $companyId = DB::table('partner_company_links')
            ->join('companies', 'partner_company_links.company_id', '=', 'companies.id')
            ->join('company_subscriptions', 'companies.id', '=', 'company_subscriptions.company_id')
            ->where('partner_company_links.partner_id', $partner->id)
            ->where('partner_company_links.is_active', true)
            ->where('company_subscriptions.status', 'active') // Must have active subscription (not trial)
            ->orderBy('company_subscriptions.created_at', 'asc')
            ->value('companies.id');

        if (!$companyId) {
            return null;
        }

        return Company::find($companyId);
    }
}

// CLAUDE-CHECKPOINT
