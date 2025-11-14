<?php

namespace Modules\Mk\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Http\Controllers\WebhookController as CashierWebhookController;

/**
 * Paddle Webhook Controller for Subscriptions
 *
 * Extends Cashier's webhook controller to handle subscription events
 * for both company subscriptions and partner subscriptions
 */
class PaddleWebhookController extends CashierWebhookController
{
    /**
     * Handle subscription created event
     *
     * @param array $payload
     * @return Response
     */
    protected function handleSubscriptionCreated(array $payload): Response
    {
        Log::info('Paddle subscription created', [
            'subscription_id' => $payload['data']['id'] ?? null,
        ]);

        $data = $payload['data'];
        $customData = $data['custom_data'] ?? [];

        // Determine if this is a company or partner subscription
        if (isset($customData['company_id'])) {
            $this->handleCompanySubscriptionCreated($data, $customData);
        } elseif (isset($customData['user_id'])) {
            $this->handlePartnerSubscriptionCreated($data, $customData);
        }

        return $this->successMethod();
    }

    /**
     * Handle subscription updated event
     *
     * @param array $payload
     * @return Response
     */
    protected function handleSubscriptionUpdated(array $payload): Response
    {
        Log::info('Paddle subscription updated', [
            'subscription_id' => $payload['data']['id'] ?? null,
        ]);

        $data = $payload['data'];
        $customData = $data['custom_data'] ?? [];

        // Determine if this is a company or partner subscription
        if (isset($customData['company_id'])) {
            $this->handleCompanySubscriptionUpdated($data, $customData);
        } elseif (isset($customData['user_id'])) {
            $this->handlePartnerSubscriptionUpdated($data, $customData);
        }

        return $this->successMethod();
    }

    /**
     * Handle subscription payment succeeded
     *
     * @param array $payload
     * @return Response
     */
    protected function handleSubscriptionPaymentSucceeded(array $payload): Response
    {
        Log::info('Paddle subscription payment succeeded', [
            'subscription_id' => $payload['data']['subscription_id'] ?? null,
        ]);

        $data = $payload['data'];

        // Get subscription via Paddle ID
        $subscription = \Laravel\Paddle\Subscription::where('paddle_id', $data['subscription_id'])->first();

        if ($subscription) {
            // Check if this is a company subscription
            if ($subscription->billable_type === Company::class) {
                $company = $subscription->billable;

                if ($company) {
                    // Trigger commission calculation for partners
                    $this->triggerCommissionCalculation($company, $data);
                }
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle subscription cancelled event
     *
     * @param array $payload
     * @return Response
     */
    protected function handleSubscriptionCanceled(array $payload): Response
    {
        Log::info('Paddle subscription cancelled', [
            'subscription_id' => $payload['data']['id'] ?? null,
        ]);

        return $this->successMethod();
    }

    /**
     * Handle transaction completed event
     *
     * @param array $payload
     * @return Response
     */
    protected function handleTransactionCompleted(array $payload): Response
    {
        Log::info('Paddle transaction completed', [
            'transaction_id' => $payload['data']['id'] ?? null,
        ]);

        return $this->successMethod();
    }

    /**
     * Handle company subscription created
     *
     * @param array $data
     * @param array $customData
     * @return void
     */
    private function handleCompanySubscriptionCreated(array $data, array $customData): void
    {
        $companyId = $customData['company_id'];
        $tier = $customData['tier'] ?? 'starter';

        $company = Company::find($companyId);

        if ($company) {
            $company->update([
                'subscription_tier' => $tier,
            ]);

            Log::info('Company subscription tier updated', [
                'company_id' => $companyId,
                'tier' => $tier,
                'paddle_subscription_id' => $data['id'],
            ]);
        }
    }

    /**
     * Handle company subscription updated
     *
     * @param array $data
     * @param array $customData
     * @return void
     */
    private function handleCompanySubscriptionUpdated(array $data, array $customData): void
    {
        $companyId = $customData['company_id'];
        $status = $data['status'];

        $company = Company::find($companyId);

        if ($company) {
            // If subscription is cancelled or paused, potentially downgrade tier
            if (in_array($status, ['canceled', 'past_due'])) {
                $company->update([
                    'subscription_tier' => 'free',
                ]);

                Log::info('Company downgraded to free tier', [
                    'company_id' => $companyId,
                    'reason' => $status,
                ]);
            }
        }
    }

    /**
     * Handle partner subscription created
     *
     * @param array $data
     * @param array $customData
     * @return void
     */
    private function handlePartnerSubscriptionCreated(array $data, array $customData): void
    {
        $userId = $customData['user_id'];

        $user = User::find($userId);

        if ($user) {
            $user->update([
                'partner_subscription_tier' => 'plus',
            ]);

            Log::info('Partner upgraded to Plus', [
                'user_id' => $userId,
                'paddle_subscription_id' => $data['id'],
            ]);
        }
    }

    /**
     * Handle partner subscription updated
     *
     * @param array $data
     * @param array $customData
     * @return void
     */
    private function handlePartnerSubscriptionUpdated(array $data, array $customData): void
    {
        $userId = $customData['user_id'];
        $status = $data['status'];

        $user = User::find($userId);

        if ($user) {
            // If subscription is cancelled, downgrade to free
            if (in_array($status, ['canceled', 'past_due'])) {
                $user->update([
                    'partner_subscription_tier' => 'free',
                ]);

                Log::info('Partner downgraded to free tier', [
                    'user_id' => $userId,
                    'reason' => $status,
                ]);
            }
        }
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
        // Get active partners for this company
        $partners = $company->activePartners;

        if ($partners->isEmpty()) {
            return;
        }

        $amount = $paymentData['total'] ?? 0;
        $currency = $paymentData['currency'] ?? 'EUR';

        foreach ($partners as $partner) {
            // Calculate commission based on partner tier
            $commissionRate = $partner->partner_subscription_tier === 'plus' ? 0.22 : 0.18;
            $commissionAmount = $amount * $commissionRate;

            // Create commission record (assuming CommissionService exists)
            if (class_exists(\Modules\Mk\Services\CommissionService::class)) {
                $commissionService = app(\Modules\Mk\Services\CommissionService::class);
                $commissionService->recordCommission([
                    'partner_id' => $partner->id,
                    'company_id' => $company->id,
                    'amount' => $commissionAmount,
                    'currency' => $currency,
                    'transaction_id' => $paymentData['id'],
                    'subscription_payment' => true,
                ]);
            }

            Log::info('Commission calculated for subscription payment', [
                'partner_id' => $partner->id,
                'company_id' => $company->id,
                'amount' => $commissionAmount,
                'rate' => $commissionRate,
            ]);
        }
    }

    /**
     * Return successful response
     *
     * @return Response
     */
    private function successMethod(): Response
    {
        return new Response('Webhook handled', 200);
    }
} // CLAUDE-CHECKPOINT
