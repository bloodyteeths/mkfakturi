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
        Schema::create('import_temp_customers', function (Blueprint $table) {
            $table->id();

            // Raw data fields (as imported)
            $table->json('raw_data'); // Original row data from import file
            $table->integer('row_number'); // Row number in source file

            // Mapped fields (matching customers table structure)
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('website')->nullable();
            $table->string('tax_id')->nullable(); // Business tax ID

            // Address fields (will be linked to addresses table later)
            $table->text('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_zip')->nullable();
            $table->string('billing_country')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_zip')->nullable();
            $table->string('shipping_country')->nullable();

            // Processing fields
            $table->enum('status', ['pending', 'validated', 'mapped', 'failed', 'committed'])->default('pending');
            $table->json('validation_errors')->nullable(); // Validation error details
            $table->json('mapping_confidence')->nullable(); // Field mapping confidence scores
            $table->boolean('is_duplicate')->default(false);
            $table->string('duplicate_match_field')->nullable(); // Field used for duplicate detection
            $table->json('transformation_log')->nullable(); // Record of transformations applied

            // References
            $table->unsignedBigInteger('import_job_id');
            $table->foreign('import_job_id')->references('id')->on('import_jobs')->onDelete('cascade');
            $table->unsignedBigInteger('existing_customer_id')->nullable(); // Link to existing customer if duplicate
            $table->foreign('existing_customer_id')->references('id')->on('customers')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['import_job_id', 'status']);
            $table->index(['import_job_id', 'row_number']);
            $table->index(['email', 'import_job_id']);
            $table->index(['name', 'import_job_id']);
            $table->index('is_duplicate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_temp_customers');
    }
};
