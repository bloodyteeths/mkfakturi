<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('supplier_id')->nullable();
                $table->string('po_number', 50);
                $table->date('po_date');
                $table->date('expected_delivery_date')->nullable();
                $table->enum('status', [
                    'draft',
                    'sent',
                    'acknowledged',
                    'partially_received',
                    'fully_received',
                    'billed',
                    'closed',
                    'cancelled',
                ])->default('draft');
                $table->unsignedBigInteger('sub_total')->default(0);
                $table->unsignedBigInteger('tax')->default(0);
                $table->unsignedBigInteger('total')->default(0);
                $table->unsignedInteger('currency_id')->nullable();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedInteger('converted_bill_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->index(['company_id', 'status'], 'idx_po_company_status');
            });
        }

        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('purchase_order_id');
                $table->unsignedInteger('item_id')->nullable();
                $table->string('name', 255);
                $table->decimal('quantity', 15, 4);
                $table->decimal('received_quantity', 15, 4)->default(0);
                $table->unsignedBigInteger('price')->default(0);
                $table->unsignedBigInteger('tax')->default(0);
                $table->unsignedBigInteger('total')->default(0);
                $table->timestamps();

                $table->foreign('purchase_order_id')
                    ->references('id')
                    ->on('purchase_orders')
                    ->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedInteger('company_id');
                $table->unsignedBigInteger('purchase_order_id')->nullable();
                $table->string('receipt_number', 50);
                $table->date('receipt_date');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            });
        }

        if (!Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('goods_receipt_id');
                $table->unsignedBigInteger('purchase_order_item_id')->nullable();
                $table->unsignedInteger('item_id')->nullable();
                $table->decimal('quantity_received', 15, 4);
                $table->decimal('quantity_accepted', 15, 4)->nullable();
                $table->decimal('quantity_rejected', 15, 4)->default(0);
                $table->timestamps();

                $table->foreign('goods_receipt_id')
                    ->references('id')
                    ->on('goods_receipts')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};

// CLAUDE-CHECKPOINT
