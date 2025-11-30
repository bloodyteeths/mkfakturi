<?php

namespace App\Http\Controllers\V1\Admin\Project;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteProjectsRequest;
use App\Http\Requests\ProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;

/**
 * Projects Controller
 *
 * Handles CRUD operations for projects.
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 */
class ProjectsController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index(Request $request): JsonResponse|AnonymousResourceCollection
    {
        try {
            $this->authorize('viewAny', Project::class);

            $limit = $request->input('limit', 10);

            $projects = Project::with([
                'customer',
                'currency',
                'company',
                'creator',
            ])
                ->withCount(['invoices', 'expenses', 'payments'])
                ->whereCompany()
                ->applyFilters($request->all())
                ->paginateData($limit);

            return ProjectResource::collection($projects)
                ->additional([
                    'meta' => [
                        'project_total_count' => Project::whereCompany()->count(),
                        'open_count' => Project::whereCompany()->open()->count(),
                        'in_progress_count' => Project::whereCompany()->inProgress()->count(),
                        'completed_count' => Project::whereCompany()->completed()->count(),
                        'on_hold_count' => Project::whereCompany()->onHold()->count(),
                        'cancelled_count' => Project::whereCompany()->cancelled()->count(),
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error('Project index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'company_id' => $request->header('company'),
            ]);
            throw $e;
        }
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Project::class);

            Log::info('Creating project', ['data' => $request->validated()]);

            $project = Project::createProject($request);
            $project->load(['customer', 'currency', 'company', 'creator']);

            Log::info('Project created successfully', ['id' => $project->id]);

            return (new ProjectResource($project))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            Log::error('Project store error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $request->all(),
                'company_id' => $request->header('company'),
            ]);
            throw $e;
        }
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load(['customer', 'currency', 'company', 'creator']);
        $project->loadCount(['invoices', 'expenses', 'payments']);

        return (new ProjectResource($project))
            ->response();
    }

    /**
     * Update the specified project.
     */
    public function update(ProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project = $project->updateProject($request);
        $project->load(['customer', 'currency', 'company', 'creator']);
        $project->loadCount(['invoices', 'expenses', 'payments']);

        return (new ProjectResource($project))
            ->response();
    }

    /**
     * Remove the specified projects.
     */
    public function delete(DeleteProjectsRequest $request): JsonResponse
    {
        $this->authorize('deleteMultiple', Project::class);

        Project::deleteProjects($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Get project summary with all financial totals.
     *
     * Supports optional date range filtering via from_date and to_date query params.
     */
    public function summary(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $summary = $project->getSummary($fromDate, $toDate);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get list of projects for dropdown/select.
     * Simplified response for form selects.
     */
    public function list(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = Project::whereCompany()
            ->select(['id', 'name', 'code', 'status', 'customer_id'])
            ->when($request->input('status'), function ($query, $status) {
                $query->whereStatus($status);
            })
            ->when($request->input('customer_id'), function ($query, $customerId) {
                $query->whereCustomer($customerId);
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'code' => $project->code,
                    'status' => $project->status,
                    'customer_id' => $project->customer_id,
                    'display_name' => $project->code
                        ? "[{$project->code}] {$project->name}"
                        : $project->name,
                ];
            }),
        ]);
    }

    /**
     * Get documents (invoices, expenses, payments) for a project.
     */
    public function documents(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $type = $request->input('type', 'all');

        $data = [];

        if ($type === 'all' || $type === 'invoices') {
            $data['invoices'] = $project->invoices()
                ->with(['customer', 'currency'])
                ->select(['id', 'invoice_number', 'invoice_date', 'total', 'status', 'paid_status', 'customer_id', 'currency_id'])
                ->orderBy('invoice_date', 'desc')
                ->limit(50)
                ->get();
        }

        if ($type === 'all' || $type === 'expenses') {
            $data['expenses'] = $project->expenses()
                ->with(['category', 'currency'])
                ->select(['id', 'expense_date', 'amount', 'notes', 'expense_category_id', 'currency_id'])
                ->orderBy('expense_date', 'desc')
                ->limit(50)
                ->get();
        }

        if ($type === 'all' || $type === 'payments') {
            $data['payments'] = $project->payments()
                ->with(['customer', 'currency', 'paymentMethod'])
                ->select(['id', 'payment_number', 'payment_date', 'amount', 'customer_id', 'currency_id', 'payment_method_id'])
                ->orderBy('payment_date', 'desc')
                ->limit(50)
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}

// CLAUDE-CHECKPOINT
