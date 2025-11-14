<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add account_type to distinguish between accountants and companies
            $table->enum('account_type', ['accountant', 'company', 'sales_rep', 'admin'])
                ->default('company')
                ->after('role')
                ->index('idx_users_account_type');

            // KYC status for accountants who want to earn commissions
            $table->enum('kyc_status', ['pending', 'verified', 'rejected'])
                ->default('pending')
                ->after('account_type')
                ->index('idx_users_kyc_status');

            // Partner tier for accountants (free affiliate vs paid tier)
            $table->enum('partner_tier', ['free', 'plus'])
                ->default('free')
                ->after('kyc_status')
                ->comment('For accountants only: free = affiliate, plus = paid accountant tier');
        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_account_type');
            $table->dropIndex('idx_users_kyc_status');
            $table->dropColumn(['account_type', 'kyc_status', 'partner_tier']);
        });
    }
};
// CLAUDE-CHECKPOINT
