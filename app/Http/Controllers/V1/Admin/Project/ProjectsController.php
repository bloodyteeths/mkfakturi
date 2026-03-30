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
        $this->authorize('viewAny', Project::class);

        $limit = $request->input('limit', 10);

        $type = $request->input('type');

        $query = Project::with([
            'customer',
            'currency',
            'company',
            'creator',
        ])
            ->withCount(['invoices', 'expenses', 'payments'])
            ->whereCompany()
            ->applyFilters($request->all());

        // For branches, also load warehouse and device counts
        if ($type === 'branch') {
            $query->withCount(['warehouses', 'fiscalDevices']);
            $query->with('manager');
        }

        $projects = $query->paginateData($limit);

        return ProjectResource::collection($projects)
            ->additional([
                'meta' => [
                    'project_total_count' => Project::whereCompany()->projects()->count(),
                    'branch_total_count' => Project::whereCompany()->branches()->count(),
                    'all_total_count' => Project::whereCompany()->count(),
                    'open_count' => Project::whereCompany()->projects()->open()->count(),
                    'in_progress_count' => Project::whereCompany()->projects()->inProgress()->count(),
                    'completed_count' => Project::whereCompany()->projects()->completed()->count(),
                    'on_hold_count' => Project::whereCompany()->projects()->onHold()->count(),
                    'cancelled_count' => Project::whereCompany()->projects()->cancelled()->count(),
                    'active_branch_count' => Project::whereCompany()->activeBranches()->count(),
                ],
            ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(ProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        // Check usage limit
        $usageService = app(\App\Services\UsageLimitService::class);
        $company = \App\Models\Company::find($request->header('company'));
        if ($company && ! $usageService->canUse($company, 'projects_total')) {
            return response()->json($usageService->buildLimitExceededResponse($company, 'projects_total'), 402);
        }

        $project = Project::createProject($request);
        $project->load(['customer', 'currency', 'company', 'creator']);

        return (new ProjectResource($project))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $relations = ['customer', 'currency', 'company', 'creator'];
        $counts = ['invoices', 'expenses', 'payments'];

        if ($project->isBranch()) {
            $relations[] = 'manager';
            $relations[] = 'parent';
            $counts[] = 'warehouses';
            $counts[] = 'fiscalDevices';
        }

        $project->load($relations);
        $project->loadCount($counts);

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
            ->select(['id', 'name', 'code', 'status', 'customer_id', 'type', 'city', 'is_active'])
            ->when($request->input('type'), function ($query, $type) {
                $query->whereType($type);
            })
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
                    'type' => $project->type ?? 'project',
                    'city' => $project->city,
                    'is_active' => $project->is_active,
                    'customer_id' => $project->customer_id,
                    'display_name' => $project->code
                        ? "[{$project->code}] {$project->name}"
                        : $project->name,
                ];
            }),
        ]);
    }

    /**
     * Get side-by-side financial comparison of all branches.
     */
    public function branchComparison(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $branches = Project::whereCompany()
            ->branches()
            ->where('is_active', true)
            ->with(['currency', 'manager'])
            ->withCount(['invoices', 'expenses', 'payments', 'warehouses', 'fiscalDevices'])
            ->orderBy('name')
            ->get();

        $data = $branches->map(function ($branch) use ($fromDate, $toDate) {
            $summary = $branch->getSummary($fromDate, $toDate);

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'city' => $branch->city,
                'manager' => $branch->manager ? $branch->manager->name : null,
                'warehouse_count' => $branch->warehouses_count,
                'fiscal_device_count' => $branch->fiscal_devices_count,
                'total_invoiced' => $summary['total_invoiced'],
                'total_expenses' => $summary['total_expenses'],
                'total_payments' => $summary['total_payments'],
                'net_result' => $summary['net_result'],
                'invoice_count' => $summary['invoice_count'],
                'expense_count' => $summary['expense_count'],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
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

