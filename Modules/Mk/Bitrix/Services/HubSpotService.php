<?php

namespace Modules\Mk\Bitrix\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HubSpotService
 *
 * Comprehensive HTTP client for HubSpot CRM API interactions.
 * Handles pipelines, properties, contacts, companies, deals, associations, and engagements.
 *
 * Features:
 * - Retry/backoff for rate limits (429 errors)
 * - Logging of all API calls
 * - Configurable via env variables
 *
 * @see https://developers.hubspot.com/docs/api/overview
 */
class HubSpotService
{
    /**
     * Base URL for HubSpot API.
     */
    protected string $baseUrl = 'https://api.hubapi.com';

    /**
     * Access token for API authentication.
     */
    protected ?string $accessToken;

    /**
     * Pipeline ID for deals.
     */
    protected string $pipelineId;

    /**
     * Maximum number of retries for rate-limited requests.
     */
    protected int $maxRetries = 3;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Support both HUBSPOT_PRIVATE_APP_TOKEN and HUBSPOT_ACCESS_TOKEN
        $this->accessToken = config('hubspot.access_token')
            ?: env('HUBSPOT_PRIVATE_APP_TOKEN')
            ?: env('HUBSPOT_ACCESS_TOKEN')
            ?: config('bitrix.hubspot.access_token');
        $this->pipelineId = config('hubspot.pipeline_id')
            ?: config('hubspot.pipeline')
            ?: env('HUBSPOT_PIPELINE_ID', 'default');
    }

    /**
     * Check if HubSpot is configured.
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken);
    }

    /**
     * Make an API request to HubSpot with retry/backoff for rate limits.
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @param int $attempt Current attempt number (for retries)
     * @return array|null
     * @throws \Exception
     */
    protected function request(string $method, string $endpoint, array $data = [], int $attempt = 1): ?array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('HubSpot is not configured. Set HUBSPOT_PRIVATE_APP_TOKEN or HUBSPOT_ACCESS_TOKEN in .env');
        }

        $url = $this->baseUrl . $endpoint;

        Log::info('HubSpot API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'attempt' => $attempt,
        ]);

        $response = Http::withToken($this->accessToken)
            ->accept('application/json')
            ->timeout(30)
            ->{strtolower($method)}($url, $data);

        // Handle rate limiting (429) with exponential backoff
        if ($response->status() === 429) {
            if ($attempt < $this->maxRetries) {
                $backoffSeconds = pow(2, $attempt); // 2, 4, 8 seconds
                Log::warning('HubSpot rate limit hit, backing off', [
                    'endpoint' => $endpoint,
                    'attempt' => $attempt,
                    'backoff_seconds' => $backoffSeconds,
                ]);
                sleep($backoffSeconds);
                return $this->request($method, $endpoint, $data, $attempt + 1);
            }

            Log::error('HubSpot rate limit exceeded after max retries', [
                'endpoint' => $endpoint,
                'max_retries' => $this->maxRetries,
            ]);
            throw new \Exception('HubSpot API rate limit exceeded after max retries');
        }

        if ($response->failed()) {
            $error = $response->json();
            $message = $error['message'] ?? $response->body();
            $category = $error['category'] ?? 'UNKNOWN';

            Log::error('HubSpot API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'category' => $category,
                'error' => $message,
            ]);

            throw new \Exception("HubSpot API error ({$category}): {$message}");
        }

        Log::info('HubSpot API response', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
        ]);

        return $response->json();
    }

    /**
     * Test the API connection.
     *
     * @return array Account info
     * @throws \Exception
     */
    public function testConnection(): array
    {
        return $this->request('GET', '/account-info/v3/details');
    }

    // =========================================================================
    // PIPELINES
    // =========================================================================

    /**
     * Create a pipeline.
     *
     * @param string $objectType Object type (e.g., 'deals')
     * @param string $label Pipeline label/name
     * @param array $stages Array of stage definitions [{label, displayOrder, metadata: {probability}}]
     * @return string|null Pipeline ID or null on failure
     */
    public function createPipeline(string $objectType, string $label, array $stages): ?string
    {
        try {
            $response = $this->request('POST', "/crm/v3/pipelines/{$objectType}", [
                'label' => $label,
                'displayOrder' => 1,
                'stages' => $stages,
            ]);

            $pipelineId = $response['id'] ?? null;

            if ($pipelineId) {
                Log::info('HubSpot pipeline created', [
                    'pipeline_id' => $pipelineId,
                    'label' => $label,
                    'object_type' => $objectType,
                ]);
            }

            return $pipelineId;
        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot pipeline', [
                'label' => $label,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get all pipelines for an object type.
     *
     * @param string $objectType Object type (default: 'deals')
     * @return array Array of pipelines
     */
    public function getPipelines(string $objectType = 'deals'): array
    {
        try {
            $response = $this->request('GET', "/crm/v3/pipelines/{$objectType}");
            return $response['results'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot pipelines', [
                'object_type' => $objectType,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Find a pipeline by label.
     *
     * @param string $label Pipeline label/name
     * @param string $objectType Object type (default: 'deals')
     * @return array|null Pipeline data or null if not found
     */
    public function getPipelineByLabel(string $label, string $objectType = 'deals'): ?array
    {
        $pipelines = $this->getPipelines($objectType);

        foreach ($pipelines as $pipeline) {
            if (strcasecmp($pipeline['label'] ?? '', $label) === 0) {
                return $pipeline;
            }
        }

        return null;
    }

    /**
     * Get stages for a pipeline.
     *
     * @param string $pipelineId Pipeline ID
     * @param string $objectType Object type (default: 'deals')
     * @return array Array of stages
     */
    public function getPipelineStages(string $pipelineId, string $objectType = 'deals'): array
    {
        try {
            $response = $this->request('GET', "/crm/v3/pipelines/{$objectType}/{$pipelineId}");
            return $response['stages'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get pipeline stages', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Delete a pipeline.
     *
     * @param string $pipelineId Pipeline ID
     * @param string $objectType Object type (default: 'deals')
     * @return bool
     */
    public function deletePipeline(string $pipelineId, string $objectType = 'deals'): bool
    {
        try {
            $this->request('DELETE', "/crm/v3/pipelines/{$objectType}/{$pipelineId}");

            Log::info('HubSpot pipeline deleted', [
                'pipeline_id' => $pipelineId,
                'object_type' => $objectType,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete HubSpot pipeline', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update a pipeline (rename, update stages).
     *
     * @param string $pipelineId Pipeline ID
     * @param array $data Pipeline data (label, stages, etc.)
     * @param string $objectType Object type (default: 'deals')
     * @return bool True on success
     */
    public function updatePipeline(string $pipelineId, array $data, string $objectType = 'deals'): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/pipelines/{$objectType}/{$pipelineId}", $data);

            Log::info('HubSpot pipeline updated', [
                'pipeline_id' => $pipelineId,
                'object_type' => $objectType,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot pipeline', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create a stage in an existing pipeline.
     *
     * @param string $pipelineId Pipeline ID
     * @param array $stageData Stage data (label, displayOrder, metadata)
     * @param string $objectType Object type (default: 'deals')
     * @return string|null Stage ID or null on failure
     */
    public function createPipelineStage(string $pipelineId, array $stageData, string $objectType = 'deals'): ?string
    {
        try {
            $response = $this->request(
                'POST',
                "/crm/v3/pipelines/{$objectType}/{$pipelineId}/stages",
                $stageData
            );

            $stageId = $response['id'] ?? null;

            if ($stageId) {
                Log::info('HubSpot pipeline stage created', [
                    'pipeline_id' => $pipelineId,
                    'stage_id' => $stageId,
                    'label' => $stageData['label'] ?? 'N/A',
                ]);
            }

            return $stageId;
        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot pipeline stage', [
                'pipeline_id' => $pipelineId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Update a stage in a pipeline.
     *
     * @param string $pipelineId Pipeline ID
     * @param string $stageId Stage ID
     * @param array $stageData Stage data to update
     * @param string $objectType Object type (default: 'deals')
     * @return bool True on success
     */
    public function updatePipelineStage(string $pipelineId, string $stageId, array $stageData, string $objectType = 'deals'): bool
    {
        try {
            $this->request(
                'PATCH',
                "/crm/v3/pipelines/{$objectType}/{$pipelineId}/stages/{$stageId}",
                $stageData
            );

            Log::info('HubSpot pipeline stage updated', [
                'pipeline_id' => $pipelineId,
                'stage_id' => $stageId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot pipeline stage', [
                'pipeline_id' => $pipelineId,
                'stage_id' => $stageId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get the first (default) pipeline.
     *
     * @param string $objectType Object type (default: 'deals')
     * @return array|null Pipeline data or null if none exists
     */
    public function getDefaultPipeline(string $objectType = 'deals'): ?array
    {
        $pipelines = $this->getPipelines($objectType);
        return $pipelines[0] ?? null;
    }

    // =========================================================================
    // CUSTOM PROPERTIES (CRM Schemas)
    // =========================================================================

    /**
     * Create a custom property.
     *
     * @param string $objectType Object type (contacts, companies, deals)
     * @param string $name Property internal name
     * @param string $label Property display label
     * @param string $type Property type (string, number, date, etc.)
     * @param string $groupName Property group (default: 'dealinformation')
     * @return bool True on success
     */
    public function createProperty(
        string $objectType,
        string $name,
        string $label,
        string $type = 'string',
        string $groupName = 'dealinformation'
    ): bool {
        try {
            $payload = [
                'name' => $name,
                'label' => $label,
                'type' => $type,
                'fieldType' => $this->getFieldType($type),
                'groupName' => $groupName,
            ];

            // Boolean properties need explicit options
            if ($type === 'bool' || $type === 'boolean') {
                $payload['type'] = 'enumeration';
                $payload['fieldType'] = 'booleancheckbox';
                $payload['options'] = [
                    ['label' => 'Yes', 'value' => 'true', 'displayOrder' => 0],
                    ['label' => 'No', 'value' => 'false', 'displayOrder' => 1],
                ];
            }

            $this->request('POST', "/crm/v3/properties/{$objectType}", $payload);

            Log::info('HubSpot property created', [
                'object_type' => $objectType,
                'name' => $name,
                'type' => $type,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot property', [
                'object_type' => $objectType,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a property exists.
     *
     * @param string $objectType Object type (contacts, companies, deals)
     * @param string $name Property name
     * @return bool
     */
    public function propertyExists(string $objectType, string $name): bool
    {
        try {
            $this->request('GET', "/crm/v3/properties/{$objectType}/{$name}");
            return true;
        } catch (\Exception $e) {
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
     * Get field type from property type.
     *
     * @param string $type
     * @return string
     */
    protected function getFieldType(string $type): string
    {
        return match ($type) {
            'number' => 'number',
            'date' => 'date',
            'datetime' => 'date',
            'bool', 'boolean' => 'booleancheckbox',
            default => 'text',
        };
    }

    // =========================================================================
    // COMPANIES
    // =========================================================================

    /**
     * Create a company.
     *
     * @param array $properties Company properties
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
     * @param string $id Company ID
     * @param array $properties Properties to update
     * @return bool True on success
     */
    public function updateCompany(string $id, array $properties): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/objects/companies/{$id}", [
                'properties' => $properties,
            ]);

            Log::info('HubSpot company updated', [
                'company_id' => $id,
                'properties_updated' => array_keys($properties),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot company', [
                'company_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
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

            return $response['results'][0] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to find company by domain', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find a company by name.
     *
     * @param string $name Company name
     * @return array|null Company data or null if not found
     */
    public function findCompanyByName(string $name): ?array
    {
        try {
            $response = $this->request('POST', '/crm/v3/objects/companies/search', [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'propertyName' => 'name',
                                'operator' => 'EQ',
                                'value' => $name,
                            ],
                        ],
                    ],
                ],
            ]);

            return $response['results'][0] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to find company by name', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create or update a company (find by domain or name).
     *
     * @param array $properties Company properties (should include 'domain' or 'name')
     * @return string|null Company ID or null on failure
     */
    public function upsertCompany(array $properties): ?string
    {
        // Try to find existing by domain first
        $existing = null;
        if (!empty($properties['domain'])) {
            $existing = $this->findCompanyByDomain($properties['domain']);
        }

        // Fall back to name search
        if (!$existing && !empty($properties['name'])) {
            $existing = $this->findCompanyByName($properties['name']);
        }

        if ($existing) {
            $companyId = $existing['id'];
            if ($this->updateCompany($companyId, $properties)) {
                return $companyId;
            }
            return null;
        }

        return $this->createCompany($properties);
    }

    // =========================================================================
    // CONTACTS
    // =========================================================================

    /**
     * Create a contact.
     *
     * @param array $properties Contact properties
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
     * @param string $id Contact ID
     * @param array $properties Properties to update
     * @return bool True on success
     */
    public function updateContact(string $id, array $properties): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/objects/contacts/{$id}", [
                'properties' => $properties,
            ]);

            Log::info('HubSpot contact updated', [
                'contact_id' => $id,
                'properties_updated' => array_keys($properties),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot contact', [
                'contact_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
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

            return $response['results'][0] ?? null;
        } catch (\Exception $e) {
            Log::error('Failed to find contact by email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create or update a contact by email.
     *
     * @param string $email Contact email
     * @param array $properties Additional properties
     * @return string|null Contact ID or null on failure
     */
    public function upsertContact(string $email, array $properties): ?string
    {
        $properties['email'] = $email;

        $existing = $this->findContactByEmail($email);

        if ($existing) {
            $contactId = $existing['id'];
            if ($this->updateContact($contactId, $properties)) {
                return $contactId;
            }
            return null;
        }

        return $this->createContact($properties);
    }

    // =========================================================================
    // DEALS
    // =========================================================================

    /**
     * Create a deal.
     *
     * @param array $properties Deal properties
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
     * Update a deal.
     *
     * @param string $id Deal ID
     * @param array $properties Properties to update
     * @return bool True on success
     */
    public function updateDeal(string $id, array $properties): bool
    {
        try {
            $this->request('PATCH', "/crm/v3/objects/deals/{$id}", [
                'properties' => $properties,
            ]);

            Log::info('HubSpot deal updated', [
                'deal_id' => $id,
                'properties_updated' => array_keys($properties),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update HubSpot deal', [
                'deal_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get a deal by ID.
     *
     * @param string $id Deal ID
     * @param array $properties Properties to fetch (optional)
     * @return array|null Deal data or null if not found
     */
    public function getDeal(string $id, array $properties = []): ?array
    {
        try {
            $endpoint = "/crm/v3/objects/deals/{$id}";
            if (!empty($properties)) {
                $endpoint .= '?properties=' . implode(',', $properties);
            }

            return $this->request('GET', $endpoint);
        } catch (\Exception $e) {
            Log::error('Failed to get HubSpot deal', [
                'deal_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find deals by company ID.
     *
     * @param string $companyId Company ID
     * @param string $pipelineId Pipeline ID to filter by
     * @return array Array of deals
     */
    public function findDealsByCompany(string $companyId, string $pipelineId): array
    {
        try {
            // First get associated deal IDs
            $response = $this->request('GET', "/crm/v3/objects/companies/{$companyId}/associations/deals");
            $associations = $response['results'] ?? [];

            if (empty($associations)) {
                return [];
            }

            // Get deal details with pipeline filter
            $dealIds = array_column($associations, 'id');
            $deals = [];

            foreach ($dealIds as $dealId) {
                $deal = $this->getDeal($dealId, ['dealname', 'dealstage', 'pipeline']);
                if ($deal && ($deal['properties']['pipeline'] ?? '') === $pipelineId) {
                    $deals[] = $deal;
                }
            }

            return $deals;
        } catch (\Exception $e) {
            Log::error('Failed to find deals by company', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get deals by stage.
     *
     * @param string $pipelineId Pipeline ID
     * @param string $stageId Stage ID
     * @return array Array of deals
     */
    public function getDealsByStage(string $pipelineId, string $stageId): array
    {
        try {
            $response = $this->request('POST', '/crm/v3/objects/deals/search', [
                'filterGroups' => [
                    [
                        'filters' => [
                            [
                                'propertyName' => 'pipeline',
                                'operator' => 'EQ',
                                'value' => $pipelineId,
                            ],
                            [
                                'propertyName' => 'dealstage',
                                'operator' => 'EQ',
                                'value' => $stageId,
                            ],
                        ],
                    ],
                ],
                'properties' => ['dealname', 'dealstage', 'pipeline', 'amount'],
                'limit' => 100,
            ]);

            return $response['results'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get deals by stage', [
                'pipeline_id' => $pipelineId,
                'stage_id' => $stageId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Update deal stage.
     *
     * @param string $dealId Deal ID
     * @param string $stageId Stage ID
     * @return bool True on success
     */
    public function updateDealStage(string $dealId, string $stageId): bool
    {
        return $this->updateDeal($dealId, ['dealstage' => $stageId]);
    }

    // =========================================================================
    // ASSOCIATIONS
    // =========================================================================

    /**
     * Associate a contact to a company.
     *
     * @param string $contactId Contact ID
     * @param string $companyId Company ID
     * @return bool True on success
     */
    public function associateContactToCompany(string $contactId, string $companyId): bool
    {
        try {
            $this->request(
                'PUT',
                "/crm/v3/objects/contacts/{$contactId}/associations/companies/{$companyId}/contact_to_company",
                []
            );

            Log::info('HubSpot contact associated to company', [
                'contact_id' => $contactId,
                'company_id' => $companyId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to associate contact to company', [
                'contact_id' => $contactId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Associate a deal to a contact.
     *
     * @param string $dealId Deal ID
     * @param string $contactId Contact ID
     * @return bool True on success
     */
    public function associateDealToContact(string $dealId, string $contactId): bool
    {
        try {
            $this->request(
                'PUT',
                "/crm/v3/objects/deals/{$dealId}/associations/contacts/{$contactId}/deal_to_contact",
                []
            );

            Log::info('HubSpot deal associated to contact', [
                'deal_id' => $dealId,
                'contact_id' => $contactId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to associate deal to contact', [
                'deal_id' => $dealId,
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Associate a deal to a company.
     *
     * @param string $dealId Deal ID
     * @param string $companyId Company ID
     * @return bool True on success
     */
    public function associateDealToCompany(string $dealId, string $companyId): bool
    {
        try {
            $this->request(
                'PUT',
                "/crm/v3/objects/deals/{$dealId}/associations/companies/{$companyId}/deal_to_company",
                []
            );

            Log::info('HubSpot deal associated to company', [
                'deal_id' => $dealId,
                'company_id' => $companyId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to associate deal to company', [
                'deal_id' => $dealId,
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // =========================================================================
    // TIMELINE / NOTES / ENGAGEMENTS
    // =========================================================================

    /**
     * Create a note on an object.
     *
     * @param string $objectType Object type (contacts, companies, deals)
     * @param string $objectId Object ID
     * @param string $body Note body/content
     * @return string|null Note ID or null on failure
     */
    public function createNote(string $objectType, string $objectId, string $body): ?string
    {
        try {
            // Map object types to association type IDs
            $associationTypeIds = [
                'contacts' => 202, // note_to_contact
                'companies' => 190, // note_to_company
                'deals' => 214, // note_to_deal
            ];

            $associationTypeId = $associationTypeIds[$objectType] ?? 202;

            $response = $this->request('POST', '/crm/v3/objects/notes', [
                'properties' => [
                    'hs_timestamp' => now()->getTimestampMs(),
                    'hs_note_body' => $body,
                ],
                'associations' => [
                    [
                        'to' => ['id' => $objectId],
                        'types' => [
                            [
                                'associationCategory' => 'HUBSPOT_DEFINED',
                                'associationTypeId' => $associationTypeId,
                            ],
                        ],
                    ],
                ],
            ]);

            $noteId = $response['id'] ?? null;

            if ($noteId) {
                Log::info('HubSpot note created', [
                    'note_id' => $noteId,
                    'object_type' => $objectType,
                    'object_id' => $objectId,
                ]);
            }

            return $noteId;
        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot note', [
                'object_type' => $objectType,
                'object_id' => $objectId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create an engagement (email, call, meeting, task).
     *
     * @param string $type Engagement type (EMAIL, CALL, MEETING, TASK)
     * @param string $contactId Contact ID to associate
     * @param array $metadata Engagement metadata
     * @return string|null Engagement ID or null on failure
     */
    public function createEngagement(string $type, string $contactId, array $metadata): ?string
    {
        try {
            $objectType = strtolower($type) . 's'; // emails, calls, meetings, tasks
            $timestamp = $metadata['timestamp'] ?? now()->getTimestampMs();

            // Build properties based on type
            $properties = ['hs_timestamp' => $timestamp];

            switch (strtoupper($type)) {
                case 'EMAIL':
                    $properties['hs_email_direction'] = 'EMAIL';
                    $properties['hs_email_status'] = $metadata['status'] ?? 'SENT';
                    $properties['hs_email_subject'] = $metadata['subject'] ?? '';
                    $properties['hs_email_text'] = $metadata['body'] ?? '';
                    $associationTypeId = 198; // email_to_contact
                    break;

                case 'CALL':
                    $properties['hs_call_title'] = $metadata['title'] ?? 'Call';
                    $properties['hs_call_body'] = $metadata['body'] ?? '';
                    $properties['hs_call_status'] = $metadata['status'] ?? 'COMPLETED';
                    $properties['hs_call_duration'] = $metadata['duration'] ?? 0;
                    $associationTypeId = 194; // call_to_contact
                    break;

                case 'MEETING':
                    $properties['hs_meeting_title'] = $metadata['title'] ?? 'Meeting';
                    $properties['hs_meeting_body'] = $metadata['body'] ?? '';
                    $properties['hs_meeting_outcome'] = $metadata['outcome'] ?? 'COMPLETED';
                    $associationTypeId = 200; // meeting_to_contact
                    break;

                case 'TASK':
                    $properties['hs_task_subject'] = $metadata['subject'] ?? 'Task';
                    $properties['hs_task_body'] = $metadata['body'] ?? '';
                    $properties['hs_task_status'] = $metadata['status'] ?? 'NOT_STARTED';
                    $properties['hs_task_priority'] = $metadata['priority'] ?? 'MEDIUM';
                    $associationTypeId = 204; // task_to_contact
                    break;

                default:
                    throw new \Exception("Unknown engagement type: {$type}");
            }

            $response = $this->request('POST', "/crm/v3/objects/{$objectType}", [
                'properties' => $properties,
                'associations' => [
                    [
                        'to' => ['id' => $contactId],
                        'types' => [
                            [
                                'associationCategory' => 'HUBSPOT_DEFINED',
                                'associationTypeId' => $associationTypeId,
                            ],
                        ],
                    ],
                ],
            ]);

            $engagementId = $response['id'] ?? null;

            if ($engagementId) {
                Log::info('HubSpot engagement created', [
                    'engagement_id' => $engagementId,
                    'type' => $type,
                    'contact_id' => $contactId,
                ]);
            }

            return $engagementId;
        } catch (\Exception $e) {
            Log::error('Failed to create HubSpot engagement', [
                'type' => $type,
                'contact_id' => $contactId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // =========================================================================
    // UTILITIES
    // =========================================================================

    /**
     * Get the configured pipeline ID.
     *
     * @return string
     */
    public function getPipelineId(): string
    {
        return $this->pipelineId;
    }

    /**
     * Extract domain from URL or website.
     *
     * @param string $website
     * @return string|null
     */
    public function extractDomain(string $website): ?string
    {
        $website = trim($website);

        if (empty($website)) {
            return null;
        }

        // Add protocol if missing
        if (!preg_match('~^https?://~i', $website)) {
            $website = 'https://' . $website;
        }

        $parsed = parse_url($website);
        $host = $parsed['host'] ?? null;

        if (!$host) {
            return null;
        }

        // Remove www prefix
        return preg_replace('/^www\./i', '', $host);
    }

    /**
     * Get stage ID by label from the configured pipeline.
     *
     * @param string $stageLabel Stage label
     * @return string|null Stage ID or null if not found
     */
    public function getStageIdByLabel(string $stageLabel): ?string
    {
        $stages = $this->getPipelineStages($this->pipelineId);

        foreach ($stages as $stage) {
            if (strcasecmp($stage['label'] ?? '', $stageLabel) === 0) {
                return $stage['id'];
            }
        }

        return null;
    }
}

// CLAUDE-CHECKPOINT
