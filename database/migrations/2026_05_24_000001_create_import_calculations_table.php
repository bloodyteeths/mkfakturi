<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('import_calculations')) {
            Schema::create('import_calculations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('document_number', 30);
                $table->date('document_date');
                $table->string('status', 20)->default('draft');
                $table->unsignedBigInteger('supplier_bill_id')->nullable();
                $table->string('supplier_name', 255)->nullable();
                $table->string('supplier_invoice_number', 100)->nullable();
                $table->string('currency_code', 3)->default('EUR');
                $table->decimal('exchange_rate', 12, 6);
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->bigInteger('transport_amount')->default(0);
                $table->bigInteger('forwarding_amount')->default(0);
                $table->bigInteger('other_costs_amount')->default(0);
                $table->bigInteger('customs_duty_total')->default(0);
                $table->bigInteger('import_vat_total')->default(0);
                $table->bigInteger('total_landed_cost')->default(0);
                $table->bigInteger('total_invoice_value_mkd')->default(0);
                $table->decimal('vat_rate', 5, 2)->default(18.00);
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->foreign('supplier_bill_id')->references('id')->on('bills')->onDelete('set null');
                $table->foreign('warehouse_id')->references('id')->on('warehouses')->onDelete('set null');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');

                $table->index(['company_id', 'document_date']);
                $table->unique(['company_id', 'document_number']);
            });
        }

        if (! Schema::hasTable('import_calculation_items')) {
            Schema::create('import_calculation_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('import_calculation_id');
                $table->unsignedBigInteger('item_id')->nullable();
                $table->string('tariff_heading', 20);
                $table->string('description', 500);
                $table->decimal('quantity', 12, 4);
                $table->string('unit', 20)->default('ком.');
                $table->bigInteger('unit_price_fcy')->default(0);
                $table->bigInteger('invoice_value_fcy')->default(0);
                $table->bigInteger('invoice_value_mkd')->default(0);
                $table->bigInteger('transport_allocated')->default(0);
                $table->bigInteger('customs_base')->default(0);
                $table->decimal('customs_duty_rate', 5, 2)->default(0);
                $table->bigInteger('customs_duty_amount')->default(0);
                $table->bigInteger('forwarding_allocated')->default(0);
                $table->bigInteger('other_costs_allocated')->default(0);
                $table->bigInteger('landed_cost_before_vat')->default(0);
                $table->bigInteger('import_vat_amount')->default(0);
                $table->bigInteger('total_landed_cost')->default(0);
                $table->bigInteger('unit_landed_cost')->default(0);
                $table->string('notes', 255)->nullable();
                $table->timestamps();

                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->foreign('import_calculation_id')->references('id')->on('import_calculations')->onDelete('cascade');
                $table->foreign('item_id')->references('id')->on('items')->onDelete('set null');

                $table->index(['import_calculation_id', 'item_id']);
                $table->index(['import_calculation_id', 'tariff_heading']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('import_calculation_items');
        Schema::dropIfExists('import_calculations');
    }
};

// CLAUDE-CHECKPOINT
