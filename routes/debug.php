<?php

use App\Models\BankAccount;
use App\Models\BankToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Debug Routes - REMOVE IN PRODUCTION
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('debug')->group(function () {

    // Show recent Laravel logs
    Route::get('/logs', function () {
        $logFile = storage_path('logs/laravel.log');

        if (! file_exists($logFile)) {
            return response()->json(['error' => 'Log file not found']);
        }

        // Get last 200 lines
        $lines = [];
        $file = new \SplFileObject($logFile);
        $file->seek(PHP_INT_MAX);
        $total = $file->key();

        $start = max(0, $total - 200);
        $file->seek($start);

        while (! $file->eof()) {
            $lines[] = $file->fgets();
        }

        return response('<pre>'.htmlspecialchars(implode('', $lines)).'</pre>');
    });

    // Show bank tokens
    Route::get('/bank-tokens', function () {
        $tokens = BankToken::with('company')->latest()->get();

        return response()->json([
            'count' => $tokens->count(),
            'tokens' => $tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'bank_code' => $token->bank_code,
                    'company_id' => $token->company_id,
                    'company_name' => $token->company->name ?? 'N/A',
                    'expires_at' => $token->expires_at,
                    'created_at' => $token->created_at,
                    'has_access_token' => ! empty($token->access_token),
                    'has_refresh_token' => ! empty($token->refresh_token),
                ];
            }),
        ], JSON_PRETTY_PRINT);
    });

    // Show bank accounts
    Route::get('/bank-accounts', function () {
        $accounts = BankAccount::with('company')->latest()->get();

        return response()->json([
            'count' => $accounts->count(),
            'accounts' => $accounts->map(function ($account) {
                return [
                    'id' => $account->id,
                    'bank_code' => $account->bank_code,
                    'bank_name' => $account->bank_name,
                    'company_id' => $account->company_id,
                    'company_name' => $account->company->name ?? 'N/A',
                    'account_number' => $account->account_number,
                    'iban' => $account->iban,
                    'current_balance' => $account->current_balance,
                    'is_active' => $account->is_active,
                    'created_at' => $account->created_at,
                ];
            }),
        ], JSON_PRETTY_PRINT);
    });
});
