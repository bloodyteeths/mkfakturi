<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'bank_account')) {
                $table->string('bank_account')->nullable()->after('iban');
            }
            if (! Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('bank_account');
            }
            if (! Schema::hasColumn('suppliers', 'activity_code')) {
                $table->string('activity_code', 10)->nullable()->after('company_registration_number');
            }
            if (! Schema::hasColumn('suppliers', 'authorized_person')) {
                $table->string('authorized_person')->nullable()->after('contact_phone');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('suppliers')) {
            return;
        }

        Schema::table('suppliers', function (Blueprint $table) {
            $columns = ['bank_account', 'bank_name', 'activity_code', 'authorized_person'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('suppliers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
