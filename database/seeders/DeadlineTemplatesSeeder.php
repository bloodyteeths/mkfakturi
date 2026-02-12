<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Deadline;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Deadline Templates Seeder (P8-02)
 *
 * Creates recurring deadline templates for all active companies.
 * Idempotent: skips companies that already have system deadlines.
 *
 * Standard MK accounting deadlines:
 * - VAT Return (ДДВ пријава): 25th of each month
 * - MPIN Filing (МПИН пријава): 10th of each month
 * - CIT Advance (Аконтација на данок на добивка): 15th of each month
 * - Annual Financial Statement (Годишна сметка): March 15th
 */
class DeadlineTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('deadlines')) {
            $this->command?->warn('Deadlines table does not exist yet. Skipping seeder.');

            return;
        }

        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->command?->info('No companies found. Skipping deadline templates seeder.');

            return;
        }

        $templates = $this->getTemplates();
        $created = 0;
        $skipped = 0;

        foreach ($companies as $company) {
            // Find partner_id for this company (if any)
            $partnerId = DB::table('partner_company_links')
                ->where('company_id', $company->id)
                ->where('is_active', true)
                ->value('partner_id');

            foreach ($templates as $template) {
                // Check if this type of recurring deadline already exists for this company
                $exists = Deadline::where('company_id', $company->id)
                    ->where('deadline_type', $template['deadline_type'])
                    ->where('is_recurring', true)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                $dueDate = $this->calculateNextDueDate($template['recurrence_rule']);

                Deadline::create([
                    'company_id' => $company->id,
                    'partner_id' => $partnerId,
                    'title' => $template['title'],
                    'title_mk' => $template['title_mk'],
                    'description' => $template['description'],
                    'deadline_type' => $template['deadline_type'],
                    'due_date' => $dueDate,
                    'status' => Deadline::STATUS_UPCOMING,
                    'reminder_days_before' => [7, 3, 1],
                    'is_recurring' => true,
                    'recurrence_rule' => $template['recurrence_rule'],
                ]);

                $created++;
            }
        }

        $this->command?->info("Deadline templates: {$created} created, {$skipped} skipped (already exist).");
        Log::info('DeadlineTemplatesSeeder completed', [
            'created' => $created,
            'skipped' => $skipped,
            'companies' => $companies->count(),
        ]);
    }

    /**
     * Get the standard MK accounting deadline templates.
     *
     * Based on Macedonian tax calendar (UJP / Управа за јавни приходи):
     * - MPIN filing: 10th of each month
     * - MPIN + CIT payment: 15th of each month
     * - VAT return filing: 25th of each month
     * - VAT payment: 30th of each month
     * - CIT annual return (electronic): March 15th
     * - Annual financial statements (Central Registry): March 31st
     * - Personal income tax confirmation: May 31st
     *
     * @return array<array<string, mixed>>
     */
    protected function getTemplates(): array
    {
        return [
            [
                'title' => 'MPIN Filing',
                'title_mk' => 'МПИН пријава',
                'description' => 'Monthly payroll tax filing (MPIN) — gross salaries, social contributions, withheld PIT. Submit electronically to UJP by the 10th.',
                'deadline_type' => Deadline::TYPE_MPIN,
                'recurrence_rule' => 'monthly_10',
            ],
            [
                'title' => 'MPIN Payment & CIT Advance',
                'title_mk' => 'Уплата МПИН и аконтација данок на добивка',
                'description' => 'Payment of social contributions + withheld PIT from MPIN, and monthly CIT advance (1/12 of prior year CIT). Due 15th of each month.',
                'deadline_type' => Deadline::TYPE_CIT,
                'recurrence_rule' => 'monthly_15',
            ],
            [
                'title' => 'VAT Return Filing',
                'title_mk' => 'ДДВ-04 пријава',
                'description' => 'Monthly VAT return (DDV-04) submission to UJP. Due on the 25th of the month following the tax period.',
                'deadline_type' => Deadline::TYPE_VAT,
                'recurrence_rule' => 'monthly_25',
            ],
            [
                'title' => 'CIT Annual Return',
                'title_mk' => 'Годишен данок на добивка (ДБ)',
                'description' => 'Annual Corporate Income Tax return (electronic filing). Due March 15th. Final CIT payment due within 30 days.',
                'deadline_type' => Deadline::TYPE_CIT,
                'recurrence_rule' => 'annual_03_15',
            ],
            [
                'title' => 'Annual Financial Statement',
                'title_mk' => 'Годишна сметка (Централен Регистар)',
                'description' => 'Annual financial statement submission to the Central Registry of North Macedonia. Due March 31st each year.',
                'deadline_type' => Deadline::TYPE_ANNUAL_FS,
                'recurrence_rule' => 'annual_03_31',
            ],
            [
                'title' => 'Personal Income Tax Confirmation',
                'title_mk' => 'Годишна даночна пријава (ПДД-ГДП)',
                'description' => 'Confirm or correct the pre-filled annual personal income tax return from UJP. Deadline May 31st — auto-confirmed if no action taken.',
                'deadline_type' => Deadline::TYPE_ANNUAL_FS,
                'recurrence_rule' => 'annual_05_31',
            ],
        ];
    }

    /**
     * Calculate the next due date based on a recurrence rule.
     *
     * @param  string  $rule  The recurrence rule (e.g. monthly_25, annual_03_15)
     */
    protected function calculateNextDueDate(string $rule): Carbon
    {
        $today = Carbon::today();

        if (str_starts_with($rule, 'monthly_')) {
            $day = (int) str_replace('monthly_', '', $rule);
            $candidate = Carbon::create($today->year, $today->month, min($day, $today->daysInMonth));

            // If the date has passed this month, move to next month
            if ($candidate->lte($today)) {
                $candidate = $candidate->addMonthNoOverflow();
                // Adjust day for the new month
                $candidate->day = min($day, $candidate->daysInMonth);
            }

            return $candidate;
        }

        if (str_starts_with($rule, 'annual_')) {
            // Format: annual_MM_DD
            $parts = explode('_', $rule);
            $month = (int) $parts[1];
            $day = (int) $parts[2];

            $candidate = Carbon::create($today->year, $month, $day);

            // If the date has passed this year, move to next year
            if ($candidate->lte($today)) {
                $candidate = Carbon::create($today->year + 1, $month, $day);
            }

            return $candidate;
        }

        // Fallback: 30 days from now
        return $today->addDays(30);
    }
}
// CLAUDE-CHECKPOINT
