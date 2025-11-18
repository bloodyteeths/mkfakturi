<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('canned_responses')) {
            return;
        }

        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255); // E.g., "Thank you for contacting support"
            $table->text('content'); // The template content
            $table->string('category', 50)->nullable(); // E.g., "greeting", "billing", "technical"
            $table->boolean('is_active')->default(true);
            $table->integer('usage_count')->default(0); // Track how many times used
            $table->unsignedBigInteger('created_by')->nullable(); // User who created it
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
            $table->index('created_by');

            // Foreign keys
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });

        // Seed default canned responses
        DB::table('canned_responses')->insert([
            [
                'title' => 'Thank You for Contacting Support',
                'content' => 'Thank you for contacting Facturino support. We have received your inquiry and will respond within 24 hours. If this is urgent, please mark it as high priority.',
                'category' => 'greeting',
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Investigating Your Issue',
                'content' => 'We are currently investigating your issue. Our team is looking into this and will get back to you shortly with an update.',
                'category' => 'general',
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Issue Resolved',
                'content' => 'We\'re glad to inform you that your issue has been resolved. Please let us know if you need any further assistance.',
                'category' => 'general',
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Subscription Billing Inquiry',
                'content' => 'Thank you for your billing inquiry. Your current subscription is [TIER] at [AMOUNT] per month. Your next billing date is [DATE]. Please let us know if you have any questions.',
                'category' => 'billing',
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Technical Support Escalated',
                'content' => 'Your technical issue has been escalated to our engineering team. We will provide an update within 48 hours. Thank you for your patience.',
                'category' => 'technical',
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Feature Request Acknowledged',
                'content' => 'Thank you for your feature request! We\'ve added it to our product roadmap for consideration. We\'ll notify you if it\'s scheduled for development.',
                'category' => 'feature_request',
                'is_active' => true,
                'usage_count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('canned_responses');
    }
};
// CLAUDE-CHECKPOINT
