<?php

namespace Modules\Mk\Bitrix\Services;

use App\Models\Company;
use App\Models\Partner;
use App\Models\WelcomeSend;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Bitrix\Mail\Welcome\CompanyWelcome1Mail;
use Modules\Mk\Bitrix\Mail\Welcome\CompanyWelcome2Mail;
use Modules\Mk\Bitrix\Mail\Welcome\CompanyWelcome3Mail;
use Modules\Mk\Bitrix\Mail\Welcome\CompanyWelcome4Mail;
use Modules\Mk\Bitrix\Mail\Welcome\CompanyWelcome5Mail;
use Modules\Mk\Bitrix\Mail\Welcome\PartnerWelcome1Mail;
use Modules\Mk\Bitrix\Mail\Welcome\PartnerWelcome2Mail;
use Modules\Mk\Bitrix\Mail\Welcome\PartnerWelcome3Mail;
use Modules\Mk\Bitrix\Mail\Welcome\PartnerWelcome4Mail;
use Modules\Mk\Bitrix\Mail\Welcome\PartnerWelcome5Mail;

// CLAUDE-CHECKPOINT

/**
 * WelcomeEmailService
 *
 * Manages the welcome drip email series for companies and partners.
 * Sends Day 0 immediately after signup, tracks all 5 emails in welcome_sends,
 * and processes follow-ups via cron.
 */
class WelcomeEmailService
{
    /**
     * Drip schedule: template_key => days after signup.
     */
    protected array $companySchedule = [
        'company_1' => 0,
        'company_2' => 2,
        'company_3' => 5,
        'company_4' => 10,
        'company_5' => 14,
    ];

    protected array $partnerSchedule = [
        'partner_1' => 0,
        'partner_2' => 2,
        'partner_3' => 5,
        'partner_4' => 10,
        'partner_5' => 14,
    ];

    /**
     * Template key → Mailable class mapping.
     */
    protected array $mailableMap = [
        'company_1' => CompanyWelcome1Mail::class,
        'company_2' => CompanyWelcome2Mail::class,
        'company_3' => CompanyWelcome3Mail::class,
        'company_4' => CompanyWelcome4Mail::class,
        'company_5' => CompanyWelcome5Mail::class,
        'partner_1' => PartnerWelcome1Mail::class,
        'partner_2' => PartnerWelcome2Mail::class,
        'partner_3' => PartnerWelcome3Mail::class,
        'partner_4' => PartnerWelcome4Mail::class,
        'partner_5' => PartnerWelcome5Mail::class,
    ];

    /**
     * Send Day 0 welcome email and queue all follow-ups for a company.
     */
    public function sendCompanyWelcome(Company $company, string $email, string $name): void
    {
        // Check if already sent (idempotent)
        if (WelcomeSend::where('sendable_type', Company::class)
            ->where('sendable_id', $company->id)
            ->where('template_key', 'company_1')
            ->exists()) {
            return;
        }

        // Create tracking records for all 5 emails
        foreach ($this->companySchedule as $key => $days) {
            WelcomeSend::create([
                'sendable_type' => Company::class,
                'sendable_id' => $company->id,
                'email' => $email,
                'template_key' => $key,
                'status' => $days === 0 ? 'queued' : 'queued',
            ]);
        }

        // Send Day 0 immediately
        $this->sendEmail('company_1', $email, $name);
    }

    /**
     * Send Day 0 welcome email and queue all follow-ups for a partner.
     */
    public function sendPartnerWelcome(Partner $partner): void
    {
        // Check if already sent (idempotent)
        if (WelcomeSend::where('sendable_type', Partner::class)
            ->where('sendable_id', $partner->id)
            ->where('template_key', 'partner_1')
            ->exists()) {
            return;
        }

        // Create tracking records for all 5 emails
        foreach ($this->partnerSchedule as $key => $days) {
            WelcomeSend::create([
                'sendable_type' => Partner::class,
                'sendable_id' => $partner->id,
                'email' => $partner->email,
                'template_key' => $key,
                'status' => 'queued',
            ]);
        }

        // Send Day 0 immediately
        $this->sendEmail('partner_1', $partner->email, $partner->name);
    }

    /**
     * Process drip emails — called by cron.
     * Finds queued emails that are due based on the schedule offset
     * relative to the Day 0 record's created_at.
     *
     * @return int Number of emails sent
     */
    public function processDrip(bool $dryRun = false): int
    {
        $sent = 0;

        // Process company drip
        $sent += $this->processDripForSchedule($this->companySchedule, Company::class, $dryRun);

        // Process partner drip
        $sent += $this->processDripForSchedule($this->partnerSchedule, Partner::class, $dryRun);

        return $sent;
    }

    /**
     * Process drip for a specific schedule (company or partner).
     */
    protected function processDripForSchedule(array $schedule, string $sendableType, bool $dryRun): int
    {
        $sent = 0;
        $firstKey = array_key_first($schedule);

        foreach ($schedule as $templateKey => $daysAfterSignup) {
            // Skip Day 0 — already sent at signup
            if ($daysAfterSignup === 0) {
                continue;
            }

            // Find queued sends for this template
            $queuedSends = WelcomeSend::where('sendable_type', $sendableType)
                ->where('template_key', $templateKey)
                ->where('status', 'queued')
                ->get();

            foreach ($queuedSends as $send) {
                // Find the Day 0 record to calculate timing
                $day0 = WelcomeSend::where('sendable_type', $sendableType)
                    ->where('sendable_id', $send->sendable_id)
                    ->where('template_key', $firstKey)
                    ->first();

                if (! $day0) {
                    continue;
                }

                // Check if enough days have passed since signup
                $signupDate = $day0->created_at;
                $dueDate = $signupDate->copy()->addDays($daysAfterSignup);

                if (now()->lt($dueDate)) {
                    continue; // Not yet due
                }

                if ($dryRun) {
                    Log::info('[DRY RUN] Welcome drip due', [
                        'template_key' => $templateKey,
                        'email' => $send->email,
                        'sendable_type' => $sendableType,
                        'sendable_id' => $send->sendable_id,
                        'due_date' => $dueDate->toDateTimeString(),
                    ]);
                    $sent++;
                    continue;
                }

                // Resolve the recipient name
                $name = $this->resolveName($send);

                $this->sendEmail($templateKey, $send->email, $name);
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send a single email and update tracking.
     */
    protected function sendEmail(string $templateKey, string $email, string $name): void
    {
        $mailableClass = $this->mailableMap[$templateKey] ?? null;

        if (! $mailableClass) {
            Log::error('Unknown welcome template key', ['template_key' => $templateKey]);

            return;
        }

        try {
            $mailable = new $mailableClass($name);
            Mail::to($email)->locale('mk')->send($mailable);

            // Mark as sent
            WelcomeSend::where('email', $email)
                ->where('template_key', $templateKey)
                ->where('status', 'queued')
                ->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

            Log::info('Welcome email sent', [
                'template_key' => $templateKey,
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            // Mark as failed
            WelcomeSend::where('email', $email)
                ->where('template_key', $templateKey)
                ->where('status', 'queued')
                ->update(['status' => 'failed']);

            Log::error('Welcome email failed', [
                'template_key' => $templateKey,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve name from the sendable entity.
     */
    protected function resolveName(WelcomeSend $send): string
    {
        if ($send->sendable_type === Company::class) {
            $company = Company::find($send->sendable_id);
            if ($company && $company->owner) {
                return $company->owner->name;
            }

            return $company->name ?? '';
        }

        if ($send->sendable_type === Partner::class) {
            $partner = Partner::find($send->sendable_id);

            return $partner->name ?? '';
        }

        return '';
    }
}
