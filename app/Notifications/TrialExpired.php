<?php

namespace App\Notifications;

use App\Models\CompanySubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpired extends Notification
{
    use Queueable;

    protected CompanySubscription $subscription;

    /**
     * Create a new notification instance.
     */
    public function __construct(CompanySubscription $subscription)
    {
        $this->subscription = $subscription;
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
        $paddleCheckoutUrl = config('services.paddle.checkout_url');

        return (new MailMessage)
            ->subject('Your trial has ended')
            ->line("Your Standard trial for {$companyName} has ended.")
            ->line('Your account has been downgraded to the **Free** plan.')
            ->line('')
            ->line('**What this means:**')
            ->line('- You can create up to 5 invoices per month')
            ->line('- Only 1 user is allowed')
            ->line('- E-Faktura and QES signing are disabled')
            ->line('')
            ->line('**All your data is safe!** Your invoices, customers, and settings remain intact.')
            ->line('')
            ->line('Upgrade anytime to regain full access:')
            ->line('- **Standard** (€29/month): E-Faktura, 3 users, 200 invoices')
            ->line('- **Business** (€59/month): Bank feeds, 5 users, 1,000 invoices')
            ->line('- **Max** (€149/month): Unlimited everything')
            ->action('Upgrade Now', $paddleCheckoutUrl)
            ->line('Thank you for trying Facturino!');
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
            'previous_plan' => $this->subscription->plan,
            'expired_at' => now(),
        ];
    }
}
