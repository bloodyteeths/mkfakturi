<?php

namespace App\Notifications;

use App\Models\ClientDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected ClientDocument $document;

    public function __construct(ClientDocument $document)
    {
        $this->document = $document;
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $docUrl = url("/admin/documents/{$this->document->id}/review");
        $classification = $this->document->ai_classification;
        $type = $classification['type'] ?? 'other';
        $summary = $classification['summary'] ?? $this->document->original_filename;

        $typeLabels = [
            'invoice' => 'Invoice / Фактура',
            'receipt' => 'Receipt / Фискална сметка',
            'bank_statement' => 'Bank Statement / Банкарски извод',
            'contract' => 'Contract / Договор',
            'tax_form' => 'Tax Form / Даночен образец',
            'other' => 'Document / Документ',
        ];

        $typeLabel = $typeLabels[$type] ?? $typeLabels['other'];

        $subject = $this->document->processing_status === ClientDocument::PROCESSING_FAILED
            ? __('documents.notification_failed_subject', ['file' => $this->document->original_filename])
            : __('documents.notification_subject', ['type' => $typeLabel]);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting(__('documents.notification_greeting'));

        if ($this->document->processing_status === ClientDocument::PROCESSING_FAILED) {
            $mail->line(__('documents.notification_failed_line', [
                'file' => $this->document->original_filename,
            ]))
                ->line($this->document->error_message ?? __('documents.notification_unknown_error'));
        } else {
            $mail->line(__('documents.notification_line1', [
                'file' => $this->document->original_filename,
                'type' => $typeLabel,
            ]))
                ->line($summary)
                ->line(__('documents.notification_line2'));
        }

        return $mail
            ->action(__('documents.notification_action'), $docUrl)
            ->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'document_id' => $this->document->id,
            'filename' => $this->document->original_filename,
            'type' => $this->document->ai_classification['type'] ?? 'other',
            'status' => $this->document->processing_status,
        ];
    }
} // CLAUDE-CHECKPOINT
