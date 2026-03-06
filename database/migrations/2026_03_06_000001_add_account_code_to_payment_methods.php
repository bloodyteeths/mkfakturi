<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add account_code to payment_methods so each method maps to a GL account.
     * Macedonian chart: 100=Готовина, 102=Жиро-сметка, 105=Други парични средства
     */
    public function up(): void
    {
        if (! Schema::hasColumn('payment_methods', 'account_code')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('account_code', 10)->nullable()->after('name');
            });
        }

        // Backfill existing payment methods by matching common names (MK + EN)
        $mappings = [
            // Cash → 100
            '100' => ['cash', 'готовина', 'каса', 'благајна'],
            // Bank transfer → 102
            '102' => ['bank transfer', 'bank', 'transfer', 'wire', 'банкарски трансфер', 'вирман', 'жиро'],
            // Credit card → 102 (card settlements go to bank)
            '102_card' => ['credit card', 'кредитна картичка', 'картичка', 'card'],
            // Check → 100
            '100_check' => ['check', 'чек'],
        ];

        foreach ($mappings as $codeKey => $keywords) {
            $code = explode('_', $codeKey)[0]; // Strip suffix like _card, _check
            foreach ($keywords as $keyword) {
                DB::table('payment_methods')
                    ->whereNull('account_code')
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . $keyword . '%'])
                    ->update(['account_code' => $code]);
            }
        }

        // Set remaining unmapped methods to 100 (cash fallback)
        DB::table('payment_methods')
            ->whereNull('account_code')
            ->update(['account_code' => '100']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('payment_methods', 'account_code')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('account_code');
            });
        }
    }
};
