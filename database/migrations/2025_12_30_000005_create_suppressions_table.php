<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Global suppression list
     */
    public function up(): void
    {
        // CLAUDE-CHECKPOINT
        if (Schema::hasTable('suppressions')) {
            // Add missing columns if table exists
            Schema::table('suppressions', function (Blueprint $table) {
                if (!Schema::hasColumn('suppressions', 'type')) {
                    $table->string('type', 30)->default('manual')->after('email');
                }
                if (!Schema::hasColumn('suppressions', 'reason')) {
                    $table->text('reason')->nullable()->after('type');
                }
                if (!Schema::hasColumn('suppressions', 'source')) {
                    $table->string('source', 30)->default('system')->after('reason');
                }
            });
            return;
        }

        Schema::create('suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('type', 30); // unsub, bounce, complaint, manual
            $table->text('reason')->nullable();
            $table->string('source', 30)->default('system'); // postmark, user, admin
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppressions');
    }
};
