<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_qc_checks')) {
            return;
        }

        Schema::create('production_qc_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedInteger('company_id');
            $table->unsignedBigInteger('inspector_id')->nullable();
            $table->date('check_date');
            $table->enum('result', ['pass', 'fail', 'conditional'])->default('pass');
            $table->decimal('quantity_inspected', 15, 4)->default(0);
            $table->decimal('quantity_passed', 15, 4)->default(0);
            $table->decimal('quantity_rejected', 15, 4)->default(0);
            $table->text('notes')->nullable();
            $table->json('checklist')->nullable(); // [{criterion, result, notes}]
            $table->json('defects')->nullable(); // [{type, quantity, severity, notes}]
            $table->timestamps();

            $table->foreign('production_order_id')
                ->references('id')
                ->on('production_orders')
                ->onDelete('cascade');
            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
            $table->foreign('inspector_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['production_order_id', 'check_date']);
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_qc_checks');
    }
};
