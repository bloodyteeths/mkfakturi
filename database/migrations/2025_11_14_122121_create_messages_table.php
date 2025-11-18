<?php

namespace Coderflex\LaravelTicket\Database\Factories;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tableName = config('laravel_ticket.table_names.messages', 'messages');

        Schema::create($tableName['table'], function (Blueprint $table) use ($tableName) {
            $table->engine = 'InnoDB'; // CLAUDE-CHECKPOINT: Set engine
            $table->charset = 'utf8mb4'; // CLAUDE-CHECKPOINT: Set charset
            $table->collation = 'utf8mb4_unicode_ci'; // CLAUDE-CHECKPOINT: Set collation

            $table->id();
            $table->foreignId($tableName['columns']['user_foreign_id']);
            $table->foreignId($tableName['columns']['ticket_foreign_id']);
            $table->text('message');
            $table->boolean('is_internal')->default(false); // CLAUDE-CHECKPOINT: Added for internal/agent notes
            $table->timestamps();

            // CLAUDE-CHECKPOINT: Add index for performance
            $table->index($tableName['columns']['ticket_foreign_id']);
        });
    }

    public function down()
    {
        $tableName = config('laravel_ticket.table_names.messages', 'messages');
        Schema::dropIfExists($tableName['table']);
    }
};
