<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P10-02: Create fiscal device and fiscal receipt tables.
 *
 * Stores registered fiscal devices per company and their receipt history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fiscal_devices')) {
            Schema::create('fiscal_devices', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->string('device_type', 50);
                $table->string('name', 100)->nullable();
                $table->string('serial_number', 100);
                $table->string('ip_address', 45)->nullable();
                $table->unsignedSmallInteger('port')->nullable();
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->unique(['company_id', 'serial_number']);
                $table->index(['company_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('fiscal_receipts')) {
            Schema::create('fiscal_receipts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('fiscal_device_id');
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('receipt_number', 50);
                $table->integer('amount');
                $table->integer('vat_amount');
                $table->string('fiscal_id', 100);
                $table->text('raw_response')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('restrict');

                $table->foreign('fiscal_device_id')
                    ->references('id')
                    ->on('fiscal_devices')
                    ->onDelete('restrict');

                $table->index(['company_id', 'created_at']);
                $table->index(['fiscal_device_id', 'created_at']);
                $table->index('fiscal_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_receipts');
        Schema::dropIfExists('fiscal_devices');
    }
};
