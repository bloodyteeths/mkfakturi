<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create matching_rules table for P0-09: Matching Rules Engine
 *
 * Stores user-defined rules that automatically categorize, match,
 * or ignore bank transactions based on configurable conditions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('matching_rules')) {
            return;
        }

        Schema::create('matching_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('restrict');
            $table->string('name');
            $table->json('conditions'); // [{field: 'description', operator: 'contains', value: 'SUBSCRIPTION'}]
            $table->json('actions');    // [{action: 'match_customer', customer_id: 123}]
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['company_id', 'is_active', 'priority']);
        });

        // Ensure ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        if (config('database.default') === 'mysql') {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE matching_rules ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matching_rules');
    }
};

// CLAUDE-CHECKPOINT
