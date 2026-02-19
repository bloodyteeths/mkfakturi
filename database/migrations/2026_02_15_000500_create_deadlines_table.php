<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create deadlines table for deadline tracking dashboard (P8-02)
 *
 * Stores recurring and custom deadlines for MK accounting obligations:
 * - VAT returns (25th monthly)
 * - MPIN payroll filings (10th monthly)
 * - CIT advance payments (15th monthly)
 * - Annual financial statements (March 15)
 * - Custom partner/company deadlines
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('deadlines')) {
            Schema::create('deadlines', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->string('title', 200);
                $table->string('title_mk', 200)->nullable();
                $table->text('description')->nullable();
                $table->enum('deadline_type', ['vat_return', 'mpin', 'cit_advance', 'annual_fs', 'custom'])->default('custom');
                $table->date('due_date');
                $table->enum('status', ['upcoming', 'due_today', 'overdue', 'completed'])->default('upcoming');
                $table->timestamp('completed_at')->nullable();
                $table->unsignedInteger('completed_by')->nullable();
                $table->json('reminder_days_before')->default('[7, 3, 1]');
                $table->timestamp('last_reminder_sent_at')->nullable();
                $table->boolean('is_recurring')->default(false);
                $table->string('recurrence_rule', 50)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->index(['company_id', 'due_date', 'status']);
                $table->index(['partner_id', 'due_date', 'status']);
                $table->index(['status', 'due_date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deadlines');
    }
};
