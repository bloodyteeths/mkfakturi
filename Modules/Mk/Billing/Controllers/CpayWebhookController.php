<?php

namespace Modules\Mk\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\CommissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\CpayDriver;

/**
 * CPAY Webhook Controller for Subscriptions
 *
 * Handles subscription-related callbacks from CPAY payment gateway
 */
class CpayWebhookController extends Controller
{
    protected CpayDriver $cpayDriver;

    public function __construct(CpayDriver $cpayDriver)
    {
        $this->cpayDriver = $cpayDriver;
    }

    /**
     * Handle subscription callbacks from CPAY
     *
     * @param Request $request
     * @return Response
     */
    public function handleSubscriptionCallback(Request $request): Response
    {
        try {
            // Verify signature
            if (!$this->cpayDriver->verifySignature($request->all())) {
                Log::error('CPAY subscription callback signature verification failed', [
                    'data' => $request->all(),
                ]);

                return response('Unauthorized', 401);
            }

            $eventType = $request->input('event_type');
            $data = $request->all();

            Log::info('CPAY subscription callback received', [
                'event_type' => $eventType,
                'subscription_ref' => $data['subscription_ref'] ?? null,
            ]);

            // Handle different callback events
            switch ($eventType) {
                case 'subscription_created':
                    return $this->handleSubscriptionCreated($data);

                case 'subscription_payment_succeeded':
                    return $this->handleSubscriptionPaymentSucceeded($data);

                case 'subscription_payment_failed':
                    return $this->handleSubscriptionPaymentFailed($data);

                case 'subscription_cancelled':
                    return $this->handleSubscriptionCancelled($data);

                default:
                    Log::info('Unhandled CPAY subscription event', ['event_type' => $eventType]);
                    return response('OK', 200);
            }

        } catch (\Exception $e) {
            Log::error('CPAY subscription callback processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            return response('Internal Server Error', 500);
        }
    }

    /**
     * Handle subscription created event
     *
     * @param array $data
     * @return Response
     */
    protected function handleSubscriptionCreated(array $data): Response
    {
        $companyId = $data['company_id'] ?? null;
        $tier = $data['tier'] ?? 'starter';
        $subscriptionRef = $data['subscription_ref'];

        if (!$companyId) {
            Log::error('Company ID missing in subscription created callback', $data);
            return response('Bad Request', 400);
        }

        $company = Company::find($companyId);

        if (!$company) {
            Log::error('Company not found for subscription', ['company_id' => $companyId]);
            return response('Company not found', 404);
        }

        // Create subscription record via Paddle's polymorphic relationship
        // Store CPAY subscription reference in metadata
        $subscription = $company->subscriptions()->create([
            'type' => 'default',
            'paddle_id' => 'cpay_' . $subscriptionRef, // Prefix to distinguish from Paddle
            'status' => 'active',
            'provider' => 'cpay',
            'tier' => $tier,
            'monthly_price' => $this->getTierPrice($tier),
            'metadata' => [
                'cpay_subscription_ref' => $subscriptionRef,
                'created_via' => 'cpay',
            ],
        ]);

        // Update company tier
        $company->update([
            'subscription_tier' => $tier,
        ]);

        Log::info('CPAY subscription created', [
            'company_id' => $companyId,
            'tier' => $tier,
            'subscription_ref' => $subscriptionRef,
            'subscription_id' => $subscription->id,
        ]);

        return response('OK', 200);
    }

    /**
     * Handle successful subscription payment
     *
     * @param array $data
     * @return Response
     */
    protected function handleSubscriptionPaymentSucceeded(array $data): Response
    {
        $subscriptionRef = $data['subscription_ref'];
        $amount = $data['amount'] ?? 0;
        $transactionId = $data['transaction_id'];

        // Find subscription by CPAY reference
        $subscription = \Laravel\Paddle\Subscription::where('paddle_id', 'cpay_' . $subscriptionRef)->first();

        if (!$subscription) {
            Log::error('Subscription not found for payment', ['subscription_ref' => $subscriptionRef]);
            return response('Subscription not found', 404);
        }

        $company = $subscription->billable;

        if ($company && $company instanceof Company) {
            // Trigger commission calculation for partners
            $this->triggerCommissionCalculation($company, $data);

            Log::info('CPAY subscription payment processed', [
                'company_id' => $company->id,
                'subscription_ref' => $subscriptionRef,
                'amount' => $amount,
                'transaction_id' => $transactionId,
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Handle failed subscription payment
     *
     * @param array $data
     * @return Response
     */
    protected function handleSubscriptionPaymentFailed(array $data): Response
    {
        $subscriptionRef = $data['subscription_ref'];

        // Find subscription by CPAY reference
        $subscription = \Laravel\Paddle\Subscription::where('paddle_id', 'cpay_' . $subscriptionRef)->first();

        if ($subscription) {
            // Update subscription status to past_due
            $subscription->update([
                'status' => 'past_due',
            ]);

            Log::warning('CPAY subscription payment failed', [
                'subscription_ref' => $subscriptionRef,
                'reason' => $data['failure_reason'] ?? 'Unknown',
            ]);
        }

        return response('OK', 200);
    }

    /**
     * Handle subscription cancelled event
     *
     * @param array $data
     * @return Response
     */
    protected function handleSubscriptionCancelled(array $data): Response
    {
        $subscriptionRef = $data['subscription_ref'];

        // Find subscription by CPAY reference
        $subscription = \Laravel\Paddle\Subscription::where('paddle_id', 'cpay_' . $subscriptionRef)->first();

        if ($subscription) {
            $company = $subscription->billable;

            // Mark subscription as cancelled
            $subscription->update([
                'status' => 'canceled',
                'ends_at' => now(),
            ]);

            // Downgrade company to free tier
            if ($company && $company instanceof Company) {
                $company->update([
                    'subscription_tier' => 'free',
                ]);

                Log::info('CPAY subscription cancelled, company downgraded', [
                    'company_id' => $company->id,
                    'subscription_ref' => $subscriptionRef,
                ]);
            }
        }

        return response('OK', 200);
    }

    /**
     * Get tier price
     *
     * @param string $tier
     * @return float
     */
    private function getTierPrice(string $tier): float
    {
        $prices = [
            'free' => 0,
            'starter' => 12,
            'standard' => 29,
            'business' => 59,
            'max' => 149,
        ];

        return $prices[$tier] ?? 0;
    }

    /**
     * Trigger commission calculation for partners
     *
     * @param Company $company
     * @param array $paymentData
     * @return void
     */
    private function triggerCommissionCalculation(Company $company, array $paymentData): void
    {
        try {
            $amount = $paymentData['amount'] ?? 0;
            $transactionId = $paymentData['transaction_id'] ?? null;

            // Calculate month reference (YYYY-MM format)
            $monthRef = now()->format('Y-m');

            // Get the CommissionService
            $commissionService = app(CommissionService::class);

            // Record recurring commission
            $result = $commissionService->recordRecurring(
                $company->id,
                $amount,
                $monthRef,
                $transactionId
            );

            if ($result['success']) {
                Log::info('Commission recorded for CPAY subscription payment', [
                    'company_id' => $company->id,
                    'amount' => $amount,
                    'event_id' => $result['event_id'],
                    'direct_commission' => $result['direct_commission'],
                    'upline_commission' => $result['upline_commission'],
                    'month_ref' => $monthRef,
                    'provider' => 'cpay',
                ]);
            } else {
                Log::warning('Commission not recorded for CPAY payment', [
                    'company_id' => $company->id,
                    'reason' => $result['message'] ?? 'Unknown',
                    'amount' => $amount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to record commission for CPAY payment', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
} // CLAUDE-CHECKPOINT
