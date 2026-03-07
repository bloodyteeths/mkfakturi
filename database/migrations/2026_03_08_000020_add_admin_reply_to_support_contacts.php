<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_contacts') && ! Schema::hasColumn('support_contacts', 'admin_reply')) {
            Schema::table('support_contacts', function (Blueprint $table) {
                $table->text('admin_reply')->nullable();
                $table->timestamp('admin_replied_at')->nullable();
                $table->unsignedInteger('admin_user_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('support_contacts')) {
            Schema::table('support_contacts', function (Blueprint $table) {
                $table->dropColumn(['admin_reply', 'admin_replied_at', 'admin_user_id']);
            });
        }
    }
};
