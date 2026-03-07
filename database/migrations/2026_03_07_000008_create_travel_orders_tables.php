<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('travel_orders')) {
            Schema::create('travel_orders', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('employee_id')->nullable();
                $table->string('travel_number', 50)->unique();
                $table->enum('type', ['domestic', 'foreign'])->default('domestic');
                $table->text('purpose');
                $table->dateTime('departure_date');
                $table->dateTime('return_date');
                $table->enum('status', ['draft', 'pending_approval', 'approved', 'settled', 'rejected'])->default('draft');
                $table->unsignedBigInteger('advance_amount')->default(0);
                $table->unsignedBigInteger('total_per_diem')->default(0);
                $table->unsignedBigInteger('total_expenses')->default(0);
                $table->unsignedBigInteger('total_mileage_cost')->default(0);
                $table->unsignedBigInteger('grand_total')->default(0);
                $table->bigInteger('reimbursement_amount')->default(0);
                $table->unsignedBigInteger('cost_center_id')->nullable();
                $table->unsignedBigInteger('ifrs_transaction_id')->nullable();
                $table->unsignedInteger('approved_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')
                    ->references('id')->on('companies')
                    ->onDelete('restrict');

                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'departure_date']);
            });
        }

        if (!Schema::hasTable('travel_segments')) {
            Schema::create('travel_segments', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('travel_order_id');
                $table->string('from_city', 150);
                $table->string('to_city', 150);
                $table->string('country_code', 2)->nullable();
                $table->dateTime('departure_at');
                $table->dateTime('arrival_at');
                $table->enum('transport_type', ['car', 'bus', 'train', 'plane', 'other'])->default('car');
                $table->decimal('distance_km', 10, 2)->nullable();
                $table->boolean('accommodation_provided')->default(false);
                $table->boolean('meals_provided')->default(false);
                $table->decimal('per_diem_rate', 10, 2)->nullable();
                $table->decimal('per_diem_days', 5, 2)->nullable();
                $table->unsignedBigInteger('per_diem_amount')->default(0);
                $table->timestamps();

                $table->foreign('travel_order_id')
                    ->references('id')->on('travel_orders')
                    ->onDelete('cascade');

                $table->index('travel_order_id');
            });
        }

        if (!Schema::hasTable('travel_expenses')) {
            Schema::create('travel_expenses', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('travel_order_id');
                $table->enum('category', ['transport', 'accommodation', 'meals', 'other']);
                $table->string('description', 255);
                $table->unsignedBigInteger('amount');
                $table->string('currency_code', 3)->default('MKD');
                $table->string('receipt_path', 500)->nullable();
                $table->timestamps();

                $table->foreign('travel_order_id')
                    ->references('id')->on('travel_orders')
                    ->onDelete('cascade');

                $table->index('travel_order_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expenses');
        Schema::dropIfExists('travel_segments');
        Schema::dropIfExists('travel_orders');
    }
};

// CLAUDE-CHECKPOINT
