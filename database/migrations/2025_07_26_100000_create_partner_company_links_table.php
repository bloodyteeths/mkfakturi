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
        Schema::create('partner_company_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->foreign('partner_id')->references('id')->on('partners')->onDelete('cascade');
            $table->unsignedInteger('company_id'); // companies.id is unsigned int, not bigint
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->boolean('is_primary')->default(false)->comment('Primary company for partner');
            $table->decimal('override_commission_rate', 5, 2)->nullable()->comment('Override default partner commission for this company');
            $table->json('permissions')->nullable()->comment('Specific permissions for this company');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique partner-company combinations
            $table->unique(['partner_id', 'company_id'], 'partner_company_unique');
            
            // Index for performance
            $table->index(['partner_id', 'is_active'], 'partner_active_companies');
            $table->index(['company_id', 'is_active'], 'company_active_partners');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partner_company_links');
    }
};

