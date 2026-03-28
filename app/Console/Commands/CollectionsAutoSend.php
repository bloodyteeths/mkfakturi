<?php

namespace App\Console\Commands;

use App\Models\CompanySetting;
use Illuminate\Console\Command;
use Modules\Mk\Models\ReminderTemplate;
use Modules\Mk\Services\CollectionService;

class CollectionsAutoSend extends Command
{
    protected $signature = 'collections:auto-send';
    protected $description = 'Automatically send collection reminders for overdue invoices based on active auto_send templates';

    public function handle(): int
    {
        $this->info('Starting auto-send collection reminders...');

        $service = app(CollectionService::class);

        // Get all companies with active auto_send templates
        $templates = ReminderTemplate::where('is_active', true)
            ->where('auto_send', true)
            ->get()
            ->groupBy('company_id');

        $totalSent = 0;
        $totalFailed = 0;

        foreach ($templates as $companyId => $companyTemplates) {
            $this->info("Processing company {$companyId}...");

            try {
                $result = $service->getOverdueInvoices($companyId, ['per_page' => 1000]);
                $invoices = $result['invoices'];

                foreach ($invoices as $invoice) {
                    // Check if this invoice matches any auto_send template based on days_overdue
                    $matchingTemplate = $companyTemplates->first(function ($tpl) use ($invoice) {
                        return $tpl->escalation_level === $invoice['escalation_level']
                            && $invoice['days_overdue'] >= $tpl->days_after_due;
                    });

                    if (!$matchingTemplate) continue;
                    if (!$invoice['can_send']) continue; // Skip if cooldown active
                    if (!$invoice['customer_email']) continue; // Skip if no email

                    try {
                        $service->sendReminder(
                            $companyId,
                            $invoice['id'],
                            $invoice['escalation_level'],
                            $invoice['customer_email'],
                            true // attach opomena
                        );
                        $totalSent++;
                        $this->line("  Sent {$invoice['escalation_level']} reminder for invoice {$invoice['invoice_number']} to {$invoice['customer_email']}");
                    } catch (\Exception $e) {
                        $totalFailed++;
                        $this->warn("  Failed for invoice {$invoice['invoice_number']}: {$e->getMessage()}");
                    }
                }
            } catch (\Exception $e) {
                $this->error("Error processing company {$companyId}: {$e->getMessage()}");
            }
        }

        $this->info("Done. Sent: {$totalSent}, Failed: {$totalFailed}");

        return self::SUCCESS;
    }
}

// CLAUDE-CHECKPOINT
