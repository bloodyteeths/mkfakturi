<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Viber Business Notification Service.
 *
 * Sends messages via the Viber REST API.
 * Registration at partners.viber.com (free, self-service).
 *
 * @see https://developers.viber.com/docs/api/rest-bot-api/
 */
class ViberNotificationService
{
    protected string $apiUrl = 'https://chatapi.viber.com/pa';

    protected ?string $authToken;

    protected ?string $senderName;

    protected ?string $senderAvatar;

    public function __construct()
    {
        $this->authToken = config('mk.viber.auth_token');
        $this->senderName = config('mk.viber.sender_name', 'Facturino');
        $this->senderAvatar = config('mk.viber.sender_avatar');
    }

    /**
     * Check if Viber notifications are enabled and configured.
     */
    public function isEnabled(): bool
    {
        return config('mk.viber.enabled', false) && ! empty($this->authToken);
    }

    /**
     * Send a text message to a Viber user.
     *
     * @param  string  $receiverId  Viber user ID or phone number
     * @param  string  $text  Message text
     * @param  string|null  $trackingData  Optional tracking data
     * @return array{success: bool, message_token: string|null, error: string|null}
     */
    public function sendMessage(string $receiverId, string $text, ?string $trackingData = null): array
    {
        if (! $this->isEnabled()) {
            return ['success' => false, 'message_token' => null, 'error' => 'Viber notifications are not enabled'];
        }

        $payload = [
            'receiver' => $receiverId,
            'type' => 'text',
            'text' => $text,
            'sender' => [
                'name' => $this->senderName,
            ],
        ];

        if ($this->senderAvatar) {
            $payload['sender']['avatar'] = $this->senderAvatar;
        }

        if ($trackingData) {
            $payload['tracking_data'] = $trackingData;
        }

        return $this->makeRequest('send_message', $payload);
    }

    /**
     * Send a rich message with a button (e.g., "View Invoice" link).
     *
     * @param  string  $receiverId  Viber user ID
     * @param  string  $text  Message text
     * @param  string  $buttonText  Button label
     * @param  string  $buttonUrl  URL to open on button click
     * @return array
     */
    public function sendRichMessage(string $receiverId, string $text, string $buttonText, string $buttonUrl): array
    {
        if (! $this->isEnabled()) {
            return ['success' => false, 'message_token' => null, 'error' => 'Viber notifications are not enabled'];
        }

        $payload = [
            'receiver' => $receiverId,
            'type' => 'rich_media',
            'min_api_version' => 7,
            'rich_media' => [
                'Type' => 'rich_media',
                'ButtonsGroupColumns' => 6,
                'ButtonsGroupRows' => 5,
                'Buttons' => [
                    [
                        'ActionType' => 'none',
                        'ActionBody' => 'none',
                        'Text' => '<font size="14">'.$text.'</font>',
                        'TextSize' => 'medium',
                        'TextVAlign' => 'middle',
                        'TextHAlign' => 'left',
                        'Columns' => 6,
                        'Rows' => 3,
                    ],
                    [
                        'ActionType' => 'open-url',
                        'ActionBody' => $buttonUrl,
                        'Text' => '<font color="#ffffff">'.$buttonText.'</font>',
                        'TextSize' => 'large',
                        'TextVAlign' => 'middle',
                        'TextHAlign' => 'center',
                        'BgColor' => '#4f46e5',
                        'Columns' => 6,
                        'Rows' => 1,
                    ],
                ],
            ],
            'sender' => [
                'name' => $this->senderName,
            ],
        ];

        return $this->makeRequest('send_message', $payload);
    }

    /**
     * Test the connection by getting account info.
     *
     * @return array{success: bool, account_name: string|null, error: string|null}
     */
    public function testConnection(): array
    {
        $result = $this->makeRequest('get_account_info', []);

        if ($result['success']) {
            return [
                'success' => true,
                'account_name' => $result['response']['name'] ?? null,
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'account_name' => null,
            'error' => $result['error'],
        ];
    }

    /**
     * Make an API request to Viber.
     *
     * @param  string  $endpoint  API endpoint (e.g., 'send_message')
     * @param  array  $payload  Request body
     * @return array{success: bool, message_token: string|null, response: array|null, error: string|null}
     */
    protected function makeRequest(string $endpoint, array $payload): array
    {
        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withHeaders([
                    'X-Viber-Auth-Token' => $this->authToken,
                ])
                ->post("{$this->apiUrl}/{$endpoint}", $payload);

            $body = $response->json();

            if (($body['status'] ?? -1) === 0) {
                return [
                    'success' => true,
                    'message_token' => $body['message_token'] ?? null,
                    'response' => $body,
                    'error' => null,
                ];
            }

            $errorMsg = $body['status_message'] ?? 'Unknown Viber API error';

            Log::warning('ViberNotificationService: API error', [
                'endpoint' => $endpoint,
                'status' => $body['status'] ?? null,
                'error' => $errorMsg,
            ]);

            return [
                'success' => false,
                'message_token' => null,
                'response' => $body,
                'error' => $errorMsg,
            ];
        } catch (\Throwable $e) {
            Log::error('ViberNotificationService: Request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_token' => null,
                'response' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
// CLAUDE-CHECKPOINT
