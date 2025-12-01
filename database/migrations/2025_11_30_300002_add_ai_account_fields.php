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
        // Add AI account suggestion fields to invoices table (if not already exists)
        if (! Schema::hasColumn('invoices', 'suggested_debit_account_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('suggested_debit_account_id')->nullable()->after('id');
                $table->unsignedBigInteger('suggested_credit_account_id')->nullable()->after('suggested_debit_account_id');
                $table->unsignedBigInteger('confirmed_debit_account_id')->nullable()->after('suggested_credit_account_id');
                $table->unsignedBigInteger('confirmed_credit_account_id')->nullable()->after('confirmed_debit_account_id');
                $table->timestamp('account_confirmed_at')->nullable()->after('confirmed_credit_account_id');
                $table->unsignedInteger('account_confirmed_by')->nullable()->after('account_confirmed_at'); // users.id is unsigned int

                $table->foreign('suggested_debit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('suggested_credit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('confirmed_debit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('confirmed_credit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('account_confirmed_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }

        // Add AI account suggestion fields to expenses table (if not already exists)
        if (! Schema::hasColumn('expenses', 'suggested_debit_account_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedBigInteger('suggested_debit_account_id')->nullable()->after('id');
                $table->unsignedBigInteger('suggested_credit_account_id')->nullable()->after('suggested_debit_account_id');
                $table->unsignedBigInteger('confirmed_debit_account_id')->nullable()->after('suggested_credit_account_id');
                $table->unsignedBigInteger('confirmed_credit_account_id')->nullable()->after('confirmed_debit_account_id');
                $table->timestamp('account_confirmed_at')->nullable()->after('confirmed_credit_account_id');
                $table->unsignedInteger('account_confirmed_by')->nullable()->after('account_confirmed_at'); // users.id is unsigned int

                $table->foreign('suggested_debit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('suggested_credit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('confirmed_debit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('confirmed_credit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('account_confirmed_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }

        // Add AI account suggestion fields to payments table (if not already exists)
        if (! Schema::hasColumn('payments', 'suggested_debit_account_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('suggested_debit_account_id')->nullable()->after('id');
                $table->unsignedBigInteger('suggested_credit_account_id')->nullable()->after('suggested_debit_account_id');
                $table->unsignedBigInteger('confirmed_debit_account_id')->nullable()->after('suggested_credit_account_id');
                $table->unsignedBigInteger('confirmed_credit_account_id')->nullable()->after('confirmed_debit_account_id');
                $table->timestamp('account_confirmed_at')->nullable()->after('confirmed_credit_account_id');
                $table->unsignedInteger('account_confirmed_by')->nullable()->after('account_confirmed_at'); // users.id is unsigned int

                $table->foreign('suggested_debit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('suggested_credit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('confirmed_debit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('confirmed_credit_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->onDelete('set null');

                $table->foreign('account_confirmed_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['suggested_debit_account_id']);
            $table->dropForeign(['suggested_credit_account_id']);
            $table->dropForeign(['confirmed_debit_account_id']);
            $table->dropForeign(['confirmed_credit_account_id']);
            $table->dropForeign(['account_confirmed_by']);

            $table->dropColumn([
                'suggested_debit_account_id',
                'suggested_credit_account_id',
                'confirmed_debit_account_id',
                'confirmed_credit_account_id',
                'account_confirmed_at',
                'account_confirmed_by',
            ]);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['suggested_debit_account_id']);
            $table->dropForeign(['suggested_credit_account_id']);
            $table->dropForeign(['confirmed_debit_account_id']);
            $table->dropForeign(['confirmed_credit_account_id']);
            $table->dropForeign(['account_confirmed_by']);

            $table->dropColumn([
                'suggested_debit_account_id',
                'suggested_credit_account_id',
                'confirmed_debit_account_id',
                'confirmed_credit_account_id',
                'account_confirmed_at',
                'account_confirmed_by',
            ]);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['suggested_debit_account_id']);
            $table->dropForeign(['suggested_credit_account_id']);
            $table->dropForeign(['confirmed_debit_account_id']);
            $table->dropForeign(['confirmed_credit_account_id']);
            $table->dropForeign(['account_confirmed_by']);

            $table->dropColumn([
                'suggested_debit_account_id',
                'suggested_credit_account_id',
                'confirmed_debit_account_id',
                'confirmed_credit_account_id',
                'account_confirmed_at',
                'account_confirmed_by',
            ]);
        });
    }
};
// CLAUDE-CHECKPOINT
