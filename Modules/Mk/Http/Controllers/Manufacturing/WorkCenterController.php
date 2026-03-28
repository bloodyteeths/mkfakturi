<?php

namespace Modules\Mk\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Models\Manufacturing\WorkCenter;

class WorkCenterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $query = WorkCenter::where('company_id', $companyId)
            ->withCount('productionOrders')
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->has('active_only')) {
            $query->active();
        }

        $workCenters = $query->get();

        return response()->json([
            'success' => true,
            'data' => $workCenters,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'capacity_hours_per_day' => 'nullable|numeric|min:0|max:24',
            'hourly_rate' => 'nullable|integer|min:0',
            'overhead_rate' => 'nullable|integer|min:0',
            'target_availability' => 'nullable|numeric|min:0|max:100',
            'target_performance' => 'nullable|numeric|min:0|max:100',
            'target_quality' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $companyId = (int) $request->header('company');
        $validated['company_id'] = $companyId;

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $count = WorkCenter::where('company_id', $companyId)->withTrashed()->count();
            $validated['code'] = 'WC-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
        }

        $workCenter = WorkCenter::create($validated);

        return response()->json([
            'success' => true,
            'data' => $workCenter,
            'message' => 'Work center created.',
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $workCenter = WorkCenter::where('company_id', $companyId)
            ->withCount('productionOrders')
            ->findOrFail($id);

        // Calculate current month OEE
        $now = \Carbon\Carbon::now();
        $oee = $workCenter->calculateOee(
            $now->copy()->startOfMonth()->toDateString(),
            $now->toDateString()
        );

        return response()->json([
            'success' => true,
            'data' => array_merge($workCenter->toArray(), [
                'oee' => $oee,
                'target_oee' => $workCenter->getTargetOee(),
            ]),
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $workCenter = WorkCenter::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'code' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'capacity_hours_per_day' => 'nullable|numeric|min:0|max:24',
            'hourly_rate' => 'nullable|integer|min:0',
            'overhead_rate' => 'nullable|integer|min:0',
            'target_availability' => 'nullable|numeric|min:0|max:100',
            'target_performance' => 'nullable|numeric|min:0|max:100',
            'target_quality' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $workCenter->update($validated);

        return response()->json([
            'success' => true,
            'data' => $workCenter->fresh(),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $workCenter = WorkCenter::where('company_id', $companyId)->findOrFail($id);

        // Prevent deletion if used by active orders
        $activeOrders = $workCenter->productionOrders()
            ->whereIn('status', ['draft', 'in_progress'])
            ->count();

        if ($activeOrders > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete: {$activeOrders} active orders use this work center.",
            ], 422);
        }

        $workCenter->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work center deleted.',
        ]);
    }

    /**
     * OEE metrics for a specific work center.
     */
    public function oee(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $workCenter = WorkCenter::where('company_id', $companyId)->findOrFail($id);

        $from = $request->query('from');
        $to = $request->query('to');

        $oee = $workCenter->calculateOee($from, $to);

        return response()->json([
            'success' => true,
            'data' => array_merge($oee, [
                'target_oee' => $workCenter->getTargetOee(),
                'targets' => [
                    'availability' => (float) $workCenter->target_availability,
                    'performance' => (float) $workCenter->target_performance,
                    'quality' => (float) $workCenter->target_quality,
                ],
            ]),
        ]);
    }

    /**
     * OEE summary across all work centers (for dashboard).
     */
    public function oeeSummary(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $now = \Carbon\Carbon::now();
        $from = $request->query('from', $now->copy()->startOfMonth()->toDateString());
        $to = $request->query('to', $now->toDateString());

        $workCenters = WorkCenter::where('company_id', $companyId)
            ->active()
            ->orderBy('sort_order')
            ->get();

        $summary = $workCenters->map(function ($wc) use ($from, $to) {
            $oee = $wc->calculateOee($from, $to);

            return [
                'id' => $wc->id,
                'name' => $wc->name,
                'code' => $wc->code,
                'oee' => $oee['oee'],
                'availability' => $oee['availability'],
                'performance' => $oee['performance'],
                'quality' => $oee['quality'],
                'target_oee' => $wc->getTargetOee(),
                'order_count' => $oee['order_count'],
                'status' => $oee['oee'] >= $wc->getTargetOee() ? 'good'
                    : ($oee['oee'] >= $wc->getTargetOee() * 0.8 ? 'warning' : 'critical'),
            ];
        });

        // Overall OEE (weighted by order count)
        $totalOrders = $summary->sum('order_count');
        $weightedOee = $totalOrders > 0
            ? round($summary->sum(fn ($wc) => $wc['oee'] * $wc['order_count']) / $totalOrders, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'overall_oee' => $weightedOee,
                'work_centers' => $summary->values(),
                'period' => ['from' => $from, 'to' => $to],
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
