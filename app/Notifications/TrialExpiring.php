<?php

namespace App\Notifications;

use App\Models\CompanySubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiring extends Notification
{
    use Queueable;

    protected CompanySubscription $subscription;
    protected int $daysRemaining;

    /**
     * Create a new notification instance.
     */
    public function __construct(CompanySubscription $subscription, int $daysRemaining)
    {
        $this->subscription = $subscription;
        $this->daysRemaining = $daysRemaining;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $companyName = $this->subscription->company->name;
        $planName = ucfirst($this->subscription->plan);
        $paddleCheckoutUrl = config('services.paddle.checkout_url');

        $message = (new MailMessage)
            ->subject("Your {$planName} trial ends in {$this->daysRemaining} " . ($this->daysRemaining == 1 ? 'day' : 'days'));

        if ($this->daysRemaining === 0) {
            $message->line("Your {$planName} trial for {$companyName} ends **today**!")
                ->line('Upgrade now to keep your premium features including:')
                ->line('- E-Faktura sending with QES signatures')
                ->line('- Up to 3 users')
                ->line('- 200 invoices per month')
                ->line("If you don't upgrade, your account will be downgraded to the Free plan, and you'll lose access to these features.")
                ->action('Upgrade to Standard - €29/month', $paddleCheckoutUrl)
                ->line('All your data will remain safe and accessible.');
        } else {
            $message->line("Your {$planName} trial for {$companyName} will expire in **{$this->daysRemaining} " . ($this->daysRemaining == 1 ? 'day' : 'days') . "**.")
                ->line('Upgrade now to continue enjoying:')
                ->line('- E-Faktura sending with QES signatures')
                ->line('- Up to 3 users')
                ->line('- 200 invoices per month')
                ->action('Upgrade to Standard - €29/month', $paddleCheckoutUrl)
                ->line('Questions? Contact our support team.');
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'company_id' => $this->subscription->company_id,
            'plan' => $this->subscription->plan,
            'days_remaining' => $this->daysRemaining,
            'trial_ends_at' => $this->subscription->trial_ends_at,
        ];
    }
}
