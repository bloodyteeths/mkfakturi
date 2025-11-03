<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\CpayDriver;

/**
 * CPAY Payment Gateway Callback Controller
 *
 * Handles payment callbacks from CPAY (CASYS) payment gateway.
 * This controller is called by CPAY after payment completion.
 *
 * Security Features:
 * - SHA256 signature verification
 * - Idempotency check (prevents duplicate processing)
 * - Feature flag protection
 * - Comprehensive logging
 *
 * @version 1.0.0
 * @author Claude Code - CPAY Integration Agent
 */
class CpayCallbackController extends Controller
{
    /**
     * The CPAY driver instance
     *
     * @var CpayDriver
     */
    protected $cpayDriver;

    /**
     * Create a new controller instance
     */
    public function __construct()
    {
        $this->cpayDriver = new CpayDriver();
    }

    /**
     * Handle CPAY payment callback
     *
     * Expected POST parameters from CPAY:
     * - merchant_id: Merchant identifier
     * - amount: Payment amount
     * - currency: Payment currency (MKD)
     * - order_id: Invoice number
     * - transaction_id: Unique transaction identifier
     * - status: Payment status (APPROVED, DECLINED, etc.)
     * - signature: SHA256 signature for verification
     *
     * @param Request $request Callback request from CPAY
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        try {
            // Log incoming callback
            Log::info('CPAY callback received', [
                'ip' => $request->ip(),
                'data' => $request->all(),
            ]);

            // Check feature flag
            if (!config('mk.features.advanced_payments', false)) {
                Log::warning('CPAY callback rejected - feature disabled');
                return response('Feature disabled', 403);
            }

            // Validate required parameters
            $this->validateCallbackRequest($request);

            // Process callback through driver
            $this->cpayDriver->handleCallback($request);

            // Return success response to CPAY
            Log::info('CPAY callback processed successfully', [
                'transaction_id' => $request->input('transaction_id'),
                'order_id' => $request->input('order_id'),
            ]);

            return response('OK', 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Invoice not found
            Log::error('CPAY callback failed - invoice not found', [
                'order_id' => $request->input('order_id'),
                'error' => $e->getMessage(),
            ]);

            return response('Invoice not found', 404);

        } catch (\Exception $e) {
            // General error (signature verification, etc.)
            Log::error('CPAY callback processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Callback processing failed: ' . $e->getMessage(), 400);
        }
    }

    /**
     * Validate callback request parameters
     *
     * @param Request $request
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateCallbackRequest(Request $request): void
    {
        $required = [
            'merchant_id',
            'amount',
            'currency',
            'order_id',
            'transaction_id',
            'signature',
        ];

        foreach ($required as $field) {
            if (!$request->has($field)) {
                throw new \InvalidArgumentException("Missing required callback field: {$field}");
            }
        }

        // Validate merchant ID matches
        if ($request->input('merchant_id') !== config('mk.payment_gateways.cpay.merchant_id')) {
            throw new \InvalidArgumentException('Invalid merchant ID');
        }

        // Validate currency is MKD
        if ($request->input('currency') !== 'MKD') {
            throw new \InvalidArgumentException('Invalid currency - only MKD is supported');
        }

        // Validate amount is numeric and positive
        $amount = $request->input('amount');
        if (!is_numeric($amount) || $amount <= 0) {
            throw new \InvalidArgumentException('Invalid payment amount');
        }
    }
}
// CLAUDE-CHECKPOINT
