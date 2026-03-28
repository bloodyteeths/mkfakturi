<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fiscal_device_events')) {
            Schema::create('fiscal_device_events', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('fiscal_device_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('event_type', 30);
                // event_type values: open, close, z_report, error, receipt, void, status_check
                $table->string('source', 30)->default('manual');
                // source values: manual, api, scheduled, system
                $table->unsignedBigInteger('cash_amount')->nullable();
                // Amount in cents at time of event (for open/close reconciliation)
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                // metadata: { ip, user_agent, shift_number, receipt_count, etc. }
                $table->timestamp('event_at');
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->onDelete('restrict');

                $table->foreign('fiscal_device_id')
                    ->references('id')->on('fiscal_devices')
                    ->onDelete('restrict');

                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('restrict');

                $table->index(['company_id', 'event_at']);
                $table->index(['fiscal_device_id', 'event_at']);
                $table->index(['user_id', 'event_at']);
                $table->index(['event_type', 'event_at']);
            });
        }

        // Add fraud alert tracking
        if (!Schema::hasTable('fiscal_fraud_alerts')) {
            Schema::create('fiscal_fraud_alerts', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('fiscal_device_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('alert_type', 50);
                // alert_type values:
                //   unexpected_close    - device closed outside normal hours
                //   off_hours_activity  - receipt/open during off hours
                //   gap_in_receipts     - receipt number gap detected
                //   cash_discrepancy    - Z-report vs system mismatch
                //   frequent_voids      - too many voided receipts
                //   no_z_report         - day ended without Z-report
                //   rapid_open_close    - open+close within minutes (skimming pattern)
                $table->string('severity', 10)->default('medium');
                // severity: low, medium, high, critical
                $table->text('description');
                $table->json('evidence')->nullable();
                // evidence: { events: [...], amounts: {...}, timestamps: [...] }
                $table->string('status', 20)->default('open');
                // status: open, acknowledged, investigated, resolved, false_positive
                $table->unsignedBigInteger('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->onDelete('restrict');

                $table->foreign('fiscal_device_id')
                    ->references('id')->on('fiscal_devices')
                    ->onDelete('restrict');

                $table->index(['company_id', 'status', 'created_at']);
                $table->index(['fiscal_device_id', 'status']);
                $table->index(['alert_type', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_fraud_alerts');
        Schema::dropIfExists('fiscal_device_events');
    }
};

// CLAUDE-CHECKPOINT
