<?php

namespace Modules\Mk\Bitrix\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Mail\OutreachInitialMail;
use Modules\Mk\Bitrix\Mail\OutreachFollowUp1Mail;
use Modules\Mk\Bitrix\Mail\OutreachFollowUp2Mail;
use Modules\Mk\Bitrix\Mail\PartnerInviteMail;

/**
 * PostmarkOutreachService
 *
 * Handles sending outreach and transactional emails via Postmark API.
 * Uses separate message streams for cold outreach vs invites.
 * Enforces rate limits to protect sender reputation.
 *
 * @see https://postmarkapp.com/developer/api/overview
 */
class PostmarkOutreachService
{
    /**
     * Postmark API base URL.
     */
    protected const API_BASE_URL = 'https://api.postmarkapp.com';

    /**
     * Postmark server API token.
     */
    protected ?string $apiToken;

    /**
     * Message stream for cold outreach emails.
     */
    protected string $streamOutreach;

    /**
     * Message stream for transactional emails (invites).
     */
    protected string $streamTransactional;

    /**
     * Daily send limit.
     */
    protected int $dailyLimit;

    /**
     * Hourly send limit.
     */
    protected int $hourlyLimit;

    /**
     * Create a new PostmarkOutreachService instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->apiToken = config('services.postmark.token') ?? '';
        $this->streamOutreach = config('bitrix.postmark.stream_outreach', 'outreach') ?? 'outreach';
        $this->streamTransactional = config('bitrix.postmark.stream_transactional', 'transactional') ?? 'transactional';
        $this->dailyLimit = (int) (config('bitrix.outreach.daily_limit', 100) ?? 100);
        $this->hourlyLimit = (int) (config('bitrix.outreach.hourly_limit', 20) ?? 20);
    }

    /**
     * Send an outreach email using a template.
     *
     * @param string $email Recipient email
     * @param string $templateKey Template key (initial, followup_1, followup_2)
     * @param array $data Template data (companyName, demoUrl, etc.)
     * @param string $unsubscribeUrl Unsubscribe link
     * @return string|null Postmark message ID or null on failure
     */
    public function sendOutreachEmail(
        string $email,
        string $templateKey,
        array $data,
        string $unsubscribeUrl
    ): ?string {
        if (!$this->isWithinDailyLimit() || !$this->isWithinHourlyLimit()) {
            Log::warning('Outreach rate limit exceeded', [
                'email' => $email,
                'template' => $templateKey,
            ]);

            return null;
        }

        try {
            $companyName = $data['companyName'] ?? 'Your Company';
            $demoUrl = $data['demoUrl'] ?? config('app.url') . '/demo';

            // Build the appropriate mailable
            $mailable = $this->buildMailable(
                $templateKey,
                $companyName,
                $email,
                $demoUrl,
                $unsubscribeUrl
            );

            if (!$mailable) {
                Log::error('Unknown outreach template', ['template' => $templateKey]);
                return null;
            }

            // Set message stream header for outreach
            $mailable->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader(
                    'X-PM-Message-Stream',
                    $this->streamOutreach
                );
            });

            // Send via Laravel Mail (uses Postmark driver)
            Mail::to($email)->send($mailable);

            // Get the message ID from the response
            // Note: Laravel's Postmark transport returns the message ID in the sent event
            $messageId = $this->getLastMessageId();

            Log::info('Outreach email sent', [
                'email' => $email,
                'template' => $templateKey,
                'message_id' => $messageId,
            ]);

            return $messageId;

        } catch (\Exception $e) {
            Log::error('Failed to send outreach email', [
                'email' => $email,
                'template' => $templateKey,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Send a partner invite email (transactional).
     *
     * @param string $email Partner email
     * @param string $companyName Partner/company name
     * @param string $inviteUrl Activation URL
     * @return string|null Postmark message ID or null on failure
     */
    public function sendPartnerInvite(
        string $email,
        string $companyName,
        string $inviteUrl
    ): ?string {
        try {
            $mailable = new PartnerInviteMail($companyName, $email, $inviteUrl);

            // Set message stream header for transactional
            $mailable->withSymfonyMessage(function ($message) {
                $message->getHeaders()->addTextHeader(
                    'X-PM-Message-Stream',
                    $this->streamTransactional
                );
            });

            Mail::to($email)->send($mailable);

            $messageId = $this->getLastMessageId();

            Log::info('Partner invite email sent', [
                'email' => $email,
                'company' => $companyName,
                'message_id' => $messageId,
            ]);

            return $messageId;

        } catch (\Exception $e) {
            Log::error('Failed to send partner invite email', [
                'email' => $email,
                'company' => $companyName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if we're within the daily send limit.
     *
     * @return bool
     */
    public function isWithinDailyLimit(): bool
    {
        $sentToday = OutreachSend::today()->count();

        return $sentToday < $this->dailyLimit;
    }

    /**
     * Check if we're within the hourly send limit.
     *
     * @return bool
     */
    public function isWithinHourlyLimit(): bool
    {
        $sentThisHour = OutreachSend::lastHour()->count();

        return $sentThisHour < $this->hourlyLimit;
    }

    /**
     * Get remaining quota for sending.
     *
     * @return array{daily: int, hourly: int}
     */
    public function getRemainingQuota(): array
    {
        $sentToday = OutreachSend::today()->count();
        $sentThisHour = OutreachSend::lastHour()->count();

        return [
            'daily' => max(0, $this->dailyLimit - $sentToday),
            'hourly' => max(0, $this->hourlyLimit - $sentThisHour),
        ];
    }

    /**
     * Send email directly via Postmark API (for templates not in Laravel Mail).
     *
     * @param string $to Recipient email
     * @param string $from Sender email
     * @param string $subject Email subject
     * @param string $htmlBody HTML body
     * @param string $textBody Plain text body
     * @param string $stream Message stream
     * @param array $headers Additional headers
     * @return string|null Postmark message ID or null on failure
     */
    public function sendRawEmail(
        string $to,
        string $from,
        string $subject,
        string $htmlBody,
        string $textBody,
        string $stream = 'outreach',
        array $headers = []
    ): ?string {
        try {
            $payload = [
                'From' => $from,
                'To' => $to,
                'Subject' => $subject,
                'HtmlBody' => $htmlBody,
                'TextBody' => $textBody,
                'MessageStream' => $stream,
            ];

            // Add List-Unsubscribe header if provided
            if (!empty($headers['List-Unsubscribe'])) {
                $payload['Headers'] = [
                    [
                        'Name' => 'List-Unsubscribe',
                        'Value' => $headers['List-Unsubscribe'],
                    ],
                    [
                        'Name' => 'List-Unsubscribe-Post',
                        'Value' => 'List-Unsubscribe=One-Click',
                    ],
                ];
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Postmark-Server-Token' => $this->apiToken,
            ])->post(self::API_BASE_URL . '/email', $payload);

            if ($response->successful()) {
                $data = $response->json();
                $messageId = $data['MessageID'] ?? null;

                Log::info('Raw Postmark email sent', [
                    'to' => $to,
                    'subject' => $subject,
                    'message_id' => $messageId,
                ]);

                return $messageId;
            }

            Log::error('Postmark API error', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to send raw Postmark email', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build the appropriate mailable for a template key.
     *
     * @param string $templateKey
     * @param string $companyName
     * @param string $email
     * @param string $demoUrl
     * @param string $unsubscribeUrl
     * @return \Illuminate\Mail\Mailable|null
     */
    protected function buildMailable(
        string $templateKey,
        string $companyName,
        string $email,
        string $demoUrl,
        string $unsubscribeUrl
    ): ?\Illuminate\Mail\Mailable {
        return match ($templateKey) {
            'initial' => new OutreachInitialMail($companyName, $email, $demoUrl, $unsubscribeUrl),
            'followup_1' => new OutreachFollowUp1Mail($companyName, $email, $demoUrl, $unsubscribeUrl),
            'followup_2' => new OutreachFollowUp2Mail($companyName, $email, $demoUrl, $unsubscribeUrl),
            default => null,
        };
    }

    /**
     * Get the last sent message ID.
     *
     * Note: This is a placeholder. In production, you would capture
     * the message ID from the Mail::sent event or use the raw API.
     *
     * @return string|null
     */
    protected function getLastMessageId(): ?string
    {
        // Generate a tracking ID for now
        // In production, capture from Postmark response via event listener
        return 'pm-' . uniqid();
    }

    /**
     * Get email delivery statistics from Postmark.
     *
     * @param string $tag Optional tag filter
     * @param string $fromDate Start date (YYYY-MM-DD)
     * @param string $toDate End date (YYYY-MM-DD)
     * @return array|null
     */
    public function getDeliveryStats(
        ?string $tag = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): ?array {
        try {
            $params = [];

            if ($tag) {
                $params['tag'] = $tag;
            }
            if ($fromDate) {
                $params['fromdate'] = $fromDate;
            }
            if ($toDate) {
                $params['todate'] = $toDate;
            }

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'X-Postmark-Server-Token' => $this->apiToken,
            ])->get(self::API_BASE_URL . '/stats/outbound', $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get Postmark delivery stats', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}

// CLAUDE-CHECKPOINT
