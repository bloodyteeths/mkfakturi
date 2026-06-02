<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\OutreachLead;
use Modules\Mk\Bitrix\Models\Suppression;

class OutreachSuppressCommand extends Command
{
    protected $signature = 'outreach:suppress
                            {email : Email address to suppress}
                            {--reason= : Reason for suppression (e.g. "user requested removal")}
                            {--type=unsub : Suppression type: unsub|bounce|complaint|manual}';

    protected $description = 'Add an email to the suppression list so it stops receiving outreach emails';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $reason = $this->option('reason') ?: 'manual admin action';
        $type = $this->option('type');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email: {$email}");
            return Command::FAILURE;
        }

        $allowedTypes = [
            Suppression::TYPE_UNSUB,
            Suppression::TYPE_BOUNCE,
            Suppression::TYPE_COMPLAINT,
            Suppression::TYPE_MANUAL,
        ];
        if (! in_array($type, $allowedTypes, true)) {
            $this->error("Invalid type '{$type}'. Allowed: " . implode(', ', $allowedTypes));
            return Command::FAILURE;
        }

        if (Suppression::isSuppressed($email)) {
            $this->warn("{$email} is already on the suppression list.");
        } else {
            Suppression::suppress(
                $email,
                $type,
                $reason,
                Suppression::SOURCE_ADMIN,
                ['command' => 'outreach:suppress', 'actor' => get_current_user()]
            );
            $this->info("Suppressed {$email} (type={$type}).");
        }

        $leadUpdated = OutreachLead::where('email', $email)
            ->whereNotIn('status', [OutreachLead::STATUS_LOST, OutreachLead::STATUS_PARTNER_ACTIVE])
            ->update([
                'status' => OutreachLead::STATUS_LOST,
                'next_followup_at' => null,
            ]);

        if ($leadUpdated > 0) {
            $this->info("Marked {$leadUpdated} outreach lead row(s) as lost.");
        } else {
            $this->line("No active outreach lead row to update.");
        }

        Log::info('Email manually suppressed via CLI', [
            'email' => $email,
            'type' => $type,
            'reason' => $reason,
            'leads_updated' => $leadUpdated,
        ]);

        return Command::SUCCESS;
    }
}

// CLAUDE-CHECKPOINT
