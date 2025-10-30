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
        Schema::create('import_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // User-friendly job name
            $table->enum('type', ['customers', 'invoices', 'items', 'payments', 'expenses', 'complete']); // Import type
            $table->enum('status', ['pending', 'parsing', 'mapping', 'validating', 'committing', 'completed', 'failed'])->default('pending');
            $table->string('source_system')->nullable(); // e.g., 'onivo', 'megasoft', 'pantheon'
            $table->string('file_type')->nullable(); // csv, xlsx, xml, etc.
            $table->json('file_info')->nullable(); // Original filename, size, mime type, etc.
            $table->string('file_path')->nullable(); // Path to uploaded file
            $table->json('mapping_config')->nullable(); // Field mapping configuration
            $table->json('validation_rules')->nullable(); // Custom validation rules
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('error_details')->nullable(); // Detailed error information
            $table->json('summary')->nullable(); // Import summary and statistics
            
            // Relationships
            $table->unsignedInteger('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->unsignedInteger('creator_id');
            $table->foreign('creator_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['creator_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_jobs');
    }
};