<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ai_conversations')) {
            Schema::create('ai_conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('company_id');
                $table->uuid('conversation_id')->unique();
                $table->json('messages');
                $table->string('title', 255)->nullable();
                $table->integer('message_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');

                $table->index(['user_id', 'company_id', 'is_active']);
                $table->index(['user_id', 'company_id', 'updated_at']);
            });
        }

        if (! Schema::hasTable('ai_user_memory')) {
            Schema::create('ai_user_memory', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('company_id');
                $table->text('memory_summary');
                $table->string('preferred_language', 5)->default('mk');
                $table->json('frequent_topics')->nullable();
                $table->integer('total_conversations')->default(0);
                $table->integer('total_messages')->default(0);
                $table->timestamps();

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');

                $table->foreign('company_id')
                    ->references('id')
                    ->on('companies')
                    ->onDelete('cascade');

                $table->unique(['user_id', 'company_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_user_memory');
        Schema::dropIfExists('ai_conversations');
    }
};
