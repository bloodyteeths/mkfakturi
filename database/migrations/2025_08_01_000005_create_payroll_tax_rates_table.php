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
        if (Schema::hasTable('payroll_tax_rates')) {
            return;
        }

        Schema::create('payroll_tax_rates', function (Blueprint $table) {
            $table->increments('id');

            // Unique code for the tax/contribution type
            $table->string('code')->unique();

            // Names
            $table->string('name');
            $table->string('name_mk')->comment('Macedonian name');

            // Rate (decimal with 4 decimal places - e.g., 0.0900 for 9%)
            $table->decimal('rate', 5, 4);

            // Type: employee deduction, employer contribution, or both
            $table->enum('type', ['employee', 'employer', 'both']);

            // Effective dates
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            // Status
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('effective_from');
            $table->index(['is_active', 'effective_from']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_tax_rates');
    }
};

// LLM-CHECKPOINT
