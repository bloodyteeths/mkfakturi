<?php

namespace App\Console\Commands;

use App\Models\Deadline;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Generate Recurring Deadlines Command (P8-02)
 *
 * Generates next-period instances for all recurring deadline templates.
 * Runs monthly on the 1st at 00:00 via scheduler.
 *
 * Recurrence rules supported:
 * - monthly_DD  (e.g. monthly_25 → 25th of next month)
 * - monthly_DD_suffix (e.g. monthly_25_payment → 25th of next month)
 * - annual_MM_DD (e.g. annual_03_15 → March 15th next year)
 * - annual_MM_DD_suffix (e.g. annual_01_31_firmarina → Jan 31st next year)
 */
class GenerateRecurringDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadlines:generate-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate next month recurring deadline instances';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating recurring deadline instances...');

        if (! Schema::hasTable('deadlines')) {
            $this->warn('Deadlines table does not exist yet. Skipping.');

            return 0;
        }

        // Get all unique recurring deadline templates (one per company+type)
        $templates = Deadline::where('is_recurring', true)
            ->select('company_id', 'partner_id', 'title', 'title_mk', 'description', 'deadline_type', 'recurrence_rule', 'reminder_days_before')
            ->groupBy('company_id', 'partner_id', 'title', 'title_mk', 'description', 'deadline_type', 'recurrence_rule', 'reminder_days_before')
            ->get();

        if ($templates->isEmpty()) {
            $this->info('No recurring deadline templates found.');

            return 0;
        }

        $created = 0;
        $skipped = 0;

        foreach ($templates as $template) {
            try {
                $nextDueDate = $this->calculateNextDueDate($template->recurrence_rule);

                if (! $nextDueDate) {
                    $this->warn("Unknown recurrence rule: {$template->recurrence_rule} for company {$template->company_id}");
                    $skipped++;

                    continue;
                }

                // Check if a deadline for this period already exists (idempotent)
                $exists = Deadline::where('company_id', $template->company_id)
                    ->where('recurrence_rule', $template->recurrence_rule)
                    ->where('due_date', $nextDueDate->toDateString())
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Deadline::create([
                    'company_id' => $template->company_id,
                    'partner_id' => $template->partner_id,
                    'title' => $template->title,
                    'title_mk' => $template->title_mk,
                    'description' => $template->description,
                    'deadline_type' => $template->deadline_type,
                    'due_date' => $nextDueDate,
                    'status' => Deadline::STATUS_UPCOMING,
                    'reminder_days_before' => $template->reminder_days_before,
                    'is_recurring' => true,
                    'recurrence_rule' => $template->recurrence_rule,
                ]);

                $created++;
            } catch (\Exception $e) {
                $this->error("Failed to generate deadline for company {$template->company_id}: {$e->getMessage()}");
                Log::error('Failed to generate recurring deadline', [
                    'company_id' => $template->company_id,
                    'deadline_type' => $template->deadline_type,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Recurring deadlines: {$created} created, {$skipped} skipped.");
        Log::info('GenerateRecurringDeadlines completed', [
            'created' => $created,
            'skipped' => $skipped,
        ]);

        return 0;
    }

    /**
     * Calculate the next due date based on a recurrence rule.
     *
     * @param  string  $rule  The recurrence rule
     * @return Carbon|null The next due date, or null if the rule is invalid
     */
    protected function calculateNextDueDate(string $rule): ?Carbon
    {
        $today = Carbon::today();

        if (str_starts_with($rule, 'monthly_')) {
            // Extract day from rules like monthly_25, monthly_25_payment, monthly_25_books
            preg_match('/^monthly_(\d+)/', $rule, $matches);
            $day = (int) ($matches[1] ?? 1);

            // Generate for next month
            $nextMonth = $today->copy()->addMonthNoOverflow();
            $daysInNextMonth = $nextMonth->daysInMonth;

            return Carbon::create(
                $nextMonth->year,
                $nextMonth->month,
                min($day, $daysInNextMonth)
            );
        }

        if (str_starts_with($rule, 'annual_')) {
            // Format: annual_MM_DD or annual_MM_DD_suffix
            $parts = explode('_', $rule);

            if (count($parts) < 3) {
                return null;
            }

            $month = (int) $parts[1];
            $day = (int) $parts[2];

            // Check if annual date has passed this year
            $candidate = Carbon::create($today->year, $month, $day);

            if ($candidate->lte($today)) {
                $candidate = Carbon::create($today->year + 1, $month, $day);
            }

            return $candidate;
        }

        return null;
    }
}
// CLAUDE-CHECKPOINT
