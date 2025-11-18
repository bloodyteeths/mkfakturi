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
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();

            // Log identification
            $table->enum('log_type', [
                'job_created', 'job_started', 'job_completed', 'job_failed',
                'file_uploaded', 'file_parsed', 'parsing_error',
                'mapping_applied', 'mapping_failed', 'auto_mapping',
                'validation_started', 'validation_passed', 'validation_failed',
                'transformation_applied', 'transformation_failed',
                'duplicate_detected', 'duplicate_resolved',
                'record_committed', 'record_failed', 'rollback_executed',
                'custom_rule_applied', 'business_rule_violation',
                'performance_warning', 'system_error',
            ]);

            $table->enum('severity', ['debug', 'info', 'warning', 'error', 'critical'])->default('info');
            $table->string('message'); // Human-readable log message
            $table->text('detailed_message')->nullable(); // Detailed technical message

            // Context information
            $table->string('entity_type')->nullable(); // customer, invoice, item, payment, expense
            $table->unsignedBigInteger('entity_id')->nullable(); // ID of the temp entity
            $table->integer('row_number')->nullable(); // Row number in source file
            $table->string('field_name')->nullable(); // Specific field involved
            $table->text('field_value')->nullable(); // Original field value
            $table->text('transformed_value')->nullable(); // Value after transformation

            // Processing context
            $table->string('process_stage')->nullable(); // parsing, mapping, validating, committing
            $table->string('rule_applied')->nullable(); // Name of mapping rule applied
            $table->decimal('confidence_score', 3, 2)->nullable(); // Confidence in transformation
            $table->decimal('processing_time', 8, 3)->nullable(); // Time taken for operation (seconds)

            // Error details
            $table->string('error_code')->nullable(); // Error code for categorization
            $table->json('error_context')->nullable(); // Additional error context
            $table->text('stack_trace')->nullable(); // Technical stack trace
            $table->json('suggested_fixes')->nullable(); // Automated suggestions for fixing

            // Data preservation (for debugging and rollback)
            $table->json('original_data')->nullable(); // Original row data
            $table->json('intermediate_data')->nullable(); // Data at various processing stages
            $table->json('final_data')->nullable(); // Final processed data

            // Performance metrics
            $table->integer('memory_usage')->nullable(); // Memory usage in bytes
            $table->integer('records_processed')->nullable(); // Number of records processed in batch
            $table->decimal('throughput_rate', 8, 2)->nullable(); // Records per second

            // References
            $table->unsignedBigInteger('import_job_id');
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
            $table->unsignedBigInteger('mapping_rule_id')->nullable(); // Mapping rule that triggered this log
            $table->foreign('mapping_rule_id')->references('id')->on('mapping_rules')->onDelete('set null');

            // User context
            $table->unsignedInteger('user_id')->nullable(); // User who triggered the action
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->string('user_action')->nullable(); // manual_override, auto_correction, etc.

            // Audit and compliance
            $table->boolean('is_audit_required')->default(false); // Mark for compliance review
            $table->timestamp('retention_until')->nullable(); // When this log can be purged
            $table->json('compliance_tags')->nullable(); // GDPR, tax audit, etc.

            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['import_job_id', 'created_at']);
            $table->index(['import_job_id', 'log_type']);
            $table->index(['import_job_id', 'severity']);
            $table->index(['log_type', 'created_at']);
            $table->index(['severity', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['row_number', 'import_job_id']);
            $table->index(['process_stage', 'import_job_id']);
            $table->index(['error_code', 'created_at']);
            $table->index('is_audit_required');
            $table->index('retention_until');

            // Full-text search for messages (MySQL specific)
            // $table->fullText(['message', 'detailed_message']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
