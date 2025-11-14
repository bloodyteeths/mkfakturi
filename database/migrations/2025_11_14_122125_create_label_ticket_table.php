<?php

namespace Coderflex\LaravelTicket\Database\Factories;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tableName = config('laravel_ticket.table_names.label_ticket', 'label_ticket');

        Schema::create($tableName['table'], function (Blueprint $table) use ($tableName) {
            $table->engine = 'InnoDB'; // CLAUDE-CHECKPOINT: Set engine
            $table->charset = 'utf8mb4'; // CLAUDE-CHECKPOINT: Set charset
            $table->collation = 'utf8mb4_unicode_ci'; // CLAUDE-CHECKPOINT: Set collation

            collect($tableName['columns'])->each(function ($column, $key) use ($table) {
                $table->foreignId($column);
            });
        });
    }

    public function down()
    {
        $tableName = config('laravel_ticket.table_names.label_ticket', 'label_ticket');
        Schema::dropIfExists($tableName['table']);
    }
};
