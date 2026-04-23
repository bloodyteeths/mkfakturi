<?php

namespace Modules\Mk\Bitrix\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Mail\OutreachInitialMail;
use Modules\Mk\Bitrix\Mail\OutreachFollowUp1Mail;
use Modules\Mk\Bitrix\Mail\OutreachFollowUp2Mail;
use Modules\Mk\Bitrix\Mail\OutreachFollowUp3Mail;
use Modules\Mk\Bitrix\Mail\OutreachFollowUp4Mail;
use Modules\Mk\Bitrix\Mail\CompanyOutreachInitialMail;
use Modules\Mk\Bitrix\Mail\CompanyFollowUp1Mail;
use Modules\Mk\Bitrix\Mail\CompanyFollowUp2Mail;
use Modules\Mk\Bitrix\Mail\CompanyFollowUp3Mail;
use Modules\Mk\Bitrix\Mail\CompanyClickerReengageMail;
use Modules\Mk\Bitrix\Mail\CompanyFollowUp4Mail;
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
        $this->dailyLimit = (int) (config('bitrix.outreach.daily_limit', 0) ?? 0);
        $this->hourlyLimit = (int) (config('bitrix.outreach.hourly_limit', 0) ?? 0);
    }

    /**
     * Send an outreach email using a template.
     *
     * @param string $email Recipient email
     * @param string $templateKey Template key (initial, followup_1, followup_2, followup_3, followup_4)
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
            $appUrl = rtrim(config('app.url', 'https://app.facturino.mk'), '/');

            $ctaUrl = $appUrl . '/signup?' . http_build_query([
                'email' => base64_encode($email),
                'utm_source' => 'outreach',
                'utm_medium' => 'email',
                'utm_campaign' => $templateKey,
            ]);

            // Build the appropriate mailable
            $mailable = $this->buildMailable(
                $templateKey,
                $companyName,
                $email,
                $ctaUrl,
                $unsubscribeUrl
            );

            if (!$mailable) {
                Log::error('Unknown outreach template', ['template' => $templateKey]);
                return null;
            }

            // Force Macedonian locale for all outreach emails
            $mailable->locale('mk');

            // Send via Laravel Mail (uses Postmark driver)
            // Company emails → broadcast stream, accountant emails → outreach stream
            $isCompanyTemplate = str_starts_with($templateKey, 'company_');
            $stream = $isCompanyTemplate ? 'broadcast' : $this->streamOutreach;
            $mailable->withSymfonyMessage(function ($message) use ($stream) {
                $message->getHeaders()->addTextHeader(
                    'X-PM-Message-Stream',
                    $stream
                );
            });

            // Use sendNow() to avoid Closure serialization errors if Mailable
            // implements ShouldQueue — withSymfonyMessage() Closures can't be serialized
            $sentMessage = Mail::to($email)->sendNow($mailable);

            // Extract real Postmark MessageID from the SentMessage
            $messageId = $this->extractMessageId($sentMessage);

            Log::info('Outreach email sent', [
                'email' => $email,
                'template' => $templateKey,
                'message_id' => $messageId,
            ]);

            return $messageId;

        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();

            // Postmark 406: Inactive recipient (previously bounced/complained)
            // Auto-suppress to prevent infinite retries
            if (str_contains(strtolower($errorMsg), 'not allowed to send')
                || str_contains(strtolower($errorMsg), 'inactive recipient')
                || str_contains($errorMsg, '406')) {
                Suppression::fromBounce($email, "Postmark inactive: {$errorMsg}");
                Log::warning('Auto-suppressed inactive Postmark recipient', [
                    'email' => $email,
                    'template' => $templateKey,
                ]);
            }

            Log::error('Failed to send outreach email', [
                'email' => $email,
                'template' => $templateKey,
                'error' => $errorMsg,
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

            $sentMessage = Mail::to($email)->sendNow($mailable);

            $messageId = $this->extractMessageId($sentMessage);

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
        string $signupUrl,
        string $unsubscribeUrl
    ): ?\Illuminate\Mail\Mailable {
        return match ($templateKey) {
            // Accountant templates
            'initial' => new OutreachInitialMail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'followup_1' => new OutreachFollowUp1Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'followup_2' => new OutreachFollowUp2Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'followup_3' => new OutreachFollowUp3Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'followup_4' => new OutreachFollowUp4Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            // Company templates
            'company_initial' => new CompanyOutreachInitialMail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'company_followup_1' => new CompanyFollowUp1Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'company_followup_2' => new CompanyFollowUp2Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'company_followup_3' => new CompanyFollowUp3Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'company_followup_4' => new CompanyFollowUp4Mail($companyName, $email, $signupUrl, $unsubscribeUrl),
            'company_clicker_reengage' => new CompanyClickerReengageMail($companyName, $email, $signupUrl, $unsubscribeUrl),
            default => null,
        };
    }

    /**
     * Extract the Postmark MessageID from a SentMessage.
     *
     * The Postmark Symfony transport sets the MessageID on the SentMessage
     * after a successful API call. Format: UUID (e.g. "87196cc2-2d62-...").
     *
     * @param \Illuminate\Mail\SentMessage|null $sentMessage
     * @return string|null
     */
    protected function extractMessageId($sentMessage): ?string
    {
        if (!$sentMessage) {
            return null;
        }

        $messageId = $sentMessage->getMessageId();

        if (!$messageId) {
            return null;
        }

        // Symfony may wrap the ID in angle brackets: <uuid@server>
        $messageId = trim($messageId, '<>');

        // Strip @domain suffix if present (Symfony format: uuid@server.postmarkapp.com)
        if (str_contains($messageId, '@')) {
            $messageId = explode('@', $messageId)[0];
        }

        return $messageId;
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

