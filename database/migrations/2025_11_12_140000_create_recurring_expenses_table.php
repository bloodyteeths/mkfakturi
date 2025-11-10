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
        if (Schema::hasTable('recurring_expenses')) {
            return;
        }

        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Expense details
            $table->unsignedBigInteger('expense_category_id');
            $table->foreign('expense_category_id')->references('id')->on('expense_categories')->onDelete('restrict');
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('id')->on('customers')->onDelete('set null'); // Vendors are stored in customers table
            $table->unsignedInteger('currency_id');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('restrict');

            // Amount and description
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();

            // Recurrence configuration
            $table->enum('frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly']);
            $table->timestamp('next_occurrence_at');
            $table->timestamp('ends_at')->nullable(); // Optional end date
            $table->boolean('is_active')->default(true);

            // Metadata
            $table->unsignedInteger('created_by');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            $table->timestamps();

            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['next_occurrence_at', 'is_active']);
            $table->index('frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
// CLAUDE-CHECKPOINT
