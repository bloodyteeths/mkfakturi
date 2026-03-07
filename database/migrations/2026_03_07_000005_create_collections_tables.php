<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. reminder_templates table
        if (! Schema::hasTable('reminder_templates')) {
            Schema::create('reminder_templates', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->enum('escalation_level', ['friendly', 'firm', 'final', 'legal']);
                $table->integer('days_after_due');
                $table->text('subject_mk');
                $table->text('subject_en');
                $table->text('subject_tr');
                $table->text('subject_sq');
                $table->text('body_mk');
                $table->text('body_en');
                $table->text('body_tr');
                $table->text('body_sq');
                $table->boolean('is_active')->default(true);
                $table->boolean('auto_send')->default(false);
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->index('company_id', 'idx_rt_company');
            });
        }

        // 2. reminder_history table
        if (! Schema::hasTable('reminder_history')) {
            Schema::create('reminder_history', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedInteger('invoice_id');
                $table->unsignedBigInteger('customer_id');
                $table->unsignedBigInteger('template_id')->nullable();
                $table->string('escalation_level', 20);
                $table->timestamp('sent_at')->useCurrent();
                $table->enum('sent_via', ['email', 'sms'])->default('email');
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->unsignedBigInteger('amount_due');
                $table->text('notes')->nullable();

                $table->index('invoice_id', 'idx_rh_invoice');
                $table->index('customer_id', 'idx_rh_customer');
                $table->index('company_id', 'idx_rh_company');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_history');
        Schema::dropIfExists('reminder_templates');
    }
};

// CLAUDE-CHECKPOINT
