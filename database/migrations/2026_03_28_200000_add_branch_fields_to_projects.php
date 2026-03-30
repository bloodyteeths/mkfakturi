<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds branch (Подружница) fields to the projects table.
 * Extends the Project model to also serve as Branch management for multi-location companies.
 * Existing projects automatically get type='project' via default value.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'type')) {
                $table->enum('type', ['project', 'branch'])->default('project')->after('notes');
            }

            if (! Schema::hasColumn('projects', 'address')) {
                $table->string('address', 500)->nullable()->after('type');
            }

            if (! Schema::hasColumn('projects', 'city')) {
                $table->string('city', 100)->nullable()->after('address');
            }

            if (! Schema::hasColumn('projects', 'municipality')) {
                $table->string('municipality', 100)->nullable()->after('city');
            }

            if (! Schema::hasColumn('projects', 'registration_number')) {
                $table->string('registration_number', 50)->nullable()->after('municipality');
            }

            if (! Schema::hasColumn('projects', 'manager_id')) {
                $table->integer('manager_id')->unsigned()->nullable()->after('registration_number');
                $table->foreign('manager_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('projects', 'phone')) {
                $table->string('phone', 50)->nullable()->after('manager_id');
            }

            if (! Schema::hasColumn('projects', 'email')) {
                $table->string('email', 255)->nullable()->after('phone');
            }

            if (! Schema::hasColumn('projects', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('email');
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('projects')
                    ->onDelete('set null');
            }

            if (! Schema::hasColumn('projects', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('parent_id');
            }

            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['company_id', 'type']);

            $table->dropColumn([
                'type', 'address', 'city', 'municipality', 'registration_number',
                'manager_id', 'phone', 'email', 'parent_id', 'is_active',
            ]);
        });
    }
};
