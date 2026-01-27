<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserDataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DataExportReadyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public User $user;
    public UserDataExport $export;
    public string $downloadUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, UserDataExport $export)
    {
        $this->user = $user;
        $this->export = $export;
        $this->downloadUrl = url('/admin/settings/privacy-data');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $fileSizeMb = round($this->export->file_size / 1024 / 1024, 2);

        return $this->from(config('mail.from.address'), config('mail.from.name'))
            ->subject(__('data_export.email_subject'))
            ->markdown('emails.data-export-ready', [
                'user' => $this->user,
                'downloadUrl' => $this->downloadUrl,
                'fileSizeMb' => $fileSizeMb,
                'expiresAt' => $this->export->expires_at->format('d.m.Y'),
            ]);
    }
}
// CLAUDE-CHECKPOINT
