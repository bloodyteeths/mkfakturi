<?php

namespace Coderflex\LaravelTicket\Database\Factories;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tableName = config('laravel_ticket.table_names.tickets', 'tickets');

        Schema::create($tableName, function (Blueprint $table) {
            $table->engine = 'InnoDB'; // CLAUDE-CHECKPOINT: Set engine
            $table->charset = 'utf8mb4'; // CLAUDE-CHECKPOINT: Set charset
            $table->collation = 'utf8mb4_unicode_ci'; // CLAUDE-CHECKPOINT: Set collation

            $table->id();
            $table->uuid('uuid')->nullable();
            $table->foreignId('user_id');
            $table->foreignId('company_id')->nullable(); // CLAUDE-CHECKPOINT: Added for multi-tenancy
            $table->string('title');
            $table->string('message')->nullable();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal'); // CLAUDE-CHECKPOINT: Changed to ENUM with urgent option
            $table->string('status')->default('open');
            $table->boolean('is_resolved')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            // CLAUDE-CHECKPOINT: Add indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'user_id']);
            $table->index('uuid');
        });
    }

    public function down()
    {
        $tableName = config('laravel_ticket.table_names.tickets', 'tickets');
        Schema::dropIfExists($tableName);
    }
};
