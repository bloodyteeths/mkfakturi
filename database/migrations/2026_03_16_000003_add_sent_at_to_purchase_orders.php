<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders') && !Schema::hasColumn('purchase_orders', 'sent_at')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->timestamp('sent_at')->nullable()->after('email_sent_to');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('purchase_orders', 'sent_at')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn('sent_at');
            });
        }
    }
};
