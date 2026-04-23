<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Models\UnsubscribeToken;
use Modules\Mk\Bitrix\Services\PostmarkOutreachService;

class OutreachReengageClickersCommand extends Command
{
    protected $signature = 'outreach:reengage-clickers
        {--limit=50 : Max emails to send}
        {--dry-run : Preview without sending}
        {--min-days=2 : Min days since last click before re-engaging}';

    protected $description = 'Send re-engagement emails to leads who clicked but did not sign up';

    protected int $maxTemplatesPerLead = 5;

    public function handle(PostmarkOutreachService $postmarkService): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');
        $minDays = (int) $this->option('min-days');

        $this->info('Outreach Clicker Re-engagement');
        $this->line('==============================');

        if ($dryRun) {
            $this->warn('DRY RUN — no emails will be sent');
        }

        $eligible = $this->findEligibleClickers($minDays, $limit);

        $this->info("Found {$eligible->count()} eligible clickers (clicked ≥{$minDays} days ago, not signed up, not suppressed)");

        if ($eligible->isEmpty()) {
            $this->info('No eligible leads. Done.');
            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        foreach ($eligible as $index => $row) {
            $lead = OutreachLead::find($row->outreach_lead_id);
            if (! $lead) {
                $skipped++;
                continue;
            }

            // Check max emails per lead
            $sendCount = OutreachSend::where('outreach_lead_id', $lead->id)->count();
            if ($sendCount >= $this->maxTemplatesPerLead) {
                $this->line("  Skip {$lead->email} — already received {$sendCount} emails");
                $skipped++;
                continue;
            }

            if (Suppression::isSuppressed($lead->email)) {
                $this->line("  Skip {$lead->email} — suppressed");
                $skipped++;
                continue;
            }

            $templateKey = 'company_clicker_reengage';

            if ($dryRun) {
                $this->info("  [DRY] Would send {$templateKey} to {$lead->email} (clicked: {$row->clicked_at}, clicks: {$row->click_count})");
                $sent++;
                continue;
            }

            // Jitter between sends
            if ($index > 0) {
                sleep(rand(2, 5));
            }

            $unsubToken = UnsubscribeToken::getOrCreateForLead($lead->id);
            $unsubUrl = $unsubToken->getUnsubscribeUrl();

            $messageId = $postmarkService->sendOutreachEmail(
                $lead->email,
                $templateKey,
                ['companyName' => $lead->company_name ?? 'there'],
                $unsubUrl
            );

            if (! $messageId) {
                $this->error("  Failed: {$lead->email}");
                continue;
            }

            OutreachSend::create([
                'email' => $lead->email,
                'outreach_lead_id' => $lead->id,
                'template_key' => $templateKey,
                'postmark_message_id' => $messageId,
                'status' => OutreachSend::STATUS_SENT,
                'sent_at' => now(),
            ]);

            $this->info("  Sent {$templateKey} to {$lead->email}");
            $sent++;
        }

        $this->newLine();
        $this->info("Done. Sent: {$sent}, Skipped: {$skipped}");

        Log::info('Outreach clicker re-engagement completed', [
            'sent' => $sent,
            'skipped' => $skipped,
            'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    protected function findEligibleClickers(int $minDays, int $limit)
    {
        return DB::table('outreach_sends')
            ->whereNotNull('clicked_at')
            ->where('clicked_at', '<=', now()->subDays($minDays))
            ->whereNotIn('outreach_sends.email', function ($q) {
                $q->select('email')->from('users');
            })
            ->whereNotIn('outreach_sends.email', function ($q) {
                $q->select('email')->from('suppressions');
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('outreach_sends as os2')
                    ->whereColumn('os2.email', 'outreach_sends.email')
                    ->where('os2.template_key', 'company_clicker_reengage');
            })
            ->select('outreach_sends.email', 'outreach_sends.outreach_lead_id', 'outreach_sends.clicked_at', 'outreach_sends.click_count')
            ->groupBy('outreach_sends.email', 'outreach_sends.outreach_lead_id', 'outreach_sends.clicked_at', 'outreach_sends.click_count')
            ->orderBy('outreach_sends.clicked_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
