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
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'website')) {
                $table->string('website')->nullable()->after('slug');
            }

            if (! Schema::hasColumn('companies', 'phone')) {
                $table->string('phone')->nullable()->after('website');
            }

            if (! Schema::hasColumn('companies', 'vat_number')) {
                $table->string('vat_number')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('companies', 'currency_id')) {
                $table->unsignedInteger('currency_id')->nullable()->after('vat_number');

                if (Schema::hasTable('currencies') && config('database.default') !== 'sqlite') {
                    $table->foreign('currency_id')
                        ->references('id')
                        ->on('currencies');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'currency_id')) {
                if (Schema::hasTable('currencies') && config('database.default') !== 'sqlite') {
                    $table->dropForeign(['currency_id']);
                }

                $table->dropColumn('currency_id');
            }

            if (Schema::hasColumn('companies', 'vat_number')) {
                $table->dropColumn('vat_number');
            }

            if (Schema::hasColumn('companies', 'phone')) {
                $table->dropColumn('phone');
            }

            if (Schema::hasColumn('companies', 'website')) {
                $table->dropColumn('website');
            }
        });
    }
};
