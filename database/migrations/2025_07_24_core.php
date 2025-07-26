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
        // Partners table - for accounting partners/bookkeepers
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('tax_id')->nullable(); // ЕДБ (Единствен даночен број)
            $table->string('registration_number')->nullable(); // ЕМБС (Единствен матичен број на субјектот)
            $table->string('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(0); // Default commission percentage
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Bank accounts table - for company bank accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_name');
            $table->string('account_number');
            $table->string('iban')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('bank_name');
            $table->string('bank_code')->nullable(); // Bank identification code in Macedonia
            $table->string('branch')->nullable();
            $table->string('account_type')->default('business'); // business, savings, etc.
            $table->unsignedInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Commissions table - for tracking partner commissions
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedInteger('invoice_id')->nullable();
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->foreign('payment_id')->references('id')->on('payments')->onDelete('set null');
            $table->string('commission_type'); // invoice, payment, monthly, custom
            $table->decimal('base_amount', 15, 2); // Amount on which commission is calculated
            $table->decimal('commission_rate', 5, 2); // Commission percentage for this transaction
            $table->decimal('commission_amount', 15, 2); // Calculated commission amount
            $table->unsignedInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies');
            $table->string('status')->default('pending'); // pending, approved, paid, cancelled
            $table->date('period_start')->nullable(); // For monthly commissions
            $table->date('period_end')->nullable(); // For monthly commissions
            $table->date('paid_date')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('creator_id')->nullable();
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        // Add indexes for better performance
        Schema::table('partners', function (Blueprint $table) {
            $table->index('email');
            $table->index('is_active');
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->index(['company_id', 'is_active']);
            $table->index('account_number');
        });

        Schema::table('commissions', function (Blueprint $table) {
            $table->index(['partner_id', 'status']);
            $table->index(['company_id', 'created_at']);
            $table->index('commission_type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('partners');
    }
};
