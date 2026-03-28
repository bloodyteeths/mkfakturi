<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wac_correction_proposals')) {
            return;
        }

        Schema::create('wac_correction_proposals', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('audit_run_id');
            $table->string('status', 30)->default('pending');
            $table->text('description')->nullable();
            $table->json('correction_entries');
            $table->json('ai_reasoning')->nullable();

            $table->decimal('net_quantity_adjustment', 15, 4)->default(0);
            $table->bigInteger('net_value_adjustment')->default(0);

            $table->unsignedBigInteger('proposed_by')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('audit_run_id')->references('id')->on('wac_audit_runs')->onDelete('cascade');
            $table->foreign('proposed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wac_correction_proposals');
    }
};
