<?php

namespace App\Console\Commands;

use App\Mail\OnboardingNudgeMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send a personal onboarding "finish your setup" nudge (from Tamara) to real
 * companies that signed up but haven't completed setup, framed around the
 * e-Faktura mandate. ALWAYS dry-run first: `--dry-run` prints the exact
 * recipient list + chosen variant and sends nothing.
 *
 * Safeguards: excludes test/demo/internal/partner/portfolio accounts, requires
 * a valid resolved email, is idempotent (skips anyone already emailed via
 * email_logs), rate-limits between sends, and sends on the `broadcast` stream.
 */
class SendOnboardingNudge extends Command
{
    protected $signature = 'onboarding:send-setup-nudge
                            {--dry-run : List recipients + variant without sending}
                            {--limit=100 : Max emails to send this run}
                            {--sleep=8 : Seconds to wait between sends (deliverability)}
                            {--exclude= : Comma-separated emails to skip}';

    protected $description = 'Send the e-Faktura setup nudge to engaged-but-incomplete companies';

    /** Test / internal accounts never contacted. */
    private array $testNeedles = ['test', 'proba', 'aaaa', 'demo', 'sample', 'примерок'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');
        $sleep = (int) $this->option('sleep');
        $manualExclude = collect(explode(',', (string) $this->option('exclude')))
            ->map(fn ($e) => trim(mb_strtolower($e)))->filter()->all();

        $companies = DB::table('companies')
            ->whereNotIn('id', [2, 10])                      // super-admin's own companies
            ->where(function ($q) {
                $q->whereNull('subscription_tier')
                    ->orWhereNotIn('subscription_tier', ['accountant_basic']);
            })
            ->where(function ($q) {
                $q->whereNull('is_portfolio_managed')->orWhere('is_portfolio_managed', 0);
            })
            ->orderByDesc('id')
            ->get();

        $planned = [];
        $skipped = ['test' => 0, 'no_email' => 0, 'already_sent' => 0, 'complete' => 0, 'excluded' => 0, 'partner' => 0];

        foreach ($companies as $c) {
            $nameLc = mb_strtolower($c->name ?? '');

            foreach ($this->testNeedles as $needle) {
                if (mb_strpos($nameLc, $needle) !== false) {
                    $skipped['test']++;
                    continue 2;
                }
            }

            // Setup state
            $vat = ! empty($c->vat_number) || ! empty($c->vat_id);
            $logo = ! empty($c->logo);
            $bank = DB::table('bank_accounts')->where('company_id', $c->id)->exists();
            $invoices = DB::table('invoices')->where('company_id', $c->id)->count();
            $customers = DB::table('customers')->where('company_id', $c->id)->count();
            $items = DB::table('items')->where('company_id', $c->id)->count();

            // Must be engaged (did something) AND incomplete (something still missing)
            $engaged = $invoices || $customers || $items;
            $incomplete = ! $vat || ! $logo || ! $bank || $invoices === 0;
            if (! $engaged) {
                continue;
            }
            if (! $incomplete) {
                $skipped['complete']++;
                continue;
            }

            [$email, $contact, $isPartner] = $this->resolveContact($c);
            if ($isPartner) {
                $skipped['partner']++;
                continue;
            }
            if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $skipped['no_email']++;
                continue;
            }
            if (in_array(mb_strtolower($email), $manualExclude, true)) {
                $skipped['excluded']++;
                continue;
            }

            // Idempotency — never email the same person twice
            $alreadySent = DB::table('email_logs')
                ->where('mailable_type', OnboardingNudgeMail::class)
                ->where('to', $email)
                ->exists();
            if ($alreadySent) {
                $skipped['already_sent']++;
                continue;
            }

            $variant = $invoices === 0 ? 'first_invoice' : 'complete_profile';

            $planned[] = [
                'company_id' => $c->id,
                'name' => $c->name,
                'email' => $email,
                'contact' => $contact,
                'variant' => $variant,
            ];
        }

        // Report
        $this->info(sprintf('Eligible recipients: %d', count($planned)));
        $this->line('Skipped — '.collect($skipped)->map(fn ($v, $k) => "$k:$v")->implode(', '));
        $this->newLine();

        $this->table(
            ['#', 'Company', 'Email', 'Variant'],
            collect($planned)->map(fn ($p) => [$p['company_id'], mb_substr($p['name'], 0, 30), $p['email'], $p['variant']])->all()
        );

        if ($dryRun) {
            $this->warn('DRY RUN — nothing was sent. Re-run without --dry-run to send.');

            return self::SUCCESS;
        }

        if (empty($planned)) {
            $this->info('No eligible recipients. Nothing to send.');

            return self::SUCCESS;
        }

        if (! $this->confirm(sprintf('Send %d live email(s) via the broadcast stream?', min($limit, count($planned))), false)) {
            $this->warn('Aborted.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($planned as $p) {
            if ($sent >= $limit) {
                break;
            }
            try {
                Mail::to($p['email'])->send(new OnboardingNudgeMail($p['name'], $p['variant']));

                DB::table('email_logs')->insert([
                    'from' => env('MAIL_PARTNER_FROM_ADDRESS', 'partneri@facturino.mk'),
                    'to' => $p['email'],
                    'subject' => 'Пред е-Фактура да стане задолжителна — да го завршиме поставувањето заедно',
                    'body' => 'onboarding setup nudge ('.$p['variant'].')',
                    'mailable_type' => OnboardingNudgeMail::class,
                    'mailable_id' => (string) $p['company_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $sent++;
                $this->line("  ✓ sent to {$p['email']} ({$p['name']})");
                Log::info('Onboarding nudge sent', ['email' => $p['email'], 'company_id' => $p['company_id'], 'variant' => $p['variant']]);

                if ($sleep > 0 && $sent < min($limit, count($planned))) {
                    sleep($sleep);
                }
            } catch (\Throwable $e) {
                $this->error("  ✗ failed for {$p['email']}: {$e->getMessage()}");
                Log::error('Onboarding nudge failed', ['email' => $p['email'], 'error' => $e->getMessage()]);
            }
        }

        $this->newLine();
        $this->info("Done. Sent {$sent} email(s).");

        return self::SUCCESS;
    }

    /**
     * Resolve the best contact email for a company.
     *
     * @return array{0: ?string, 1: ?string, 2: bool} [email, contactName, isPartner]
     */
    private function resolveContact(object $c): array
    {
        // Primary: the user_company pivot links users to companies. Prefer the
        // legal representative, then the lowest user id.
        $user = DB::table('user_company')
            ->join('users', 'users.id', '=', 'user_company.user_id')
            ->where('user_company.company_id', $c->id)
            ->orderByDesc('user_company.is_legal_representative')
            ->orderBy('users.id')
            ->select('users.email', 'users.name', 'users.role')
            ->first();

        // Fallback: explicit owner.
        if (! $user && ! empty($c->owner_id)) {
            $user = DB::table('users')->where('id', $c->owner_id)->select('email', 'name', 'role')->first();
        }

        if (! $user) {
            return [null, null, false];
        }

        // Never nudge partners/accountants/super-admins through this channel.
        $isPartner = in_array($user->role ?? '', ['partner', 'super admin'], true);

        return [$user->email ?? null, $user->name ?? null, $isPartner];
    }
}
