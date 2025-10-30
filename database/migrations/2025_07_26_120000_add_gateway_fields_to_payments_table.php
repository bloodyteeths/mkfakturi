<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add Gateway Integration Fields to Payments Table
 * 
 * Adds fields required for payment gateway integration including:
 * - gateway: Payment gateway identifier (cpay, paddle, bank_transfer, manual)
 * - gateway_order_id: External order ID from gateway
 * - gateway_transaction_id: External transaction ID from gateway
 * - gateway_status: Payment status from gateway
 * - gateway_data: JSON storage for gateway-specific data
 * - gateway_response: JSON storage for gateway callback responses
 * 
 * Required for CPAY-02 implementation to integrate CPAY driver
 * with existing payment processing architecture.
 * 
 * @version 1.0.0
 * @created 2025-07-26 - CPAY-02 gateway integration
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Payment gateway identifier
            $table->string('gateway', 50)->nullable()->after('notes')
                ->comment('Payment gateway: cpay, paddle, bank_transfer, manual');
            
            // Gateway order/transaction identifiers
            $table->string('gateway_order_id')->nullable()->after('gateway')
                ->comment('External order ID from payment gateway');
            
            $table->string('gateway_transaction_id')->nullable()->after('gateway_order_id')
                ->comment('External transaction ID from payment gateway');
            
            // Gateway status tracking
            $table->string('gateway_status', 50)->nullable()->after('gateway_transaction_id')
                ->comment('Payment status from gateway: PENDING, PROCESSING, COMPLETED, FAILED, CANCELLED');
            
            // JSON storage for gateway-specific data
            $table->json('gateway_data')->nullable()->after('gateway_status')
                ->comment('JSON storage for gateway request data and configuration');
            
            $table->json('gateway_response')->nullable()->after('gateway_data')
                ->comment('JSON storage for gateway callback responses and metadata');
            
            // Indexes for performance
            $table->index(['gateway', 'gateway_status'], 'payments_gateway_status_idx');
            $table->index('gateway_order_id', 'payments_gateway_order_idx');
            $table->index('gateway_transaction_id', 'payments_gateway_transaction_idx');
        });
        
        // Log the migration
        \Illuminate\Support\Facades\Log::info('Payment gateway fields migration completed', [
            'migration' => '2025_07_26_120000_add_gateway_fields_to_payments_table',
            'fields_added' => [
                'gateway', 
                'gateway_order_id', 
                'gateway_transaction_id', 
                'gateway_status', 
                'gateway_data', 
                'gateway_response'
            ],
            'indexes_added' => [
                'payments_gateway_status_idx',
                'payments_gateway_order_idx', 
                'payments_gateway_transaction_idx'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('payments_gateway_status_idx');
            $table->dropIndex('payments_gateway_order_idx');
            $table->dropIndex('payments_gateway_transaction_idx');
            
            // Drop gateway fields
            $table->dropColumn([
                'gateway',
                'gateway_order_id',
                'gateway_transaction_id', 
                'gateway_status',
                'gateway_data',
                'gateway_response'
            ]);
        });
        
        \Illuminate\Support\Facades\Log::info('Payment gateway fields migration rolled back', [
            'migration' => '2025_07_26_120000_add_gateway_fields_to_payments_table'
        ]);
    }
};

