<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Audit log for entity reassignments (AC-16)
     */
    public function up(): void
    {
        Schema::create('entity_reassignment_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type'); // company_partner, partner_upline
            $table->unsignedInteger('entity_id'); // Company or Partner ID
            $table->unsignedBigInteger('old_partner_id')->nullable();
            $table->foreign('old_partner_id')->references('id')->on('partners')->onDelete('set null');
            $table->unsignedBigInteger('new_partner_id')->nullable();
            $table->foreign('new_partner_id')->references('id')->on('partners')->onDelete('set null');
            $table->boolean('preserved_commissions')->default(false);
            $table->text('reason')->nullable();
            $table->unsignedInteger('performed_by'); // Admin user who performed reassignment
            $table->foreign('performed_by')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for audit queries
            $table->index(['entity_type', 'entity_id']);
            $table->index('old_partner_id');
            $table->index('new_partner_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_reassignment_log');
    }
};

// CLAUDE-CHECKPOINT
