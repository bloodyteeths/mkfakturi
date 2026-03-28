<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\FiscalDevice;
use App\Models\FiscalReceipt;
use Barryvdh\DomPDF\Facade\Pdf;
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
 *
 * Access: owner OR users with 'view-fiscal-monitor' ability.
 */
class FiscalFraudController extends Controller
{
    public function __construct(
        private FiscalFraudDetectionService $fraudService
    ) {
    }

    /**
     * Check if the current user has permission to access the fiscal monitor.
     * Owners and users with 'view-fiscal-monitor' ability can access.
     */
    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        if ($user->isOwner()) {
            return;
        }

        if ($user->can('view-fiscal-monitor')) {
            return;
        }

        abort(403, 'You do not have permission to access the Fiscal Monitor.');
    }

    /**
     * GET /fiscal-monitor/dashboard
     * Real-time status of all fiscal devices + open alerts.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $this->authorizeAccess($request);

        $companyId = (int) $request->header('company');
        $data = $this->fraudService->getDashboard($companyId);

        return response()->json(['data' => $data]);
    }

    /**
     * POST /fiscal-monitor/events
     * Log a fiscal device event (open, close, void, z_report, etc.).
     * Any authenticated user can log events (cashiers at their workstation).
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

        // Verify the device belongs to this company
        $device = FiscalDevice::forCompany($companyId)->find($validated['fiscal_device_id']);
        if (!$device) {
            return response()->json(['error' => 'Device not found for this company'], 404);
        }

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
        $this->authorizeAccess($request);

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
        $this->authorizeAccess($request);

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
        $this->authorizeAccess($request);

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
        $this->authorizeAccess($request);

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

    /**
     * GET /fiscal-monitor/audit-report-pdf
     * Export audit report as PDF (Записник за контрола на фискални апарати).
     */
    public function exportAuditReport(Request $request)
    {
        $this->authorizeAccess($request);

        $companyId = (int) $request->header('company');
        $company = Company::find($companyId);

        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))
            : Carbon::now()->subDays(30);
        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))
            : Carbon::now();

        $deviceId = $request->filled('device_id') ? (int) $request->query('device_id') : null;
        $userId = $request->filled('user_id') ? (int) $request->query('user_id') : null;

        $report = $this->fraudService->getAuditReport($companyId, $from, $to, $deviceId, $userId);

        $generatedAt = Carbon::now('Europe/Skopje')->format('d.m.Y H:i');
        $totalEvents = $report['total_events'] ?? 0;

        $pdf = Pdf::loadView('app.pdf.reports.fiscal-audit', [
            'company' => $company,
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'generatedAt' => $generatedAt,
            'totalEvents' => $totalEvents,
        ]);

        return $pdf->download("fiscal-audit-{$from->toDateString()}-{$to->toDateString()}.pdf");
    }

    /**
     * GET /fiscal-monitor/devices/{id}
     * Detailed view for a single device: event log, alerts, daily stats.
     */
    public function deviceDetail(Request $request, int $id): JsonResponse
    {
        $this->authorizeAccess($request);

        $companyId = (int) $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->findOrFail($id);

        // Last event to determine open/closed status
        $lastEvent = FiscalDeviceEvent::forDevice($device->id)
            ->orderByDesc('event_at')
            ->first();

        $isOpen = $lastEvent && $lastEvent->event_type === FiscalDeviceEvent::TYPE_OPEN;

        // Recent events (last 100)
        $recentEvents = FiscalDeviceEvent::forDevice($device->id)
            ->with(['user:id,name'])
            ->orderByDesc('event_at')
            ->limit(100)
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'event_type' => $e->event_type,
                'user' => $e->user ? ['id' => $e->user->id, 'name' => $e->user->name] : null,
                'cash_amount' => $e->cash_amount,
                'notes' => $e->notes,
                'source' => $e->source,
                'event_at' => $e->event_at->toDateTimeString(),
            ]);

        // Open alerts for this device
        $alerts = FiscalFraudAlert::where('fiscal_device_id', $device->id)
            ->unresolved()
            ->with(['user:id,name'])
            ->orderByDesc('created_at')
            ->get();

        // Daily stats for last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $dailyStats = FiscalDeviceEvent::forDevice($device->id)
            ->where('event_at', '>=', $thirtyDaysAgo)
            ->selectRaw("DATE(event_at) as date")
            ->selectRaw("SUM(CASE WHEN event_type = 'open' THEN 1 ELSE 0 END) as opens")
            ->selectRaw("SUM(CASE WHEN event_type = 'close' THEN 1 ELSE 0 END) as closes")
            ->selectRaw("SUM(CASE WHEN event_type = 'receipt' THEN 1 ELSE 0 END) as receipts")
            ->selectRaw("SUM(CASE WHEN event_type = 'void' THEN 1 ELSE 0 END) as voids")
            ->selectRaw("SUM(CASE WHEN event_type = 'z_report' THEN 1 ELSE 0 END) as z_reports")
            ->selectRaw("COUNT(*) as total_events")
            ->groupByRaw("DATE(event_at)")
            ->orderByDesc('date')
            ->get();

        // Receipt totals per day (from fiscal_receipts)
        $dailyRevenue = FiscalReceipt::forDevice($device->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->selectRaw("DATE(created_at) as date")
            ->selectRaw("SUM(amount) as total_amount")
            ->selectRaw("SUM(vat_amount) as total_vat")
            ->selectRaw("COUNT(*) as receipt_count")
            ->groupByRaw("DATE(created_at)")
            ->orderByDesc('date')
            ->get()
            ->keyBy('date');

        // Merge revenue into daily stats
        $dailyStatsWithRevenue = $dailyStats->map(fn ($day) => [
            'date' => $day->date,
            'opens' => (int) $day->opens,
            'closes' => (int) $day->closes,
            'receipts' => (int) $day->receipts,
            'voids' => (int) $day->voids,
            'z_reports' => (int) $day->z_reports,
            'total_events' => (int) $day->total_events,
            'revenue' => (int) ($dailyRevenue[$day->date]->total_amount ?? 0),
            'vat' => (int) ($dailyRevenue[$day->date]->total_vat ?? 0),
            'fiscal_receipts' => (int) ($dailyRevenue[$day->date]->receipt_count ?? 0),
        ]);

        // Users who operated this device
        $operators = FiscalDeviceEvent::forDevice($device->id)
            ->whereNotNull('user_id')
            ->with('user:id,name,email')
            ->selectRaw('user_id, COUNT(*) as event_count, MIN(event_at) as first_event, MAX(event_at) as last_event')
            ->groupBy('user_id')
            ->orderByDesc('event_count')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'user_name' => $row->user?->name,
                'user_email' => $row->user?->email,
                'event_count' => (int) $row->event_count,
                'first_event' => $row->first_event,
                'last_event' => $row->last_event,
            ]);

        return response()->json([
            'data' => [
                'device' => [
                    'id' => $device->id,
                    'name' => $device->name,
                    'serial_number' => $device->serial_number,
                    'device_type' => $device->device_type,
                    'connection_type' => $device->connection_type,
                    'is_active' => $device->is_active,
                    'ip_address' => $device->ip_address,
                    'port' => $device->port,
                    'metadata' => $device->metadata,
                ],
                'status' => $isOpen ? 'open' : 'closed',
                'last_event' => $lastEvent ? [
                    'type' => $lastEvent->event_type,
                    'at' => $lastEvent->event_at->toDateTimeString(),
                    'user_id' => $lastEvent->user_id,
                ] : null,
                'recent_events' => $recentEvents,
                'alerts' => $alerts,
                'daily_stats' => $dailyStatsWithRevenue,
                'operators' => $operators,
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
