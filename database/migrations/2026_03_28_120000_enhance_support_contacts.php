<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('support_contacts')) {
            if (! Schema::hasColumn('support_contacts', 'assigned_to')) {
                Schema::table('support_contacts', function (Blueprint $table) {
                    $table->unsignedInteger('assigned_to')->nullable()->after('admin_user_id');
                    $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
                });
            }
        }

        if (! Schema::hasTable('support_contact_replies')) {
            Schema::create('support_contact_replies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('support_contact_id');
                $table->foreign('support_contact_id')->references('id')->on('support_contacts')->onDelete('cascade');
                $table->unsignedInteger('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->text('message');
                $table->boolean('is_internal')->default(false);
                $table->timestamps();

                $table->index('support_contact_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_contact_replies');

        if (Schema::hasTable('support_contacts') && Schema::hasColumn('support_contacts', 'assigned_to')) {
            Schema::table('support_contacts', function (Blueprint $table) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            });
        }
    }
};
