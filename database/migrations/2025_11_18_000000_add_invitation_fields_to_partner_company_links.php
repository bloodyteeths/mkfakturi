<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->string('invitation_status')->default('accepted')->after('is_active');
            $table->integer('created_by')->unsigned()->nullable()->after('invitation_status');
            $table->timestamp('invited_at')->nullable()->after('created_by');
            $table->timestamp('accepted_at')->nullable()->after('invited_at');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('partner_company_links', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['invitation_status', 'created_by', 'invited_at', 'accepted_at']);
        });
    }
};
