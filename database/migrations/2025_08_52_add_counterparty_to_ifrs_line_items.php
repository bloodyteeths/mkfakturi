<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCounterpartyToIfrsLineItems extends Migration
{
    public function up()
    {
        $table = config('ifrs.table_prefix', 'ifrs_') . 'line_items';

        if (Schema::hasTable($table) && !Schema::hasColumn($table, 'counterparty_name')) {
            Schema::table($table, function (Blueprint $table) {
                $table->string('counterparty_name', 255)->nullable()->after('narration');
            });
        }
    }

    public function down()
    {
        $table = config('ifrs.table_prefix', 'ifrs_') . 'line_items';

        if (Schema::hasTable($table) && Schema::hasColumn($table, 'counterparty_name')) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('counterparty_name');
            });
        }
    }
}
