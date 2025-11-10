<?php

namespace App\Console\Commands;

use App\Models\RecurringExpense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessRecurringExpenses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-expenses:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process recurring expenses that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing recurring expenses...');

        $dueExpenses = RecurringExpense::dueForProcessing()->get();

        if ($dueExpenses->isEmpty()) {
            $this->info('No recurring expenses due for processing.');
            return 0;
        }

        $this->info("Found {$dueExpenses->count()} recurring expenses to process.");

        $processed = 0;
        $failed = 0;

        foreach ($dueExpenses as $recurringExpense) {
            try {
                $expense = $recurringExpense->generateExpense();

                $this->info("Created expense ID {$expense->id} from recurring expense ID {$recurringExpense->id}");
                $processed++;
            } catch (\Exception $e) {
                $this->error("Failed to process recurring expense ID {$recurringExpense->id}: {$e->getMessage()}");
                Log::error('Failed to process recurring expense', [
                    'recurring_expense_id' => $recurringExpense->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $failed++;
            }
        }

        $this->info("Processed: {$processed}, Failed: {$failed}");

        return 0;
    }
}
// CLAUDE-CHECKPOINT
