<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compensations')) {
            Schema::create('compensations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('compensation_number', 50);
                $table->date('compensation_date');
                $table->enum('partner_type', ['customer', 'supplier']);
                $table->unsignedBigInteger('partner_id');
                $table->unsignedBigInteger('our_debt_amount')->default(0);
                $table->unsignedBigInteger('their_debt_amount')->default(0);
                $table->unsignedBigInteger('offset_amount')->default(0);
                $table->unsignedBigInteger('remaining_our_debt')->default(0);
                $table->unsignedBigInteger('remaining_their_debt')->default(0);
                $table->text('notes')->nullable();
                $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
                $table->timestamp('confirmed_at')->nullable();
                $table->unsignedBigInteger('creator_id')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('restrict');
                $table->unique(['company_id', 'compensation_number']);
                $table->index(['company_id', 'compensation_date']);
            });
        }

        if (!Schema::hasTable('compensation_items')) {
            Schema::create('compensation_items', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->unsignedBigInteger('compensation_id');
                $table->enum('side', ['our_debt', 'their_debt']);
                $table->string('document_type', 50);
                $table->unsignedBigInteger('document_id')->nullable();
                $table->string('document_number', 100);
                $table->date('document_date');
                $table->unsignedBigInteger('original_amount');
                $table->unsignedBigInteger('offset_amount');
                $table->timestamps();

                $table->foreign('compensation_id')->references('id')->on('compensations')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compensation_items');
        Schema::dropIfExists('compensations');
    }
};
// CLAUDE-CHECKPOINT
