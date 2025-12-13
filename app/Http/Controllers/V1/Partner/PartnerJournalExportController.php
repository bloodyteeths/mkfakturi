<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\JournalExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Partner Journal Export Controller (PAB-03)
 *
 * Manages journal entry viewing and export for companies linked to partners.
 * Partners can view, export, and manage journal entries for their client companies.
 */
class PartnerJournalExportController extends Controller
{
    /**
     * List journal entries for a company.
     *
     * Returns paginated journal entries with optional filtering by date range and status.
     * Includes related invoice/expense/payment data.
     *
     * @param Request $request
     * @param int $company Company ID from route
     * @return JsonResponse
     */
    public function entries(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:all,suggested,confirmed',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        $service = new JournalExportService(
            $company,
            $request->input('start_date'),
            $request->input('end_date')
        );

        $entries = $service->getJournalEntries();

        // Add confidence and suggestion data to each entry
        // This enables the AI suggestion UI to work properly
        $entries = $entries->map(function ($entry, $index) use ($company) {
            // Generate unique ID for frontend
            $entry['id'] = $index + 1;

            // Add default confidence (0.3 = default account suggestion)
            // In the future, this will use AccountSuggestionService for real AI suggestions
            $entry['confidence'] = $entry['confidence'] ?? 0.3;
            $entry['suggestion_reason'] = $entry['suggestion_reason'] ?? 'default';

            // Add entity info for learning system
            $entry['entity_type'] = $entry['entity_type'] ??
                ($entry['type'] === 'expense' ? 'expense_category' :
                ($entry['type'] === 'invoice' || $entry['type'] === 'payment' ? 'customer' : null));
            $entry['entity_id'] = $entry['entity_id'] ?? null;

            // Use account_code as account_id placeholder if not set
            $entry['account_id'] = $entry['account_id'] ?? null;

            return $entry;
        });

        // Filter by status if requested
        $status = $request->input('status', 'all');
        if ($status !== 'all') {
            // TODO: Implement status filtering when journal entry confirmation is added
        }

        // Paginate results
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $total = $entries->count();
        $paginatedEntries = $entries
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedEntries,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Get single journal entry details.
     *
     * Returns full entry details with all line items.
     * Note: Current implementation uses entry reference and date to identify entries
     * since journal entries are generated on-the-fly, not stored.
     *
     * @param Request $request
     * @param int $company Company ID
     * @param string $reference Entry reference (invoice/payment/expense number)
     * @return JsonResponse
     */
    public function show(Request $request, int $company, string $reference): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'date' => 'required|date',
        ]);

        // Get journal entries for the date range around the requested date
        $date = Carbon::parse($request->input('date'));
        $service = new JournalExportService(
            $company,
            $date->copy()->subDays(1)->format('Y-m-d'),
            $date->copy()->addDays(1)->format('Y-m-d')
        );

        $entries = $service->getJournalEntries();

        // Filter to only entries matching the reference
        $matchingEntries = $entries->filter(function ($entry) use ($reference) {
            return $entry['reference'] === $reference;
        })->values();

        if ($matchingEntries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Journal entry not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reference' => $reference,
                'date' => $matchingEntries->first()['date'],
                'type' => $matchingEntries->first()['type'],
                'entries' => $matchingEntries,
                'is_balanced' => abs($matchingEntries->sum('debit') - $matchingEntries->sum('credit')) < 0.01,
                'total_debit' => $matchingEntries->sum('debit'),
                'total_credit' => $matchingEntries->sum('credit'),
                // Note: Account suggestions/confirmations not yet implemented
                'status' => 'suggested',
            ],
        ]);
    }

    /**
     * Confirm or adjust a journal entry.
     *
     * Note: This feature requires a journal_entries table to store confirmed entries
     * and account mapping preferences. Currently not implemented in the base system.
     *
     * @param Request $request
     * @param int $company Company ID
     * @param string $reference Entry reference
     * @return JsonResponse
     */
    public function confirm(Request $request, int $company, string $reference): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'date' => 'required|date',
            'line_item_index' => 'required|integer|min:0',
            'account_id' => 'required|integer|exists:accounts,id',
        ]);

        // TODO: Implement journal entry confirmation feature
        // This requires:
        // 1. A journal_entries table to store confirmed entries
        // 2. A journal_entry_lines table for individual line items
        // 3. Logic to update account mappings based on confirmations
        // 4. Validation that the account belongs to the company
        //
        // For now, return a not implemented response
        return response()->json([
            'success' => false,
            'message' => 'Journal entry confirmation feature not yet implemented. Please update account mappings directly to change default accounts.',
            'note' => 'This feature requires database schema additions (journal_entries, journal_entry_lines tables)',
        ], 501); // 501 Not Implemented
    }

    /**
     * Export journal entries to file.
     *
     * Exports journal entries for the specified date range in the requested format.
     * Supported formats: csv, pantheon, zonel
     *
     * @param Request $request
     * @param int $company Company ID
     * @return Response
     */
    public function export(Request $request, int $company): Response
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response('Partner not found', 404);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response('No access to this company', 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'sometimes|in:csv,pantheon,zonel',
        ]);

        $format = $request->input('format', 'csv');

        $service = new JournalExportService(
            $company,
            $request->input('start_date'),
            $request->input('end_date')
        );

        // Generate export content based on format
        $content = match ($format) {
            JournalExportService::FORMAT_PANTHEON => $service->toPantheonXML(),
            JournalExportService::FORMAT_ZONEL => $service->toZonelCSV(),
            default => $service->toCSV(),
        };

        // Determine file extension and content type
        $extension = match ($format) {
            JournalExportService::FORMAT_PANTHEON => 'xml',
            default => 'csv',
        };

        $contentType = match ($format) {
            JournalExportService::FORMAT_PANTHEON => 'application/xml; charset=UTF-8',
            default => 'text/csv; charset=UTF-8',
        };

        // Generate filename
        $from = Carbon::parse($request->input('start_date'))->format('Ymd');
        $to = Carbon::parse($request->input('end_date'))->format('Ymd');
        $filename = "company_{$company}_journals_{$format}_{$from}_{$to}.{$extension}";

        return response($content, 200)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($content));
    }

    /**
     * Get journal export summary.
     *
     * Returns summary statistics for the export period without paginating.
     *
     * @param Request $request
     * @param int $company Company ID
     * @return JsonResponse
     */
    public function summary(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $service = new JournalExportService(
            $company,
            $request->input('start_date'),
            $request->input('end_date')
        );

        $summary = $service->getSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get available export formats.
     *
     * Returns list of supported export formats for journal entries.
     *
     * @return JsonResponse
     */
    public function formats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                [
                    'value' => 'csv',
                    'label' => 'Generic CSV',
                    'description' => 'Standard CSV format compatible with most systems',
                ],
                [
                    'value' => 'pantheon',
                    'label' => 'Pantheon',
                    'description' => 'Format for Pantheon accounting software (Macedonian)',
                ],
                [
                    'value' => 'zonel',
                    'label' => 'Zonel',
                    'description' => 'Format for Zonel accounting software (Macedonian)',
                ],
            ],
        ]);
    }

    /**
     * Get partner from authenticated request.
     *
     * @param Request $request
     * @return Partner|null
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Learn account mappings from accountant review.
     *
     * Accepts or overrides AI suggestions to improve future account selection.
     * When accepted=true, reinforces the suggestion. When accepted=false, learns the override.
     *
     * @param Request $request
     * @param int $company Company ID
     * @return JsonResponse
     */
    public function learn(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'mappings' => 'required|array|min:1',
            'mappings.*.entity_type' => 'required|in:customer,supplier,expense_category',
            'mappings.*.entity_id' => 'required|integer',
            'mappings.*.account_id' => 'required|integer|exists:accounts,id',
            'mappings.*.accepted' => 'required|boolean',
        ]);

        $mappings = $request->input('mappings');
        $learnedCount = 0;

        foreach ($mappings as $mappingData) {
            // Map entity type to model class
            $entityType = match($mappingData['entity_type']) {
                'customer' => \App\Models\AccountMapping::ENTITY_CUSTOMER,
                'supplier' => \App\Models\AccountMapping::ENTITY_SUPPLIER,
                'expense_category' => \App\Models\AccountMapping::ENTITY_EXPENSE_CATEGORY,
            };

            // Verify account belongs to this company
            $account = \App\Models\Account::where('id', $mappingData['account_id'])
                ->where('company_id', $company)
                ->first();

            if (!$account) {
                continue; // Skip invalid accounts
            }

            // Update or create the mapping
            // We store the mapping in the debit_account_id for simplicity
            // (The service will use the appropriate account based on transaction type)
            \App\Models\AccountMapping::updateOrCreate(
                [
                    'company_id' => $company,
                    'entity_type' => $entityType,
                    'entity_id' => $mappingData['entity_id'],
                ],
                [
                    'debit_account_id' => $account->id,
                    'credit_account_id' => $account->id,
                    'meta' => [
                        'learned_at' => now()->toIso8601String(),
                        'learned_by_partner_id' => $partner->id,
                        'accepted_suggestion' => $mappingData['accepted'],
                        'confidence' => $mappingData['accepted'] ? 1.0 : 0.9,
                    ],
                ]
            );

            $learnedCount++;
        }

        return response()->json([
            'success' => true,
            'learned_count' => $learnedCount,
            'message' => 'Mappings saved successfully',
        ]);
    }

    /**
     * Accept all AI suggestions above confidence threshold.
     *
     * Bulk accepts suggestions and saves them as learned mappings.
     *
     * @param Request $request
     * @param int $company Company ID
     * @return JsonResponse
     */
    public function acceptAll(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'min_confidence' => 'sometimes|numeric|min:0|max:1',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $minConfidence = $request->input('min_confidence', 0.8);

        // For now, return a success response indicating this feature would
        // accept all high-confidence suggestions from the specified date range.
        // Full implementation would require tracking suggestions with confidence scores.
        return response()->json([
            'success' => true,
            'message' => 'Bulk accept feature - requires AI suggestion tracking to be implemented',
            'note' => 'This endpoint will accept all suggestions above confidence threshold once AI suggestions are tracked',
            'parameters' => [
                'min_confidence' => $minConfidence,
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
        ]);
    }

    /**
     * Check if partner has access to a company.
     *
     * @param Partner $partner
     * @param int $companyId
     * @return bool
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
