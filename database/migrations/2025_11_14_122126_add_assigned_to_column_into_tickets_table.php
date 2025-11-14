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

        Schema::table($tableName, function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->after('is_locked'); // CLAUDE-CHECKPOINT: Fixed foreign key syntax
            $table->index('assigned_to'); // CLAUDE-CHECKPOINT: Added index for performance
        });
    }

    public function down()
    {
        $tableName = config('laravel_ticket.table_names.tickets', 'tickets');

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
            $table->dropColumn('assigned_to');
        });
    }
};
