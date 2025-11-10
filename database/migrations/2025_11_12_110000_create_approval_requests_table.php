<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Approval Requests table for document approval workflow
 * Supports Invoice, Estimate, Expense, Bill, CreditNote approval process
 *
 * IMPORTANT: This is a stub implementation ready for ringlesoft/laravel-process-approval package
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('approvable_type'); // Invoice, Estimate, Expense, Bill, CreditNote
            $table->unsignedBigInteger('approvable_id');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('requested_by')->nullable(); // User who requested approval
            $table->unsignedBigInteger('approved_by')->nullable(); // User who approved/rejected
            $table->text('approval_note')->nullable(); // Reason for approval/rejection
            $table->text('request_note')->nullable(); // Note from requester
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('restrict');

            $table->foreign('requested_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('approved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['approvable_type', 'approvable_id']);
            $table->index('requested_by');
            $table->index('approved_by');
        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};

// CLAUDE-CHECKPOINT
