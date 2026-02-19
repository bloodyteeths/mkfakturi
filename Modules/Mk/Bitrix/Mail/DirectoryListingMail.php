<?php

namespace Modules\Mk\Bitrix\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Outreach email to business software directories, accountant associations,
 * and tech blogs requesting Facturino be listed/reviewed.
 */
class DirectoryListingMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public string $directoryName;
    public string $contactName;
    public string $websiteUrl;
    public string $unsubscribeUrl;

    /**
     * Create a new message instance.
     *
     * @param string $directoryName Name of the directory/association/blog
     * @param string $contactName Contact person name (or "there")
     * @param string $websiteUrl Facturino website URL
     * @param string $unsubscribeUrl URL to unsubscribe
     */
    public function __construct(
        string $directoryName,
        string $contactName,
        string $websiteUrl,
        string $unsubscribeUrl
    ) {
        $this->directoryName = $directoryName;
        $this->contactName = $contactName;
        $this->websiteUrl = $websiteUrl;
        $this->unsubscribeUrl = $unsubscribeUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), 'Facturino')
            ->subject(__('outreach.directory.subject', ['directory' => $this->directoryName]))
            ->markdown('emails.outreach.directory_listing', [
                'directoryName' => $this->directoryName,
                'contactName' => $this->contactName,
                'websiteUrl' => $this->websiteUrl,
                'unsubscribeUrl' => $this->unsubscribeUrl,
            ]);
    }
}
