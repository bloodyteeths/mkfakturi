<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Mk\Models\CustomReportTemplate;
use Modules\Mk\Services\CustomReportService;

class CustomReportController extends Controller
{
    protected CustomReportService $service;

    public function __construct(CustomReportService $service)
    {
        $this->service = $service;
    }

    /**
     * List saved report templates.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $filters = [
            'search' => $request->query('search'),
        ];

        $data = $this->service->list($companyId, array_filter($filters));

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Show a single template.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $template = CustomReportTemplate::forCompany($companyId)
            ->with('createdBy:id,name')
            ->find($id);

        if (! $template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $template->id,
                'company_id' => $template->company_id,
                'name' => $template->name,
                'account_filter' => $template->account_filter,
                'columns' => $template->columns,
                'period_type' => $template->period_type,
                'group_by' => $template->group_by,
                'comparison' => $template->comparison,
                'schedule_cron' => $template->schedule_cron,
                'schedule_emails' => $template->schedule_emails,
                'created_by' => $template->created_by,
                'created_by_user' => $template->createdBy ? [
                    'id' => $template->createdBy->id,
                    'name' => $template->createdBy->name,
                ] : null,
                'created_at' => $template->created_at?->toIso8601String(),
                'updated_at' => $template->updated_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create a new template.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'account_filter' => 'required|array',
            'account_filter.type' => 'required|string|in:range,category,specific,all',
            'columns' => 'required|array|min:1',
            'columns.*' => 'string|in:code,name,opening,debit,credit,closing,budget,variance,variance_pct',
            'period_type' => 'nullable|string|in:month,quarter,year,custom',
            'group_by' => 'nullable|string|in:month,quarter,cost_center',
            'comparison' => 'nullable|string|in:previous_year,budget',
            'schedule_cron' => 'nullable|string|max:50',
            'schedule_emails' => 'nullable|array',
            'schedule_emails.*' => 'email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $data = $validator->validated();
            $data['created_by'] = $request->user()?->id;

            $template = $this->service->create($companyId, $data);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'name' => $template->name,
                ],
                'message' => 'Template created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update a template.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $template = CustomReportTemplate::forCompany($companyId)->find($id);

        if (! $template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:150',
            'account_filter' => 'sometimes|required|array',
            'account_filter.type' => 'required_with:account_filter|string|in:range,category,specific,all',
            'columns' => 'sometimes|required|array|min:1',
            'columns.*' => 'string|in:code,name,opening,debit,credit,closing,budget,variance,variance_pct',
            'period_type' => 'nullable|string|in:month,quarter,year,custom',
            'group_by' => 'nullable|string|in:month,quarter,cost_center',
            'comparison' => 'nullable|string|in:previous_year,budget',
            'schedule_cron' => 'nullable|string|max:50',
            'schedule_emails' => 'nullable|array',
            'schedule_emails.*' => 'email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $template = $this->service->update($id, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $template->id,
                    'name' => $template->name,
                ],
                'message' => 'Template updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Delete a template.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $template = CustomReportTemplate::forCompany($companyId)->find($id);

        if (! $template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully.',
        ]);
    }

    /**
     * Preview a report from ad-hoc config (without saving).
     */
    public function preview(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $validator = Validator::make($request->all(), [
            'account_filter' => 'required|array',
            'account_filter.type' => 'required|string|in:range,category,specific,all',
            'columns' => 'required|array|min:1',
            'period_type' => 'nullable|string|in:month,quarter,year,custom',
            'group_by' => 'nullable|string|in:month,quarter,cost_center',
            'comparison' => 'nullable|string|in:previous_year,budget',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        try {
            $data = $this->service->preview($companyId, $validator->validated());

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Execute a saved template.
     */
    public function execute(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $template = CustomReportTemplate::forCompany($companyId)->find($id);

        if (! $template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        try {
            $overrides = [
                'date_from' => $request->query('date_from'),
                'date_to' => $request->query('date_to'),
            ];

            $data = $this->service->execute($id, array_filter($overrides));

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export PDF for a template.
     */
    public function exportPdf(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        if (! $companyId) {
            return response()->json(['error' => 'Company header required'], 400);
        }

        $template = CustomReportTemplate::forCompany($companyId)->find($id);

        if (! $template) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        try {
            $data = $this->service->exportPdf($id);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

// CLAUDE-CHECKPOINT
