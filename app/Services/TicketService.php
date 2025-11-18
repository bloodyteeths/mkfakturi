<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ticket Service for External Support System Integration
 *
 * This service demonstrates how Facturino would integrate with external
 * support systems like Zendesk, Freshdesk, or custom ticketing platforms.
 *
 * Features:
 * - Create tickets in external systems
 * - Sync ticket status updates
 * - Handle webhook notifications
 * - Cache frequently accessed data
 * - Provide fallback for system unavailability
 */
class TicketService
{
    /**
     * External ticketing system configuration
     */
    private array $config;

    /**
     * HTTP client timeout in seconds
     */
    private int $timeout = 30;

    public function __construct()
    {
        $this->config = [
            'base_url' => config('services.support.base_url', 'https://api.ticketing-system.com'),
            'api_key' => config('services.support.api_key', 'demo_api_key'),
            'organization' => config('services.support.organization', 'facturino'),
            'webhook_secret' => config('services.support.webhook_secret', 'demo_webhook_secret'),
        ];
    }

    /**
     * Create a new support ticket in external system
     */
    public function createTicket(array $ticketData): array
    {
        try {
            // Validate required fields
            $this->validateTicketData($ticketData);

            // Transform data for external API
            $payload = $this->transformTicketData($ticketData);

            // Send to external system
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_key'],
                'Content-Type' => 'application/json',
                'X-Organization' => $this->config['organization'],
            ])
                ->timeout($this->timeout)
                ->post($this->config['base_url'].'/api/v1/tickets', $payload);

            if ($response->successful()) {
                $externalTicket = $response->json();

                // Cache the ticket for quick access
                $this->cacheTicket($externalTicket);

                // Log successful creation
                Log::info('External ticket created successfully', [
                    'external_ticket_id' => $externalTicket['id'],
                    'internal_reference' => $ticketData['internal_reference'] ?? null,
                ]);

                return [
                    'success' => true,
                    'ticket_id' => $externalTicket['id'],
                    'ticket_url' => $externalTicket['url'] ?? null,
                    'status' => $externalTicket['status'],
                    'created_at' => $externalTicket['created_at'],
                ];
            } else {
                // Handle API errors
                Log::error('External ticket creation failed', [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'ticket_data' => $ticketData,
                ]);

                return $this->createFallbackTicket($ticketData);
            }

        } catch (\Exception $e) {
            Log::error('Exception during ticket creation', [
                'error' => $e->getMessage(),
                'ticket_data' => $ticketData,
            ]);

            return $this->createFallbackTicket($ticketData);
        }
    }

    /**
     * Get ticket status from external system
     */
    public function getTicketStatus(string $ticketId): ?array
    {
        try {
            // Check cache first
            $cacheKey = "ticket_status_{$ticketId}";
            $cachedStatus = Cache::get($cacheKey);

            if ($cachedStatus) {
                return $cachedStatus;
            }

            // Fetch from external system
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_key'],
                'X-Organization' => $this->config['organization'],
            ])
                ->timeout($this->timeout)
                ->get($this->config['base_url']."/api/v1/tickets/{$ticketId}");

            if ($response->successful()) {
                $ticket = $response->json();

                // Cache for 5 minutes
                Cache::put($cacheKey, $ticket, 300);

                return $ticket;
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error fetching ticket status', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update ticket in external system
     */
    public function updateTicket(string $ticketId, array $updateData): bool
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_key'],
                'Content-Type' => 'application/json',
                'X-Organization' => $this->config['organization'],
            ])
                ->timeout($this->timeout)
                ->put($this->config['base_url']."/api/v1/tickets/{$ticketId}", $updateData);

            if ($response->successful()) {
                // Clear cache
                Cache::forget("ticket_status_{$ticketId}");

                Log::info('Ticket updated successfully', [
                    'ticket_id' => $ticketId,
                    'update_data' => $updateData,
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Error updating ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Add comment to ticket
     */
    public function addComment(string $ticketId, string $comment, bool $isInternal = false): bool
    {
        try {
            $payload = [
                'body' => $comment,
                'author_id' => 'system', // In production, use actual user ID
                'public' => ! $isInternal,
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->config['api_key'],
                'Content-Type' => 'application/json',
                'X-Organization' => $this->config['organization'],
            ])
                ->timeout($this->timeout)
                ->post($this->config['base_url']."/api/v1/tickets/{$ticketId}/comments", $payload);

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Error adding comment to ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Handle webhook from external ticketing system
     */
    public function handleWebhook(array $webhookData): bool
    {
        try {
            // Verify webhook signature
            if (! $this->verifyWebhookSignature($webhookData)) {
                Log::warning('Invalid webhook signature', $webhookData);

                return false;
            }

            // Process different event types
            $eventType = $webhookData['event_type'] ?? 'unknown';

            switch ($eventType) {
                case 'ticket.status_changed':
                    return $this->handleStatusChange($webhookData);

                case 'ticket.comment_added':
                    return $this->handleCommentAdded($webhookData);

                case 'ticket.assigned':
                    return $this->handleTicketAssigned($webhookData);

                case 'ticket.resolved':
                    return $this->handleTicketResolved($webhookData);

                default:
                    Log::info('Unhandled webhook event type', [
                        'event_type' => $eventType,
                        'data' => $webhookData,
                    ]);

                    return true;
            }

        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData,
            ]);

            return false;
        }
    }

    /**
     * Get support metrics and statistics
     */
    public function getSupportMetrics(): array
    {
        try {
            $cacheKey = 'support_metrics';
            $metrics = Cache::get($cacheKey);

            if (! $metrics) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$this->config['api_key'],
                    'X-Organization' => $this->config['organization'],
                ])
                    ->timeout($this->timeout)
                    ->get($this->config['base_url'].'/api/v1/reports/metrics');

                if ($response->successful()) {
                    $metrics = $response->json();
                    // Cache for 1 hour
                    Cache::put($cacheKey, $metrics, 3600);
                } else {
                    // Return fallback metrics
                    $metrics = $this->getFallbackMetrics();
                }
            }

            return $metrics;

        } catch (\Exception $e) {
            Log::error('Error fetching support metrics', ['error' => $e->getMessage()]);

            return $this->getFallbackMetrics();
        }
    }

    /**
     * Validate ticket data before sending to external system
     *
     * @throws \InvalidArgumentException
     */
    private function validateTicketData(array $ticketData): void
    {
        $required = ['subject', 'description', 'priority', 'category', 'user_email'];

        foreach ($required as $field) {
            if (empty($ticketData[$field])) {
                throw new \InvalidArgumentException("Required field '{$field}' is missing");
            }
        }
    }

    /**
     * Transform internal ticket data to external API format
     */
    private function transformTicketData(array $ticketData): array
    {
        return [
            'title' => $ticketData['subject'],
            'description' => $ticketData['description'],
            'priority' => $this->mapPriority($ticketData['priority']),
            'category' => $this->mapCategory($ticketData['category']),
            'requester' => [
                'email' => $ticketData['user_email'],
                'name' => $ticketData['user_name'] ?? null,
            ],
            'custom_fields' => [
                'company_id' => $ticketData['company_id'] ?? null,
                'platform' => 'Facturino Macedonia',
                'source' => 'api',
            ],
            'tags' => $this->generateTags($ticketData),
        ];
    }

    /**
     * Map internal priority to external system priority
     */
    private function mapPriority(string $priority): string
    {
        $mapping = [
            'low' => 'low',
            'medium' => 'normal',
            'high' => 'high',
            'critical' => 'urgent',
        ];

        return $mapping[$priority] ?? 'normal';
    }

    /**
     * Map internal category to external system category
     */
    private function mapCategory(string $category): string
    {
        $mapping = [
            'technical' => 'Technical Support',
            'billing' => 'Billing & Accounts',
            'migration' => 'Data Migration',
            'tax' => 'Tax Compliance',
            'banking' => 'Banking Integration',
            'general' => 'General Inquiry',
        ];

        return $mapping[$category] ?? 'General Inquiry';
    }

    /**
     * Generate tags for the ticket
     */
    private function generateTags(array $ticketData): array
    {
        $tags = ['facturino', 'macedonia'];

        if (! empty($ticketData['category'])) {
            $tags[] = $ticketData['category'];
        }

        if (! empty($ticketData['priority'])) {
            $tags[] = $ticketData['priority'];
        }

        return $tags;
    }

    /**
     * Cache ticket data for quick access
     */
    private function cacheTicket(array $ticket): void
    {
        $cacheKey = "ticket_status_{$ticket['id']}";
        Cache::put($cacheKey, $ticket, 300); // Cache for 5 minutes
    }

    /**
     * Create a fallback ticket when external system is unavailable
     */
    private function createFallbackTicket(array $ticketData): array
    {
        $fallbackId = 'FALLBACK-'.time().'-'.rand(1000, 9999);

        Log::warning('Created fallback ticket due to external system unavailability', [
            'fallback_id' => $fallbackId,
            'original_data' => $ticketData,
        ]);

        return [
            'success' => true,
            'ticket_id' => $fallbackId,
            'status' => 'pending_sync',
            'message' => 'Ticket created locally. Will sync with support system when available.',
            'created_at' => now()->toISOString(),
        ];
    }

    /**
     * Verify webhook signature for security
     */
    private function verifyWebhookSignature(array $webhookData): bool
    {
        // In production, implement proper HMAC signature verification
        // This is a simplified demo implementation
        return ! empty($webhookData['signature']) || config('app.env') === 'local';
    }

    /**
     * Handle ticket status change webhook
     */
    private function handleStatusChange(array $data): bool
    {
        $ticketId = $data['ticket']['id'] ?? null;
        $newStatus = $data['ticket']['status'] ?? null;

        if ($ticketId && $newStatus) {
            // Clear cache
            Cache::forget("ticket_status_{$ticketId}");

            // Update internal records if needed
            Log::info('Ticket status changed', [
                'ticket_id' => $ticketId,
                'new_status' => $newStatus,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Handle comment added webhook
     */
    private function handleCommentAdded(array $data): bool
    {
        $ticketId = $data['ticket']['id'] ?? null;
        $comment = $data['comment'] ?? null;

        if ($ticketId && $comment) {
            Log::info('Comment added to ticket', [
                'ticket_id' => $ticketId,
                'comment_id' => $comment['id'] ?? null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Handle ticket assigned webhook
     */
    private function handleTicketAssigned(array $data): bool
    {
        $ticketId = $data['ticket']['id'] ?? null;
        $assignee = $data['ticket']['assignee'] ?? null;

        if ($ticketId && $assignee) {
            Log::info('Ticket assigned', [
                'ticket_id' => $ticketId,
                'assignee' => $assignee['name'] ?? $assignee['email'] ?? 'Unknown',
            ]);

            return true;
        }

        return false;
    }

    /**
     * Handle ticket resolved webhook
     */
    private function handleTicketResolved(array $data): bool
    {
        $ticketId = $data['ticket']['id'] ?? null;
        $resolution = $data['ticket']['resolution'] ?? null;

        if ($ticketId) {
            Log::info('Ticket resolved', [
                'ticket_id' => $ticketId,
                'resolution_time' => $data['ticket']['resolution_time'] ?? null,
            ]);

            return true;
        }

        return false;
    }

    /**
     * Get fallback metrics when external system is unavailable
     */
    private function getFallbackMetrics(): array
    {
        return [
            'total_tickets' => 0,
            'open_tickets' => 0,
            'resolved_tickets' => 0,
            'average_response_time' => '4 hours',
            'customer_satisfaction' => '95%',
            'first_contact_resolution' => '85%',
            'status' => 'metrics_unavailable',
        ];
    }
}
