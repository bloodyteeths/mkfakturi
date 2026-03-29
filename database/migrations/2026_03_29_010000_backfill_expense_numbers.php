<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill expense_number for existing expenses that don't have one
        $companies = DB::table('expenses')
            ->whereNull('expense_number')
            ->distinct()
            ->pluck('company_id');

        foreach ($companies as $companyId) {
            // Get the highest existing number for this company
            $lastNumber = DB::table('expenses')
                ->where('company_id', $companyId)
                ->whereNotNull('expense_number')
                ->orderByRaw("CAST(REPLACE(expense_number, 'EXP-', '') AS UNSIGNED) DESC")
                ->value('expense_number');

            $nextNum = 1;
            if ($lastNumber) {
                $nextNum = (int) str_replace('EXP-', '', $lastNumber) + 1;
            }

            // Get all expenses without a number, ordered by date then id
            $expenses = DB::table('expenses')
                ->where('company_id', $companyId)
                ->whereNull('expense_number')
                ->orderBy('expense_date')
                ->orderBy('id')
                ->pluck('id');

            foreach ($expenses as $expenseId) {
                DB::table('expenses')
                    ->where('id', $expenseId)
                    ->update([
                        'expense_number' => 'EXP-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT),
                    ]);
                $nextNum++;
            }
        }

        // Also backfill status for expenses that have null status
        DB::table('expenses')
            ->whereNull('status')
            ->update(['status' => 'draft']);
    }

    public function down(): void
    {
        // Cannot safely reverse — would lose numbering
    }
};
