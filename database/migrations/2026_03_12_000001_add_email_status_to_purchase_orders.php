<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_orders') && !Schema::hasColumn('purchase_orders', 'email_status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->string('email_status', 20)->nullable()->after('status');
                $table->string('email_sent_to')->nullable()->after('email_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('purchase_orders') && Schema::hasColumn('purchase_orders', 'email_status')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropColumn(['email_status', 'email_sent_to']);
            });
        }
    }
};
