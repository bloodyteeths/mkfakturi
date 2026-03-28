<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Mk\Models\FiscalDeviceEvent;
use Modules\Mk\Models\FiscalFraudAlert;
use Modules\Mk\Services\FiscalFraudDetectionService;

/**
 * Fiscal Fraud Detection Controller
 *
 * Endpoints for logging fiscal device events, viewing fraud alerts,
 * real-time device status dashboard, and audit reports.
 */
class FiscalFraudController extends Controller
{
    public function __construct(
        private FiscalFraudDetectionService $fraudService
    ) {
    }

    /**
     * GET /fiscal-monitor/dashboard
     * Real-time status of all fiscal devices + open alerts.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $data = $this->fraudService->getDashboard($companyId);

        return response()->json(['data' => $data]);
    }

    /**
     * POST /fiscal-monitor/events
     * Log a fiscal device event (open, close, void, z_report, etc.).
     */
    public function logEvent(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fiscal_device_id' => 'required|integer|exists:fiscal_devices,id',
                'event_type' => 'required|string|in:open,close,z_report,error,receipt,void,status_check',
                'cash_amount' => 'nullable|integer|min:0',
                'notes' => 'nullable|string|max:500',
                'metadata' => 'nullable|array',
                'event_at' => 'nullable|date',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');

        $event = $this->fraudService->logEvent(
            $companyId,
            $validated['fiscal_device_id'],
            $validated['event_type'],
            $request->user()?->id,
            $validated['cash_amount'] ?? null,
            $validated['notes'] ?? null,
            array_merge($validated['metadata'] ?? [], [
                'source' => 'api',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]),
            isset($validated['event_at']) ? Carbon::parse($validated['event_at']) : null
        );

        return response()->json(['data' => $event->load('user:id,name')], 201);
    }

    /**
     * GET /fiscal-monitor/events
     * List events with filters.
     */
    public function events(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $query = FiscalDeviceEvent::forCompany($companyId)
            ->with(['user:id,name', 'fiscalDevice:id,name,serial_number'])
            ->orderByDesc('event_at');

        if ($request->filled('device_id')) {
            $query->forDevice((int) $request->query('device_id'));
        }
        if ($request->filled('user_id')) {
            $query->forUser((int) $request->query('user_id'));
        }
        if ($request->filled('event_type')) {
            $query->ofType($request->query('event_type'));
        }
        if ($request->filled('from')) {
            $query->where('event_at', '>=', Carbon::parse($request->query('from')));
        }
        if ($request->filled('to')) {
            $query->where('event_at', '<=', Carbon::parse($request->query('to'))->endOfDay());
        }

        $limit = (int) ($request->query('limit') ?? 50);
        $events = $query->paginate(min($limit, 100));

        return response()->json($events);
    }

    /**
     * GET /fiscal-monitor/alerts
     * List fraud alerts with filters.
     */
    public function alerts(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $query = FiscalFraudAlert::forCompany($companyId)
            ->with(['fiscalDevice:id,name,serial_number', 'user:id,name'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        } else {
            $query->unresolved();
        }

        if ($request->filled('device_id')) {
            $query->where('fiscal_device_id', (int) $request->query('device_id'));
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }
        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->query('alert_type'));
        }

        $alerts = $query->paginate(20);

        return response()->json($alerts);
    }

    /**
     * PATCH /fiscal-monitor/alerts/{id}
     * Update alert status (acknowledge, investigate, resolve, false_positive).
     */
    public function updateAlert(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|string|in:acknowledged,investigated,resolved,false_positive',
                'resolution_notes' => 'nullable|string|max:1000',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');
        $alert = FiscalFraudAlert::forCompany($companyId)->findOrFail($id);

        $updateData = ['status' => $validated['status']];

        if (in_array($validated['status'], ['resolved', 'false_positive'])) {
            $updateData['resolved_by'] = $request->user()?->id;
            $updateData['resolved_at'] = now();
            $updateData['resolution_notes'] = $validated['resolution_notes'] ?? null;
        }

        $alert->update($updateData);

        return response()->json(['data' => $alert->fresh(['fiscalDevice', 'user', 'resolvedByUser'])]);
    }

    /**
     * GET /fiscal-monitor/audit-report
     * Employee activity audit report for a date range.
     */
    public function auditReport(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))
            : Carbon::now()->subDays(30);
        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))
            : Carbon::now();

        $deviceId = $request->filled('device_id') ? (int) $request->query('device_id') : null;
        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;

        $report = $this->fraudService->getAuditReport($companyId, $from, $to, $deviceId, $userId);

        return response()->json(['data' => $report]);
    }
}

// CLAUDE-CHECKPOINT
