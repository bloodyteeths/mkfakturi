<?php

namespace Modules\Mk\Bitrix\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HubSpot API Client
 *
 * HTTP client for HubSpot CRM API using private app access token.
 * Handles contacts, companies, deals, associations, and engagements.
 *
 * @see https://developers.hubspot.com/docs/api/overview
 */
class HubSpotApiClient
{
    /**
     * HubSpot API base URL.
     */
    protected const BASE_URL = 'https://api.hubapi.com';

    /**
     * Private app access token.
     */
    protected ?string $accessToken;

    /**
     * Deal pipeline ID for outreach.
     */
    protected string $pipelineId;

    /**
     * Deal stages configuration.
     */
    protected array $dealStages;

    /**
     * Timestamp of last API request (for rate limiting).
     */
    protected ?float $lastRequestTime = null;

    /**
     * Minimum delay between requests in milliseconds (100ms = 10 req/sec).
     */
    protected int $minRequestDelayMs = 100;

    /**
     * Create a new HubSpotApiClient instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->accessToken = config('hubspot.access_token') ?? '';
        // Support both hubspot.pipeline and hubspot.pipeline_id for compatibility
        $this->pipelineId = config('hubspot.pipeline') ?? config('hubspot.pipeline_id', 'default') ?? 'default';
        $this->dealStages = config('hubspot.deal_stages', []) ?? [];
    }

    /**
     * Make a request to the HubSpot API.
     *
     * @param string $method HTTP method (GET, POST, PATCH, DELETE)
     * @param string $endpoint API endpoint (e.g., '/crm/v3/objects/contacts')
     * @param array $data Request body data
     * @param array $query Query parameters
     * @return array Response data
     *
     * @throws \Exception If API call fails
     */
    public function request(string $method, string $endpoint, array $data = [], array $query = []): array
    {
        $this->enforceRateLimit();

        $url = self::BASE_URL . $endpoint;

        Log::info('HubSpot API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'has_data' => !empty($data),
        ]);

        try {
            $request = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Content-Type' => 'application/json',
                ]);

            if (!empty($query)) {
                $request = $request->withQueryString($query);
            }

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $data),
                'PATCH' => $request->patch($url, $data),
                'DELETE' => $request->delete($url),
                default => throw new \Exception("Unsupported HTTP method: {$method}"),
            };

            $this->lastRequestTime = microtime(true);

            $responseData = $response->json() ?? [];

            // Check for HubSpot API errors
            if ($response->failed()) {
                $errorMessage = $responseData['message'] ?? 'Unknown error';
                $errorCategory = $responseData['category'] ?? 'UNKNOWN';

                Log::error('HubSpot API error', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'error' => $errorMessage,
                    'category' => $errorCategory,
                ]);

                // Handle rate limiting (429)
                if ($response->status() === 429) {
                    Log::warning('HubSpot rate limit exceeded, waiting 1 second');
                    usleep(1000000);
                    return $this->request($method, $endpoint, $data, $query);
                }

                throw new \Exception("HubSpot API error: {$errorCategory} - {$errorMessage}");
            }

            Log::info('HubSpot API response', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return $responseData;

        } catch (\Exception $e) {
            Log::error('HubSpot API request failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create or update a contact by email.
     *
     * @param string $email Contact email
     * @param array $properties Contact properties
     * @return array{id: string, properties: array}|null Contact data or null on failure
     */
    public function upsertContact(string $email, array $properties = []): ?array
    {
        try {
            // Search for existing contact by email
            $existingContact = $this->findContactByEmail($email);

            $properties['email'] = $email;

            if ($existingContact) {
                // Update existing contact
                $response = $this->request('PATCH', "/crm/v3/objects/contacts/{$existingContact['id']}", [
                    'properties' => $properties,
                ]);

                Log::info('HubSpot contact updated', [
                    'contact_id' => $response['id'],
                    'email' => $email,
                ]);

                return $response;
            }

            // Create new contact
            $response = $this->request('POST', '/crm/v3/objects/contacts', [
                'properties' => $properties,
            ]);

            Log::info('HubSpot contact created', [
                'contact_id' => $response['id'],
                'email' => $email,
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to upsert HubSpot contact', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a contact by email.
     *
     * @param string $email Contact email
     * @return array|null Contact data or null if not found
     */
    public function findContactByEmail(string $email): ?array
    {
        try {
            $response = $this->request('POST', '/crm/v3/objects/contacts/search', [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'propertyName' => 'email',
                                'operator' => 'EQ',
                                'value' => $email,
                            ],
                        ],
                    ],
                ],
            ]);

            $results = $response['results'] ?? [];

            return !empty($results) ? $results[0] : null;

        } catch (\Exception $e) {
            Log::error('Failed to find HubSpot contact by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create or update a company by domain.
     *
     * @param string $domain Company domain
     * @param array $properties Company properties
     * @return array{id: string, properties: array}|null Company data or null on failure
     */
    public function upsertCompany(string $domain, array $properties = []): ?array
    {
        try {
            // Search for existing company by domain
            $existingCompany = $this->findCompanyByDomain($domain);

            $properties['domain'] = $domain;

            if ($existingCompany) {
                // Update existing company
                $response = $this->request('PATCH', "/crm/v3/objects/companies/{$existingCompany['id']}", [
                    'properties' => $properties,
                ]);

                Log::info('HubSpot company updated', [
                    'company_id' => $response['id'],
                    'domain' => $domain,
                ]);

                return $response;
            }

            // Create new company
            $response = $this->request('POST', '/crm/v3/objects/companies', [
                'properties' => $properties,
            ]);

            Log::info('HubSpot company created', [
                'company_id' => $response['id'],
                'domain' => $domain,
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to upsert HubSpot company', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find a company by domain.
     *
     * @param string $domain Company domain
     * @return array|null Company data or null if not found
     */
    public function findCompanyByDomain(string $domain): ?array
    {
        try {
            $response = $this->request('POST', '/crm/v3/objects/companies/search', [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'propertyName' => 'domain',
                                'operator' => 'EQ',
                                'value' => $domain,
                            ],
                        ],
                    ],
                ],
            ]);

            $results = $response['results'] ?? [];

            return !empty($results) ? $results[0] : null;

        } catch (\Exception $e) {
            Log::error('Failed to find HubSpot company by domain', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create or update a deal.
     *
     * @param string|null $dealId Existing deal ID (null for create)
     * @param array $properties Deal properties
     * @return array{id: string, properties: array}|null Deal data or null on failure
     */
    public function upsertDeal(?string $dealId, array $properties = []): ?array
    {
        try {
            // Set pipeline if not specified
            if (!isset($properties['pipeline'])) {
                $properties['pipeline'] = $this->pipelineId;
            }

            if ($dealId) {
                // Update existing deal
                $response = $this->request('PATCH', "/crm/v3/objects/deals/{$dealId}", [
                    'properties' => $properties,
                ]);

                Log::info('HubSpot deal updated', [
                    'deal_id' => $response['id'],
                ]);

                return $response;
            }

            // Create new deal
            $response = $this->request('POST', '/crm/v3/objects/deals', [
                'properties' => $properties,
            ]);

            Log::info('HubSpot deal created', [
                'deal_id' => $response['id'],
            ]);

            return $response;

        } catch (\Exception $e) {
            Log::error('Failed to upsert HubSpot deal', [
                'deal_id' => $dealId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update deal stage.
     *
     * @param string $dealId Deal ID
     * @param string $stage Stage ID
     * @return bool True on success
     */
    public function updateDealStage(string $dealId, string $stage): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/objects/deals/{$dealId}", [
                'properties' => [
                    'dealstage' => $stage,
                ],
            ]);

            Log::info('HubSpot deal stage updated', [
                'deal_id' => $dealId,
                'stage' => $stage,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot deal stage', [
                'deal_id' => $dealId,
                'stage' => $stage,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get a deal by ID.
     *
     * @param string $dealId Deal ID
     * @return array|null Deal data or null if not found
     */
    public function getDeal(string $dealId): ?array
    {
        try {
            return $this->request('GET', "/crm/v3/objects/deals/{$dealId}");
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot deal', [
                'deal_id' => $dealId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Associate a contact with a company.
     *
     * @param string $contactId Contact ID
     * @param string $companyId Company ID
     * @return bool True on success
     */
    public function associateContactToCompany(string $contactId, string $companyId): bool
    {
        try {
            $this->request('PUT', "/crm/v3/objects/contacts/{$contactId}/associations/companies/{$companyId}/contact_to_company", []);

            Log::info('HubSpot contact associated to company', [
                'contact_id' => $contactId,
                'company_id' => $companyId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to associate HubSpot contact to company', [
                'contact_id' => $contactId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Associate a deal with a contact.
     *
     * @param string $dealId Deal ID
     * @param string $contactId Contact ID
     * @return bool True on success
     */
    public function associateDealToContact(string $dealId, string $contactId): bool
    {
        try {
            $this->request('PUT', "/crm/v3/objects/deals/{$dealId}/associations/contacts/{$contactId}/deal_to_contact", []);

            Log::info('HubSpot deal associated to contact', [
                'deal_id' => $dealId,
                'contact_id' => $contactId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to associate HubSpot deal to contact', [
                'deal_id' => $dealId,
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Associate a deal with a company.
     *
     * @param string $dealId Deal ID
     * @param string $companyId Company ID
     * @return bool True on success
     */
    public function associateDealToCompany(string $dealId, string $companyId): bool
    {
        try {
            $this->request('PUT', "/crm/v3/objects/deals/{$dealId}/associations/companies/{$companyId}/deal_to_company", []);

            Log::info('HubSpot deal associated to company', [
                'deal_id' => $dealId,
                'company_id' => $companyId,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to associate HubSpot deal to company', [
                'deal_id' => $dealId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create an email engagement.
     *
     * @param string $contactId Contact ID to associate
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string|null $fromEmail Sender email
     * @param string|null $toEmail Recipient email
     * @return string|null Engagement ID or null on failure
     */
    public function createEmailEngagement(
        string $contactId,
        string $subject,
        string $body,
        ?string $fromEmail = null,
        ?string $toEmail = null
    ): ?string {
        try {
            $timestamp = (int) (microtime(true) * 1000);

            $properties = [
                'hs_timestamp' => $timestamp,
                'hs_email_direction' => 'EMAIL',
                'hs_email_subject' => $subject,
                'hs_email_text' => strip_tags($body),
                'hs_email_html' => $body,
                'hs_email_status' => 'SENT',
            ];

            if ($fromEmail) {
                $properties['hs_email_sender_email'] = $fromEmail;
            }

            if ($toEmail) {
                $properties['hs_email_to_email'] = $toEmail;
            }

            $response = $this->request('POST', '/crm/v3/objects/emails', [
                'properties' => $properties,
            ]);

            $emailId = $response['id'] ?? null;

            if ($emailId) {
                // Associate email with contact
                $this->request('PUT', "/crm/v3/objects/emails/{$emailId}/associations/contacts/{$contactId}/email_to_contact", []);

                Log::info('HubSpot email engagement created', [
                    'email_id' => $emailId,
                    'contact_id' => $contactId,
                    'subject' => $subject,
                ]);
            }

            return $emailId;

        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot email engagement', [
                'contact_id' => $contactId,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a note engagement.
     *
     * @param string $contactId Contact ID to associate
     * @param string $body Note body
     * @return string|null Note ID or null on failure
     */
    public function createNote(string $contactId, string $body): ?string
    {
        try {
            $timestamp = (int) (microtime(true) * 1000);

            $response = $this->request('POST', '/crm/v3/objects/notes', [
                'properties' => [
                    'hs_timestamp' => $timestamp,
                    'hs_note_body' => $body,
                ],
            ]);

            $noteId = $response['id'] ?? null;

            if ($noteId) {
                // Associate note with contact
                $this->request('PUT', "/crm/v3/objects/notes/{$noteId}/associations/contacts/{$contactId}/note_to_contact", []);

                Log::info('HubSpot note created', [
                    'note_id' => $noteId,
                    'contact_id' => $contactId,
                ]);
            }

            return $noteId;

        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot note', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get the configured deal stage ID.
     *
     * @param string $stageName Stage name (e.g., 'new', 'emailed', 'interested')
     * @return string|null Stage ID or null if not found
     */
    public function getStageId(string $stageName): ?string
    {
        return $this->dealStages[$stageName] ?? null;
    }

    /**
     * Create a task associated with a contact.
     *
     * @param string $contactId Contact ID to associate
     * @param string $subject Task subject
     * @param string $body Task body/description
     * @param \DateTimeInterface|null $dueDate Due date (defaults to end of today)
     * @return string|null Task ID or null on failure
     */
    public function createTask(
        string $contactId,
        string $subject,
        string $body = '',
        ?\DateTimeInterface $dueDate = null
    ): ?string {
        try {
            $timestamp = (int) (microtime(true) * 1000);
            $dueDate = $dueDate ?? now()->endOfDay();

            $response = $this->request('POST', '/crm/v3/objects/tasks', [
                'properties' => [
                    'hs_timestamp' => $timestamp,
                    'hs_task_body' => $body,
                    'hs_task_subject' => $subject,
                    'hs_task_status' => 'NOT_STARTED',
                    'hs_task_priority' => 'HIGH',
                    'hs_task_type' => 'CALL',
                ],
            ]);

            $taskId = $response['id'] ?? null;

            if ($taskId) {
                // Associate task with contact
                $this->request('PUT', "/crm/v3/objects/tasks/{$taskId}/associations/contacts/{$contactId}/task_to_contact", []);

                Log::info('HubSpot task created', [
                    'task_id' => $taskId,
                    'contact_id' => $contactId,
                    'subject' => $subject,
                ]);
            }

            return $taskId;

        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot task', [
                'contact_id' => $contactId,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get a deal by ID with specific properties.
     *
     * @param string $dealId Deal ID
     * @param array $properties Properties to fetch
     * @return array|null Deal data or null if not found
     */
    public function getDealWithProperties(string $dealId, array $properties = []): ?array
    {
        try {
            $query = [];
            if (!empty($properties)) {
                $query['properties'] = implode(',', $properties);
            }

            return $this->request('GET', "/crm/v3/objects/deals/{$dealId}", [], $query);
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot deal with properties', [
                'deal_id' => $dealId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update deal properties.
     *
     * @param string $dealId Deal ID
     * @param array $properties Properties to update
     * @return bool True on success
     */
    public function updateDeal(string $dealId, array $properties): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/objects/deals/{$dealId}", [
                'properties' => $properties,
            ]);

            Log::info('HubSpot deal updated', [
                'deal_id' => $dealId,
                'properties_updated' => array_keys($properties),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot deal', [
                'deal_id' => $dealId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get associated contact for a deal.
     *
     * @param string $dealId Deal ID
     * @return string|null Contact ID or null if not found
     */
    public function getContactIdForDeal(string $dealId): ?string
    {
        try {
            $response = $this->request('GET', "/crm/v3/objects/deals/{$dealId}/associations/contacts", []);

            $results = $response['results'] ?? [];

            if (empty($results)) {
                return null;
            }

            // Return first associated contact
            return (string) ($results[0]['id'] ?? null);

        } catch (\Exception $e) {
            Log::error('Failed to get contact for deal', [
                'deal_id' => $dealId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Search deals by filter.
     *
     * @param array $filters Filter conditions
     * @param array $properties Properties to fetch
     * @param int $limit Max results to return
     * @return array Array of deals
     */
    public function searchDeals(array $filters, array $properties = [], int $limit = 100): array
    {
        try {
            $params = [
                'filterGroups' => [
                    [
                        'filters' => $filters,
                    ],
                ],
                'limit' => $limit,
            ];

            if (!empty($properties)) {
                $params['properties'] = $properties;
            }

            $response = $this->request('POST', '/crm/v3/objects/deals/search', $params);

            return $response['results'] ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to search HubSpot deals', [
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get deals in a specific stage without a partner ID.
     *
     * @param string $stageName Stage name from config (e.g., 'interested')
     * @return array Array of deals
     */
    public function getDealsInStageWithoutPartner(string $stageName): array
    {
        $stageId = $this->dealStages[$stageName] ?? null;

        if (!$stageId) {
            Log::warning('Unknown deal stage', ['stage' => $stageName]);
            return [];
        }

        return $this->searchDeals(
            [
                [
                    'propertyName' => 'dealstage',
                    'operator' => 'EQ',
                    'value' => $stageId,
                ],
                [
                    'propertyName' => 'facturino_partner_id',
                    'operator' => 'NOT_HAS_PROPERTY',
                ],
            ],
            ['dealname', 'facturino_lead_id', 'facturino_partner_id', 'dealstage']
        );
    }

    /**
     * Log a note to a HubSpot contact (alias for createNote).
     *
     * @param string $contactId Contact ID
     * @param string $noteBody Note content
     * @return string|null Note ID or null on failure
     */
    public function logNote(string $contactId, string $noteBody): ?string
    {
        return $this->createNote($contactId, $noteBody);
    }

    /**
     * Log an email activity.
     *
     * POST /crm/v3/objects/emails
     *
     * @param string $contactId Contact ID to associate
     * @param array $emailData Email data (subject, body, timestamp, status)
     * @return string|null Email engagement ID or null on failure
     */
    public function logEmail(string $contactId, array $emailData): ?string
    {
        try {
            $timestamp = $emailData['timestamp'] ?? (int) (microtime(true) * 1000);

            $response = $this->request('POST', '/crm/v3/objects/emails', [
                'properties' => [
                    'hs_timestamp' => $timestamp,
                    'hs_email_direction' => 'EMAIL', // outgoing
                    'hs_email_subject' => $emailData['subject'] ?? '',
                    'hs_email_text' => $emailData['body'] ?? '',
                    'hs_email_status' => $emailData['status'] ?? 'SENT',
                ],
                'associations' => [[
                    'to' => ['id' => $contactId],
                    'types' => [['associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 198]], // email_to_contact
                ]],
            ]);

            $emailId = $response['id'] ?? null;

            if ($emailId) {
                Log::info('HubSpot email logged', [
                    'email_id' => $emailId,
                    'contact_id' => $contactId,
                    'subject' => $emailData['subject'] ?? 'N/A',
                ]);
            }

            return $emailId;

        } catch (\Exception $e) {
            Log::error('Failed to log HubSpot email', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Log a call.
     *
     * POST /crm/v3/objects/calls
     *
     * @param string $contactId Contact ID to associate
     * @param array $callData Call data (title, body, status, duration, timestamp)
     * @return string|null Call ID or null on failure
     */
    public function logCall(string $contactId, array $callData): ?string
    {
        try {
            $timestamp = $callData['timestamp'] ?? (int) (microtime(true) * 1000);

            $response = $this->request('POST', '/crm/v3/objects/calls', [
                'properties' => [
                    'hs_timestamp' => $timestamp,
                    'hs_call_title' => $callData['title'] ?? 'Call',
                    'hs_call_body' => $callData['body'] ?? '',
                    'hs_call_status' => $callData['status'] ?? 'COMPLETED',
                    'hs_call_duration' => $callData['duration'] ?? 0,
                ],
                'associations' => [[
                    'to' => ['id' => $contactId],
                    'types' => [['associationCategory' => 'HUBSPOT_DEFINED', 'associationTypeId' => 194]], // call_to_contact
                ]],
            ]);

            $callId = $response['id'] ?? null;

            if ($callId) {
                Log::info('HubSpot call logged', [
                    'call_id' => $callId,
                    'contact_id' => $contactId,
                ]);
            }

            return $callId;

        } catch (\Exception $e) {
            Log::error('Failed to log HubSpot call', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ==================== PIPELINES ====================

    /**
     * Get all deal pipelines.
     *
     * GET /crm/v3/pipelines/deals
     *
     * @return array Array of pipelines
     */
    public function getPipelines(): array
    {
        try {
            $response = $this->request('GET', '/crm/v3/pipelines/deals');
            return $response['results'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot pipelines', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get pipeline stages.
     *
     * @param string $pipelineId Pipeline ID
     * @return array Array of stages
     */
    public function getPipelineStages(string $pipelineId): array
    {
        try {
            $response = $this->request('GET', "/crm/v3/pipelines/deals/{$pipelineId}");
            return $response['stages'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot pipeline stages', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ==================== PROPERTIES (Custom Fields) ====================

    /**
     * Create a custom property.
     *
     * POST /crm/v3/properties/{objectType}
     *
     * @param string $objectType Object type (contacts, companies, deals)
     * @param array $propertyData Property definition
     * @return bool True on success
     */
    public function createProperty(string $objectType, array $propertyData): bool
    {
        try {
            $this->request('POST', "/crm/v3/properties/{$objectType}", $propertyData);

            Log::info('HubSpot property created', [
                'object_type' => $objectType,
                'property_name' => $propertyData['name'] ?? 'N/A',
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot property', [
                'object_type' => $objectType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all properties for an object type.
     *
     * @param string $objectType Object type (contacts, companies, deals)
     * @return array Array of properties
     */
    public function getProperties(string $objectType): array
    {
        try {
            $response = $this->request('GET', "/crm/v3/properties/{$objectType}");
            return $response['results'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot properties', [
                'object_type' => $objectType,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Check if property exists.
     *
     * @param string $objectType Object type (contacts, companies, deals)
     * @param string $propertyName Property name
     * @return bool True if exists
     */
    public function propertyExists(string $objectType, string $propertyName): bool
    {
        $properties = $this->getProperties($objectType);
        foreach ($properties as $prop) {
            if ($prop['name'] === $propertyName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Search deals by stage.
     *
     * POST /crm/v3/objects/deals/search
     *
     * @param string $stage Deal stage ID
     * @param string|null $pipeline Pipeline ID (optional)
     * @return array Array of deals
     */
    public function getDealsByStage(string $stage, ?string $pipeline = null): array
    {
        $filters = [[
            'propertyName' => 'dealstage',
            'operator' => 'EQ',
            'value' => $stage,
        ]];

        if ($pipeline) {
            $filters[] = [
                'propertyName' => 'pipeline',
                'operator' => 'EQ',
                'value' => $pipeline,
            ];
        }

        return $this->searchDeals(
            $filters,
            ['dealname', 'dealstage', 'pipeline', 'amount', 'facturino_lead_id', 'facturino_partner_id']
        );
    }

    /**
     * Get contact by ID.
     *
     * GET /crm/v3/objects/contacts/{contactId}
     *
     * @param string $contactId Contact ID
     * @param array $properties Properties to retrieve (empty = defaults)
     * @return array|null Contact data or null if not found
     */
    public function getContact(string $contactId, array $properties = []): ?array
    {
        try {
            $query = [];
            if (!empty($properties)) {
                $query['properties'] = implode(',', $properties);
            }

            return $this->request('GET', "/crm/v3/objects/contacts/{$contactId}", [], $query);
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot contact', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a contact.
     *
     * POST /crm/v3/objects/contacts
     *
     * @param array $properties Contact properties (email, firstname, lastname, phone, etc.)
     * @return string|null Contact ID or null on failure
     */
    public function createContact(array $properties): ?string
    {
        try {
            $response = $this->request('POST', '/crm/v3/objects/contacts', [
                'properties' => $properties,
            ]);

            $contactId = $response['id'] ?? null;

            if ($contactId) {
                Log::info('HubSpot contact created', [
                    'contact_id' => $contactId,
                    'email' => $properties['email'] ?? 'N/A',
                ]);
            }

            return $contactId;

        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot contact', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update a contact.
     *
     * PATCH /crm/v3/objects/contacts/{contactId}
     *
     * @param string $contactId Contact ID
     * @param array $properties Properties to update
     * @return bool True on success
     */
    public function updateContact(string $contactId, array $properties): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/objects/contacts/{$contactId}", [
                'properties' => $properties,
            ]);

            Log::info('HubSpot contact updated', [
                'contact_id' => $contactId,
                'properties_updated' => array_keys($properties),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot contact', [
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a company.
     *
     * POST /crm/v3/objects/companies
     *
     * @param array $properties Company properties (name, domain, industry, etc.)
     * @return string|null Company ID or null on failure
     */
    public function createCompany(array $properties): ?string
    {
        try {
            $response = $this->request('POST', '/crm/v3/objects/companies', [
                'properties' => $properties,
            ]);

            $companyId = $response['id'] ?? null;

            if ($companyId) {
                Log::info('HubSpot company created', [
                    'company_id' => $companyId,
                    'name' => $properties['name'] ?? 'N/A',
                ]);
            }

            return $companyId;

        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot company', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update a company.
     *
     * PATCH /crm/v3/objects/companies/{companyId}
     *
     * @param string $companyId Company ID
     * @param array $properties Properties to update
     * @return bool True on success
     */
    public function updateCompany(string $companyId, array $properties): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/objects/companies/{$companyId}", [
                'properties' => $properties,
            ]);

            Log::info('HubSpot company updated', [
                'company_id' => $companyId,
                'properties_updated' => array_keys($properties),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot company', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Create a deal.
     *
     * POST /crm/v3/objects/deals
     *
     * @param array $properties Deal properties (dealname, dealstage, pipeline, amount, etc.)
     * @return string|null Deal ID or null on failure
     */
    public function createDeal(array $properties): ?string
    {
        try {
            // Set default pipeline if not specified
            if (!isset($properties['pipeline'])) {
                $properties['pipeline'] = $this->pipelineId;
            }

            $response = $this->request('POST', '/crm/v3/objects/deals', [
                'properties' => $properties,
            ]);

            $dealId = $response['id'] ?? null;

            if ($dealId) {
                Log::info('HubSpot deal created', [
                    'deal_id' => $dealId,
                    'name' => $properties['dealname'] ?? 'N/A',
                ]);
            }

            return $dealId;

        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot deal', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract domain from email address.
     *
     * @param string $email Email address
     * @return string|null Domain or null if invalid
     */
    public function extractDomainFromEmail(string $email): ?string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return null;
        }

        $domain = strtolower($parts[1]);

        // Exclude common personal email domains
        $personalDomains = [
            'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'aol.com', 'icloud.com', 'mail.com', 'protonmail.com',
            'live.com', 'msn.com', 'yandex.com', 'zoho.com',
        ];

        if (in_array($domain, $personalDomains)) {
            return null;
        }

        return $domain;
    }

    /**
     * Enforce rate limiting (max 10 requests per second).
     *
     * @return void
     */
    protected function enforceRateLimit(): void
    {
        if ($this->lastRequestTime === null) {
            return;
        }

        $elapsed = (microtime(true) - $this->lastRequestTime) * 1000;

        if ($elapsed < $this->minRequestDelayMs) {
            $sleepTime = (int) (($this->minRequestDelayMs - $elapsed) * 1000);
            usleep($sleepTime);
        }
    }
}

// CLAUDE-CHECKPOINT
