<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confidence Calibration with Feedback Loop
 *
 * Creates the account_suggestion_feedback table for tracking accuracy
 * of AI-powered account suggestions. This enables the system to learn
 * and improve confidence scores based on actual user acceptance rates.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('account_suggestion_feedback')) {
            return;
        }

        Schema::create('account_suggestion_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('company_id'); // companies.id is unsigned int
            $table->string('entity_type', 50)->comment('customer, supplier, expense_category');
            $table->string('suggestion_reason', 50)->comment('learned, pattern, category, default, special');
            $table->unsignedBigInteger('suggested_account_id');
            $table->unsignedBigInteger('accepted_account_id')->nullable()->comment('Account user actually selected');
            $table->decimal('original_confidence', 4, 3)->comment('Original static confidence (0.000 to 1.000)');
            $table->boolean('was_accepted')->default(false)->comment('True if user accepted suggestion');
            $table->boolean('was_modified')->default(false)->comment('True if user changed the account');
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('suggested_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');

            $table->foreign('accepted_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'entity_type', 'suggestion_reason'], 'feedback_accuracy_lookup');
            $table->index(['company_id', 'created_at'], 'feedback_recent');
        }) ;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_suggestion_feedback');
    }
};
// CLAUDE-CHECKPOINT
