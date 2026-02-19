<?php

namespace Modules\Mk\Bitrix\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Bitrix\Mail\DirectoryListingMail;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Services\PostmarkOutreachService;

/**
 * DirectoryOutreachCommand
 *
 * Sends outreach emails to business directories, accountant associations,
 * and tech blogs requesting Facturino be listed/reviewed.
 *
 * Usage:
 *   php artisan outreach:directory --dry-run
 *   php artisan outreach:directory
 *   php artisan outreach:directory --limit=5
 */
class DirectoryOutreachCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'outreach:directory
                            {--limit=10 : Maximum emails to send in this batch}
                            {--dry-run : Show what would be sent without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send outreach emails to business directories and accountant associations to get Facturino listed';

    /**
     * Macedonian business directories and relevant websites to target.
     *
     * @var array<array{name: string, email: string, contact: string, type: string}>
     */
    protected array $directories = [
        // Accountant associations (VERIFIED)
        [
            'name' => 'ИСОС (Институт на сметководители)',
            'email' => 'info@isos.com.mk',
            'contact' => 'there',
            'type' => 'association',
        ],
        [
            'name' => 'ИОРРМ (Комора на овластени ревизори)',
            'email' => 'gensec@iorrm.org.mk',
            'contact' => 'there',
            'type' => 'association',
        ],
        [
            'name' => 'Стопанска комора на Македонија',
            'email' => 'ic@mchamber.mk',
            'contact' => 'there',
            'type' => 'association',
        ],
        // Business directories (VERIFIED)
        [
            'name' => 'Smetkovoditel.mk',
            'email' => 'contact@smetkovoditel.mk',
            'contact' => 'there',
            'type' => 'directory',
        ],
        // Tech & business media (VERIFIED / HIGH-CONFIDENCE)
        [
            'name' => 'IT.mk (IWM Network)',
            'email' => 'contact@iwmnetwork.com',
            'contact' => 'there',
            'type' => 'media',
        ],
        [
            'name' => 'Kapital.mk',
            'email' => 'redakcija@kapital.mk',
            'contact' => 'there',
            'type' => 'media',
        ],
        [
            'name' => 'Biznisinfo.mk',
            'email' => 'contact@bi.mk',
            'contact' => 'there',
            'type' => 'media',
        ],
        // Software review sites (catalog team emails)
        [
            'name' => 'Capterra / GetApp / Software Advice (Gartner)',
            'email' => 'gdmcatalogteam@gartner.com',
            'contact' => 'Catalog Team',
            'type' => 'review',
        ],
        // Accountant/legal portals (VERIFIED)
        [
            'name' => 'Pravdiko.mk',
            'email' => 'georgiev.aleksandar@pravdiko.mk',
            'contact' => 'Александар Георгиев',
            'type' => 'portal',
        ],
    ];

    /**
     * Execute the console command.
     *
     * @param PostmarkOutreachService $postmarkService
     * @return int
     */
    public function handle(PostmarkOutreachService $postmarkService): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = $this->option('dry-run');

        $this->info('Directory Listing Outreach');
        $this->line('==========================');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails will be sent');
            $this->newLine();
        }

        // Check rate limits
        if (!$dryRun && !$postmarkService->isWithinDailyLimit()) {
            $this->error('Daily send limit reached. Try again tomorrow.');
            return self::FAILURE;
        }

        // Filter out already-contacted directories
        $alreadySent = OutreachSend::where('template_key', 'directory_listing')
            ->pluck('email')
            ->toArray();

        $eligible = collect($this->directories)
            ->filter(fn ($dir) => !in_array($dir['email'], $alreadySent))
            ->take($limit)
            ->values();

        if ($eligible->isEmpty()) {
            $this->info('All directories have already been contacted.');
            return self::SUCCESS;
        }

        $this->info("Found {$eligible->count()} directories to contact");
        $this->newLine();

        // Display table
        $this->table(
            ['Name', 'Email', 'Type'],
            $eligible->map(fn ($d) => [$d['name'], $d['email'], $d['type']])->toArray()
        );
        $this->newLine();

        $stats = ['sent' => 0, 'errors' => 0];

        foreach ($eligible as $index => $directory) {
            if ($dryRun) {
                $this->line("  [DRY-RUN] Would send to: {$directory['email']} ({$directory['name']})");
                $stats['sent']++;
                continue;
            }

            // Jitter between sends (30-60s)
            if ($index > 0) {
                $jitter = rand(30, 60);
                sleep($jitter);
            }

            try {
                $websiteUrl = 'https://facturino.mk';
                $unsubscribeUrl = config('app.url') . '/unsubscribe';

                $mailable = new DirectoryListingMail(
                    $directory['name'],
                    $directory['contact'],
                    $websiteUrl,
                    $unsubscribeUrl
                );

                // Set message stream for outreach
                $mailable->withSymfonyMessage(function ($message) {
                    $message->getHeaders()->addTextHeader(
                        'X-PM-Message-Stream',
                        'outreach'
                    );
                });

                Mail::to($directory['email'])->send($mailable);

                // Record the send
                OutreachSend::create([
                    'email' => $directory['email'],
                    'template_key' => 'directory_listing',
                    'postmark_message_id' => 'pm-dir-' . uniqid(),
                    'status' => OutreachSend::STATUS_SENT,
                    'sent_at' => now(),
                ]);

                $this->info("  Sent to {$directory['email']} ({$directory['name']})");
                $stats['sent']++;

            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Failed to send directory outreach', [
                    'email' => $directory['email'],
                    'directory' => $directory['name'],
                    'error' => $e->getMessage(),
                ]);
                $this->error("  Error for {$directory['email']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->line('Results');
        $this->line('-------');
        $this->line("  Sent: {$stats['sent']}");
        $this->line("  Errors: {$stats['errors']}");

        return self::SUCCESS;
    }
}
