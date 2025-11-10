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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();

            // Polymorphic relationship to auditable models
            $table->string('auditable_type')->index();
            $table->unsignedBigInteger('auditable_id')->index();

            // Who did it
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name')->nullable(); // Denormalized for historical accuracy

            // What happened
            $table->string('event'); // created, updated, deleted, restored, forceDeleted
            $table->text('description')->nullable();

            // Before/after snapshots (encrypted for PII fields)
            $table->json('old_values')->nullable(); // Previous state
            $table->json('new_values')->nullable(); // New state
            $table->json('changed_fields')->nullable(); // List of field names that changed

            // Context
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->string('http_method', 10)->nullable();

            // Batch tracking (for bulk operations)
            $table->uuid('batch_id')->nullable()->index();

            // Tags for filtering
            $table->json('tags')->nullable();

            $table->timestamps();

            // Indexes for common queries
            $table->index(['company_id', 'auditable_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id', 'created_at']);

            // Foreign keys
            $table->foreign('company_id')
                  ->references('id')
                  ->on('companies')
                  ->onDelete('restrict');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

        }) . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
