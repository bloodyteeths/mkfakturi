<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // ─── New table: travel_order_vehicles ───
        if (!Schema::hasTable('travel_order_vehicles')) {
            Schema::create('travel_order_vehicles', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('travel_order_id');
                $table->enum('vehicle_type', ['truck', 'trailer', 'car', 'van'])->default('truck');
                $table->string('make', 100)->nullable();
                $table->string('model', 100)->nullable();
                $table->string('registration_plate', 20);
                $table->decimal('capacity_tonnes', 8, 2)->nullable();
                $table->enum('fuel_type', ['diesel', 'petrol', 'lpg', 'cng'])->default('diesel');
                $table->unsignedInteger('odometer_start')->nullable();
                $table->unsignedInteger('odometer_end')->nullable();
                $table->decimal('fuel_start_liters', 8, 2)->nullable();
                $table->decimal('fuel_end_liters', 8, 2)->nullable();
                $table->decimal('fuel_added_liters', 8, 2)->nullable();
                $table->decimal('fuel_norm_per_100km', 5, 2)->nullable();
                $table->timestamps();

                $table->foreign('travel_order_id')
                    ->references('id')->on('travel_orders')
                    ->onDelete('cascade');

                $table->index('travel_order_id');
            });
        }

        // ─── New table: travel_order_crew ───
        if (!Schema::hasTable('travel_order_crew')) {
            Schema::create('travel_order_crew', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('travel_order_id');
                $table->string('name', 150);
                $table->enum('role', ['driver', 'co_driver', 'crew'])->default('driver');
                $table->string('license_number', 50)->nullable();
                $table->string('license_category', 10)->nullable();
                $table->string('cpc_number', 50)->nullable();
                $table->timestamps();

                $table->foreign('travel_order_id')
                    ->references('id')->on('travel_orders')
                    ->onDelete('cascade');

                $table->index('travel_order_id');
            });
        }

        // ─── New table: travel_order_cargo ───
        if (!Schema::hasTable('travel_order_cargo')) {
            Schema::create('travel_order_cargo', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->unsignedBigInteger('travel_order_id');
                $table->unsignedBigInteger('travel_segment_id')->nullable();
                $table->string('cmr_number', 50)->nullable();
                $table->string('sender_name', 200)->nullable();
                $table->text('sender_address')->nullable();
                $table->string('receiver_name', 200)->nullable();
                $table->text('receiver_address')->nullable();
                $table->text('goods_description')->nullable();
                $table->unsignedInteger('packages_count')->nullable();
                $table->decimal('gross_weight_kg', 10, 2)->nullable();
                $table->string('loading_place', 200)->nullable();
                $table->string('unloading_place', 200)->nullable();
                $table->timestamps();

                $table->foreign('travel_order_id')
                    ->references('id')->on('travel_orders')
                    ->onDelete('cascade');

                $table->foreign('travel_segment_id')
                    ->references('id')->on('travel_segments')
                    ->onDelete('set null');

                $table->index('travel_order_id');
            });
        }

        // ─── New table: per_diem_rates ───
        if (!Schema::hasTable('per_diem_rates')) {
            Schema::create('per_diem_rates', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->string('country_code', 2);
                $table->string('country_name_mk', 100);
                $table->string('country_name_en', 100);
                $table->decimal('rate', 10, 2);
                $table->string('currency_code', 3)->default('EUR');
                $table->string('city', 100)->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->timestamps();

                $table->index(['country_code', 'effective_from']);
            });
        }

        // ─── New table: currency_exchange_rates ───
        if (!Schema::hasTable('currency_exchange_rates')) {
            Schema::create('currency_exchange_rates', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';

                $table->id();
                $table->string('currency_code', 3);
                $table->decimal('rate_to_mkd', 12, 6);
                $table->enum('source', ['nbrm', 'manual'])->default('manual');
                $table->date('effective_date');
                $table->timestamps();

                $table->index(['currency_code', 'effective_date']);
            });
        }

        // ─── Alter travel_orders ───
        if (Schema::hasTable('travel_orders')) {
            if (!Schema::hasColumn('travel_orders', 'transport_type_category')) {
                Schema::table('travel_orders', function (Blueprint $table) {
                    $table->enum('transport_type_category', ['goods_transport', 'passenger_transport', 'business_trip'])
                        ->default('business_trip')
                        ->after('type');
                    $table->enum('transport_mode', ['public', 'own_needs'])
                        ->nullable()
                        ->after('transport_type_category');
                    $table->unsignedInteger('total_km')->default(0)->after('total_mileage_cost');
                    $table->decimal('total_fuel_consumed', 8, 2)->nullable()->after('total_km');
                    $table->unsignedBigInteger('total_fuel_cost')->default(0)->after('total_fuel_consumed');
                    $table->unsignedBigInteger('total_toll_cost')->default(0)->after('total_fuel_cost');
                    $table->unsignedBigInteger('total_forwarding_cost')->default(0)->after('total_toll_cost');
                });
            }
        }

        // ─── Alter travel_expenses — expand category enum + add columns ───
        if (Schema::hasTable('travel_expenses')) {
            if (!Schema::hasColumn('travel_expenses', 'gl_account_code')) {
                // Expand category enum to include transport-specific types
                if (DB::getDriverName() === 'mysql') {
                    DB::statement("ALTER TABLE travel_expenses MODIFY COLUMN category ENUM('transport','accommodation','meals','other','per_diem','fuel','tolls','forwarding','vehicle_maintenance','communication') DEFAULT 'other'");
                } elseif (DB::getDriverName() === 'sqlite') {
                    // SQLite: drop CHECK constraint by recreating column
                    Schema::table('travel_expenses', function (Blueprint $table) {
                        $table->string('category_new')->default('other');
                    });
                    DB::statement('UPDATE travel_expenses SET category_new = category');
                    Schema::table('travel_expenses', function (Blueprint $table) {
                        $table->dropColumn('category');
                    });
                    Schema::table('travel_expenses', function (Blueprint $table) {
                        $table->string('category')->default('other');
                    });
                    DB::statement('UPDATE travel_expenses SET category = category_new');
                    Schema::table('travel_expenses', function (Blueprint $table) {
                        $table->dropColumn('category_new');
                    });
                }

                Schema::table('travel_expenses', function (Blueprint $table) {
                    $table->string('gl_account_code', 10)->nullable()->after('currency_code');
                    $table->decimal('exchange_rate', 12, 6)->nullable()->after('gl_account_code');
                    $table->unsignedBigInteger('amount_mkd')->nullable()->after('exchange_rate');
                    $table->unsignedBigInteger('vat_amount')->nullable()->after('amount_mkd');
                    $table->string('receipt_number', 50)->nullable()->after('vat_amount');
                });
            }
        }

        // ─── Alter travel_segments — granular meals + currency ───
        if (Schema::hasTable('travel_segments')) {
            if (!Schema::hasColumn('travel_segments', 'breakfast_provided')) {
                Schema::table('travel_segments', function (Blueprint $table) {
                    $table->string('country_name', 100)->nullable()->after('country_code');
                    $table->string('per_diem_currency', 3)->nullable()->default('MKD')->after('per_diem_amount');
                    $table->unsignedBigInteger('per_diem_amount_mkd')->default(0)->after('per_diem_currency');
                    $table->boolean('breakfast_provided')->default(false)->after('meals_provided');
                    $table->boolean('lunch_provided')->default(false)->after('breakfast_provided');
                    $table->boolean('dinner_provided')->default(false)->after('lunch_provided');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_order_cargo');
        Schema::dropIfExists('travel_order_crew');
        Schema::dropIfExists('travel_order_vehicles');
        Schema::dropIfExists('per_diem_rates');
        Schema::dropIfExists('currency_exchange_rates');

        if (Schema::hasTable('travel_orders')) {
            Schema::table('travel_orders', function (Blueprint $table) {
                $cols = ['transport_type_category', 'transport_mode', 'total_km', 'total_fuel_consumed', 'total_fuel_cost', 'total_toll_cost', 'total_forwarding_cost'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('travel_orders', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('travel_expenses')) {
            Schema::table('travel_expenses', function (Blueprint $table) {
                $cols = ['gl_account_code', 'exchange_rate', 'amount_mkd', 'vat_amount', 'receipt_number'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('travel_expenses', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('travel_segments')) {
            Schema::table('travel_segments', function (Blueprint $table) {
                $cols = ['country_name', 'per_diem_currency', 'per_diem_amount_mkd', 'breakfast_provided', 'lunch_provided', 'dinner_provided'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('travel_segments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

// CLAUDE-CHECKPOINT
