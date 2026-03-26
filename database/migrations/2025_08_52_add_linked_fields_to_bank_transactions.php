<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('bank_transactions', 'linked_type')) {
            Schema::table('bank_transactions', function (Blueprint $table) {
                $table->string('linked_type', 32)->nullable()->after('ai_match_reason');
                $table->unsignedBigInteger('linked_id')->nullable()->after('linked_type');
                $table->timestamp('reconciled_at')->nullable()->after('linked_id');

                $table->index(['linked_type', 'linked_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropIndex(['linked_type', 'linked_id']);
            $table->dropColumn(['linked_type', 'linked_id', 'reconciled_at']);
        });
    }
};
