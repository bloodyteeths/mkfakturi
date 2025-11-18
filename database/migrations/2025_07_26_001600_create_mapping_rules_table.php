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
        Schema::create('mapping_rules', function (Blueprint $table) {
            $table->id();

            // Rule identification
            $table->string('name'); // Human-readable rule name
            $table->text('description')->nullable(); // Rule description
            $table->enum('entity_type', ['customer', 'invoice', 'item', 'payment', 'expense']); // Target entity
            $table->string('source_system')->nullable(); // e.g., 'onivo', 'megasoft', 'pantheon', 'generic'

            // Field mapping configuration
            $table->string('source_field'); // Source field name/pattern
            $table->string('target_field'); // Target field in our system
            $table->json('field_variations')->nullable(); // Alternative field names/patterns

            // Transformation rules
            $table->enum('transformation_type', [
                'direct', // Direct mapping
                'regex', // Regex transformation
                'lookup', // Value lookup/translation
                'calculation', // Mathematical calculation
                'date_format', // Date format conversion
                'currency_convert', // Currency conversion
                'split', // Split field into multiple
                'combine', // Combine multiple fields
                'conditional', // Conditional mapping based on other fields
            ])->default('direct');

            $table->json('transformation_config')->nullable(); // Transformation parameters
            $table->text('transformation_script')->nullable(); // Custom transformation logic

            // Validation rules
            $table->json('validation_rules')->nullable(); // Laravel validation rules
            $table->json('business_rules')->nullable(); // Custom business validation

            // Macedonia-specific configurations
            $table->json('macedonian_patterns')->nullable(); // Macedonian field name patterns
            $table->json('language_variations')->nullable(); // Serbian/Albanian variations
            $table->json('format_patterns')->nullable(); // Date, decimal, currency format patterns

            // Confidence and learning
            $table->decimal('confidence_score', 3, 2)->default(1.00); // Rule confidence (0-1)
            $table->integer('usage_count')->default(0); // How many times this rule was used
            $table->integer('success_count')->default(0); // How many times it succeeded
            $table->decimal('success_rate', 5, 2)->default(0); // Success percentage

            // Rule priority and conditions
            $table->integer('priority')->default(100); // Execution priority (lower = higher priority)
            $table->json('conditions')->nullable(); // Conditions when this rule applies
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system_rule')->default(false); // System vs user-defined

            // Examples and testing
            $table->json('test_cases')->nullable(); // Test input/output examples
            $table->json('sample_data')->nullable(); // Sample data for this mapping

            // Relationships
            $table->unsignedInteger('company_id')->nullable(); // Company-specific rules
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedInteger('creator_id')->nullable(); // Who created this rule
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['entity_type', 'source_system']);
            $table->index(['source_field', 'entity_type']);
            $table->index(['target_field', 'entity_type']);
            $table->index(['company_id', 'is_active']);
            $table->index(['transformation_type', 'is_active']);
            $table->index(['priority', 'is_active']);
            $table->index('confidence_score');

            // Unique constraint for system rules
            $table->unique(['source_field', 'target_field', 'entity_type', 'source_system'], 'unique_mapping_rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mapping_rules');
    }
};
