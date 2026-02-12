<?php

namespace App\Notifications;

use App\Models\Deadline;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Deadline Reminder Notification (P8-02)
 *
 * Sent to company owners and managing partners when a deadline
 * is approaching. Supports both mail and database channels.
 *
 * Subject format (MK): "Потсетник: {title} - рок {due_date}"
 * Fallback (EN): "Reminder: {title} - due {due_date}"
 */
class DeadlineReminderNotification extends Notification
{
    use Queueable;

    protected Deadline $deadline;

    /**
     * Create a new notification instance.
     */
    public function __construct(Deadline $deadline)
    {
        $this->deadline = $deadline;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $deadline = $this->deadline;
        $companyName = $deadline->company->name ?? 'Unknown';
        $dueDate = $deadline->due_date->format('d.m.Y');
        $daysRemaining = $deadline->days_remaining;
        $title = $deadline->title_mk ?? $deadline->title;
        $typeLabel = $deadline->type_label;

        // Determine urgency
        $isUrgent = $daysRemaining <= 1;
        $subjectPrefix = $isUrgent ? 'ИТНО' : 'Потсетник';
        $subject = "{$subjectPrefix}: {$title} - рок {$dueDate}";

        $message = (new MailMessage)
            ->subject($subject);

        if ($isUrgent) {
            $message->line("**ИТНО**: Рокот за **{$title}** за {$companyName} е **денес** или **утре**!");
        } else {
            $message->line("Потсетник за претстојниот рок за **{$title}** за {$companyName}.");
        }

        $message->line('')
            ->line("**Детали за рокот:**")
            ->line("- Тип: {$typeLabel}")
            ->line("- Компанија: {$companyName}")
            ->line("- Краен рок: {$dueDate}")
            ->line("- Преостанати денови: {$daysRemaining}");

        if ($deadline->description) {
            $message->line('')
                ->line("**Опис:** {$deadline->description}");
        }

        $message->action('Преглед на рокови', url('/admin/partner/deadlines'))
            ->line('')
            ->line('---')
            ->line("*Reminder: {$deadline->title} for {$companyName} is due on {$dueDate} ({$daysRemaining} days remaining).*");

        return $message;
    }

    /**
     * Get the array representation of the notification (for database channel).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'deadline_id' => $this->deadline->id,
            'company_id' => $this->deadline->company_id,
            'company_name' => $this->deadline->company->name ?? null,
            'title' => $this->deadline->title,
            'title_mk' => $this->deadline->title_mk,
            'deadline_type' => $this->deadline->deadline_type,
            'due_date' => $this->deadline->due_date->toDateString(),
            'days_remaining' => $this->deadline->days_remaining,
            'status' => $this->deadline->status,
        ];
    }
}
// CLAUDE-CHECKPOINT
