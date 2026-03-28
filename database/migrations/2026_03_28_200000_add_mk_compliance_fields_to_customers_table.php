<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            Schema::table('customers', function (Blueprint $table) {
                if (! Schema::hasColumn('customers', 'vat_number')) {
                    $table->string('vat_number')->nullable()->after('tax_id');
                }
                if (! Schema::hasColumn('customers', 'bank_account')) {
                    $table->string('bank_account')->nullable()->after('vat_number');
                }
                if (! Schema::hasColumn('customers', 'bank_name')) {
                    $table->string('bank_name')->nullable()->after('bank_account');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['vat_number', 'bank_account', 'bank_name']);
        });
    }
};
