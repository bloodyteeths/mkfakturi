<?php

namespace Modules\Mk\Services;

use App\Models\FiscalDevice;
use App\Models\FiscalReceipt;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\FiscalDeviceEvent;
use Modules\Mk\Models\FiscalFraudAlert;

/**
 * Fiscal Fraud Detection Service
 *
 * Monitors fiscal device activity for suspicious patterns that may indicate
 * employee fraud (closing devices to pocket cash, voiding receipts, etc.).
 *
 * Detection rules:
 * 1. Rapid open/close — device opened and closed within 5 minutes
 * 2. Off-hours activity — events outside configured business hours
 * 3. Cash discrepancy — Z-report total doesn't match system receipts
 * 4. Frequent voids — more than 3 voids per shift
 * 5. No Z-report — day ended without daily reconciliation
 * 6. Unexpected close — device closed during peak hours without Z-report
 * 7. Receipt gap — gap in sequential receipt numbers
 */
class FiscalFraudDetectionService
{
    // Default business hours (configurable per company via metadata)
    const DEFAULT_OPEN_HOUR = 8;
    const DEFAULT_CLOSE_HOUR = 20;
    const RAPID_OPEN_CLOSE_MINUTES = 5;
    const MAX_VOIDS_PER_SHIFT = 3;

    /**
     * Log a fiscal device event and run real-time fraud checks.
     */
    public function logEvent(
        int $companyId,
        int $deviceId,
        string $eventType,
        ?int $userId = null,
        ?int $cashAmount = null,
        ?string $notes = null,
        array $metadata = [],
        ?Carbon $eventAt = null
    ): FiscalDeviceEvent {
        $event = FiscalDeviceEvent::create([
            'company_id' => $companyId,
            'fiscal_device_id' => $deviceId,
            'user_id' => $userId,
            'event_type' => $eventType,
            'source' => $metadata['source'] ?? 'api',
            'cash_amount' => $cashAmount,
            'notes' => $notes,
            'metadata' => $metadata,
            'event_at' => $eventAt ?? now(),
        ]);

        // Run real-time checks on close events
        if ($eventType === FiscalDeviceEvent::TYPE_CLOSE) {
            $this->checkRapidOpenClose($event);
            $this->checkOffHours($event);
            $this->checkUnexpectedClose($event);
        }

        if ($eventType === FiscalDeviceEvent::TYPE_VOID) {
            $this->checkFrequentVoids($event);
        }

        return $event;
    }

    /**
     * Run all fraud detection checks for a company (called by scheduled command).
     */
    public function runDailyChecks(int $companyId, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::yesterday();
        $alerts = [];

        $devices = FiscalDevice::forCompany($companyId)->active()->get();

        foreach ($devices as $device) {
            // Check for missing Z-report
            $alert = $this->checkNoZReport($device, $date);
            if ($alert) {
                $alerts[] = $alert;
            }

            // Check cash discrepancy
            $alert = $this->checkCashDiscrepancy($device, $date);
            if ($alert) {
                $alerts[] = $alert;
            }

            // Check receipt gaps
            $alert = $this->checkReceiptGaps($device, $date);
            if ($alert) {
                $alerts[] = $alert;
            }
        }

        if (count($alerts) > 0) {
            Log::warning('Fiscal fraud alerts generated', [
                'company_id' => $companyId,
                'date' => $date->toDateString(),
                'alert_count' => count($alerts),
            ]);
        }

        return $alerts;
    }

    /**
     * Detect rapid open/close pattern (device opened and closed within minutes).
     * This is a common skimming pattern — employee closes register to make
     * unrecorded sales, then reopens.
     */
    private function checkRapidOpenClose(FiscalDeviceEvent $closeEvent): ?FiscalFraudAlert
    {
        $lastOpen = FiscalDeviceEvent::forDevice($closeEvent->fiscal_device_id)
            ->ofType(FiscalDeviceEvent::TYPE_OPEN)
            ->where('event_at', '<', $closeEvent->event_at)
            ->orderByDesc('event_at')
            ->first();

        if (!$lastOpen) {
            return null;
        }

        $minutesBetween = $lastOpen->event_at->diffInMinutes($closeEvent->event_at);

        if ($minutesBetween <= self::RAPID_OPEN_CLOSE_MINUTES) {
            return $this->createAlert(
                $closeEvent->company_id,
                $closeEvent->fiscal_device_id,
                $closeEvent->user_id,
                FiscalFraudAlert::TYPE_RAPID_OPEN_CLOSE,
                'critical',
                "Фискалниот апарат беше отворен и затворен за {$minutesBetween} минути. Можна злоупотреба — продажба без фискална сметка.",
                [
                    'open_event_id' => $lastOpen->id,
                    'close_event_id' => $closeEvent->id,
                    'open_at' => $lastOpen->event_at->toDateTimeString(),
                    'close_at' => $closeEvent->event_at->toDateTimeString(),
                    'minutes_between' => $minutesBetween,
                    'open_user_id' => $lastOpen->user_id,
                    'close_user_id' => $closeEvent->user_id,
                ]
            );
        }

        return null;
    }

    /**
     * Detect off-hours activity.
     */
    private function checkOffHours(FiscalDeviceEvent $event): ?FiscalFraudAlert
    {
        $hour = $event->event_at->hour;
        $device = FiscalDevice::find($event->fiscal_device_id);
        $businessHours = $device->metadata['business_hours'] ?? null;
        $openHour = $businessHours['open'] ?? self::DEFAULT_OPEN_HOUR;
        $closeHour = $businessHours['close'] ?? self::DEFAULT_CLOSE_HOUR;

        if ($hour < $openHour || $hour >= $closeHour) {
            return $this->createAlert(
                $event->company_id,
                $event->fiscal_device_id,
                $event->user_id,
                FiscalFraudAlert::TYPE_OFF_HOURS,
                'high',
                "Активност на фискалниот апарат надвор од работно време ({$event->event_at->format('H:i')}). Работно време: {$openHour}:00-{$closeHour}:00.",
                [
                    'event_id' => $event->id,
                    'event_type' => $event->event_type,
                    'event_at' => $event->event_at->toDateTimeString(),
                    'business_hours' => "{$openHour}:00-{$closeHour}:00",
                ]
            );
        }

        return null;
    }

    /**
     * Detect unexpected close during business hours without Z-report.
     */
    private function checkUnexpectedClose(FiscalDeviceEvent $closeEvent): ?FiscalFraudAlert
    {
        $hour = $closeEvent->event_at->hour;
        $device = FiscalDevice::find($closeEvent->fiscal_device_id);
        $businessHours = $device->metadata['business_hours'] ?? null;
        $closeHour = $businessHours['close'] ?? self::DEFAULT_CLOSE_HOUR;

        // If closing more than 2 hours before end of business, flag it
        if ($hour < ($closeHour - 2)) {
            // Check if a Z-report was generated before close
            $hasZReport = FiscalDeviceEvent::forDevice($closeEvent->fiscal_device_id)
                ->ofType(FiscalDeviceEvent::TYPE_Z_REPORT)
                ->whereDate('event_at', $closeEvent->event_at->toDateString())
                ->where('event_at', '<=', $closeEvent->event_at)
                ->exists();

            if (!$hasZReport) {
                return $this->createAlert(
                    $closeEvent->company_id,
                    $closeEvent->fiscal_device_id,
                    $closeEvent->user_id,
                    FiscalFraudAlert::TYPE_UNEXPECTED_CLOSE,
                    'high',
                    "Фискалниот апарат е затворен во {$closeEvent->event_at->format('H:i')} без Z-извештај, {$closeHour - $hour} часа пред крај на работно време.",
                    [
                        'event_id' => $closeEvent->id,
                        'close_at' => $closeEvent->event_at->toDateTimeString(),
                        'hours_before_end' => $closeHour - $hour,
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Detect frequent void operations (possible fraud indicator).
     */
    private function checkFrequentVoids(FiscalDeviceEvent $voidEvent): ?FiscalFraudAlert
    {
        // Count voids today for same user + device
        $voidCount = FiscalDeviceEvent::forDevice($voidEvent->fiscal_device_id)
            ->ofType(FiscalDeviceEvent::TYPE_VOID)
            ->where('user_id', $voidEvent->user_id)
            ->whereDate('event_at', $voidEvent->event_at->toDateString())
            ->count();

        if ($voidCount > self::MAX_VOIDS_PER_SHIFT) {
            return $this->createAlert(
                $voidEvent->company_id,
                $voidEvent->fiscal_device_id,
                $voidEvent->user_id,
                FiscalFraudAlert::TYPE_FREQUENT_VOIDS,
                'medium',
                "Вработениот има {$voidCount} сторнирани сметки денес (максимум " . self::MAX_VOIDS_PER_SHIFT . "). Можна злоупотреба.",
                [
                    'void_count' => $voidCount,
                    'date' => $voidEvent->event_at->toDateString(),
                    'user_id' => $voidEvent->user_id,
                ]
            );
        }

        return null;
    }

    /**
     * Check if device had activity but no Z-report (daily check).
     */
    private function checkNoZReport(FiscalDevice $device, Carbon $date): ?FiscalFraudAlert
    {
        $hadActivity = FiscalDeviceEvent::forDevice($device->id)
            ->whereDate('event_at', $date)
            ->whereIn('event_type', [
                FiscalDeviceEvent::TYPE_OPEN,
                FiscalDeviceEvent::TYPE_RECEIPT,
            ])
            ->exists();

        if (!$hadActivity) {
            return null;
        }

        $hasZReport = FiscalDeviceEvent::forDevice($device->id)
            ->ofType(FiscalDeviceEvent::TYPE_Z_REPORT)
            ->whereDate('event_at', $date)
            ->exists();

        if (!$hasZReport) {
            return $this->createAlert(
                $device->company_id,
                $device->id,
                null,
                FiscalFraudAlert::TYPE_NO_Z_REPORT,
                'high',
                "Фискалниот апарат '{$device->name}' имаше активност на {$date->toDateString()} но нема Z-извештај.",
                [
                    'date' => $date->toDateString(),
                    'device_name' => $device->name,
                    'device_serial' => $device->serial_number,
                ]
            );
        }

        return null;
    }

    /**
     * Check Z-report total vs system receipt total.
     */
    private function checkCashDiscrepancy(FiscalDevice $device, Carbon $date): ?FiscalFraudAlert
    {
        $zReport = FiscalDeviceEvent::forDevice($device->id)
            ->ofType(FiscalDeviceEvent::TYPE_Z_REPORT)
            ->whereDate('event_at', $date)
            ->orderByDesc('event_at')
            ->first();

        if (!$zReport || !isset($zReport->metadata['total_amount'])) {
            return null;
        }

        $zTotal = (int) $zReport->metadata['total_amount'];

        $systemTotal = FiscalReceipt::forDevice($device->id)
            ->whereDate('created_at', $date)
            ->sum('amount');

        $difference = abs($zTotal - $systemTotal);
        // Allow 100 cents (1 MKD) tolerance for rounding
        if ($difference > 100) {
            $severity = $difference > 500000 ? 'critical' : ($difference > 100000 ? 'high' : 'medium');

            return $this->createAlert(
                $device->company_id,
                $device->id,
                null,
                FiscalFraudAlert::TYPE_CASH_DISCREPANCY,
                $severity,
                "Разлика меѓу Z-извештај и систем: " . number_format($difference / 100, 2) . " МКД за {$date->toDateString()}.",
                [
                    'date' => $date->toDateString(),
                    'z_report_total' => $zTotal,
                    'system_total' => $systemTotal,
                    'difference' => $difference,
                    'z_event_id' => $zReport->id,
                ]
            );
        }

        return null;
    }

    /**
     * Check for gaps in receipt numbers (possible removed receipts).
     */
    private function checkReceiptGaps(FiscalDevice $device, Carbon $date): ?FiscalFraudAlert
    {
        $receipts = FiscalReceipt::forDevice($device->id)
            ->whereDate('created_at', $date)
            ->orderBy('receipt_number')
            ->pluck('receipt_number')
            ->map(fn ($n) => (int) preg_replace('/[^0-9]/', '', $n))
            ->filter(fn ($n) => $n > 0)
            ->values();

        if ($receipts->count() < 2) {
            return null;
        }

        $gaps = [];
        for ($i = 1; $i < $receipts->count(); $i++) {
            $expected = $receipts[$i - 1] + 1;
            if ($receipts[$i] > $expected) {
                $gaps[] = [
                    'after' => $receipts[$i - 1],
                    'before' => $receipts[$i],
                    'missing_count' => $receipts[$i] - $expected,
                ];
            }
        }

        if (count($gaps) > 0) {
            $totalMissing = array_sum(array_column($gaps, 'missing_count'));

            return $this->createAlert(
                $device->company_id,
                $device->id,
                null,
                FiscalFraudAlert::TYPE_GAP_IN_RECEIPTS,
                $totalMissing > 5 ? 'critical' : 'high',
                "Пронајдени {$totalMissing} недостасувачки сметки на {$date->toDateString()}. Можно бришење на фискални сметки.",
                [
                    'date' => $date->toDateString(),
                    'gaps' => $gaps,
                    'total_missing' => $totalMissing,
                ]
            );
        }

        return null;
    }

    /**
     * Create a fraud alert (deduped — won't create duplicate for same device+type+day).
     */
    private function createAlert(
        int $companyId,
        int $deviceId,
        ?int $userId,
        string $alertType,
        string $severity,
        string $description,
        array $evidence = []
    ): FiscalFraudAlert {
        // Dedupe: don't create duplicate alerts for same device+type on same day
        $existing = FiscalFraudAlert::where('company_id', $companyId)
            ->where('fiscal_device_id', $deviceId)
            ->where('alert_type', $alertType)
            ->whereDate('created_at', today())
            ->unresolved()
            ->first();

        if ($existing) {
            // Append evidence to existing alert
            $existingEvidence = $existing->evidence ?? [];
            $existingEvidence['additional'][] = $evidence;
            $existing->update(['evidence' => $existingEvidence]);
            return $existing;
        }

        $alert = FiscalFraudAlert::create([
            'company_id' => $companyId,
            'fiscal_device_id' => $deviceId,
            'user_id' => $userId,
            'alert_type' => $alertType,
            'severity' => $severity,
            'description' => $description,
            'evidence' => $evidence,
            'status' => FiscalFraudAlert::STATUS_OPEN,
        ]);

        Log::warning('Fiscal fraud alert created', [
            'alert_id' => $alert->id,
            'type' => $alertType,
            'severity' => $severity,
            'device_id' => $deviceId,
            'company_id' => $companyId,
        ]);

        return $alert;
    }

    /**
     * Get device status dashboard data for a company.
     */
    public function getDashboard(int $companyId): array
    {
        $devices = FiscalDevice::forCompany($companyId)
            ->active()
            ->withCount('receipts')
            ->get();

        $dashboard = [];
        foreach ($devices as $device) {
            $lastEvent = FiscalDeviceEvent::forDevice($device->id)
                ->orderByDesc('event_at')
                ->first();

            $todayEvents = FiscalDeviceEvent::forDevice($device->id)
                ->whereDate('event_at', today())
                ->orderBy('event_at')
                ->get();

            $todayReceipts = FiscalReceipt::forDevice($device->id)
                ->whereDate('created_at', today())
                ->count();

            $todayRevenue = FiscalReceipt::forDevice($device->id)
                ->whereDate('created_at', today())
                ->sum('amount');

            $isOpen = false;
            if ($lastEvent) {
                $isOpen = $lastEvent->event_type === FiscalDeviceEvent::TYPE_OPEN;
            }

            $dashboard[] = [
                'device' => [
                    'id' => $device->id,
                    'name' => $device->name ?? $device->serial_number,
                    'type' => $device->device_type,
                    'serial_number' => $device->serial_number,
                ],
                'status' => $isOpen ? 'open' : 'closed',
                'last_event' => $lastEvent ? [
                    'type' => $lastEvent->event_type,
                    'at' => $lastEvent->event_at->toDateTimeString(),
                    'user' => $lastEvent->user ? $lastEvent->user->name : null,
                ] : null,
                'today' => [
                    'events' => $todayEvents->map(fn ($e) => [
                        'type' => $e->event_type,
                        'at' => $e->event_at->format('H:i'),
                        'user_id' => $e->user_id,
                    ]),
                    'receipt_count' => $todayReceipts,
                    'revenue' => $todayRevenue,
                ],
            ];
        }

        $openAlerts = FiscalFraudAlert::forCompany($companyId)
            ->unresolved()
            ->with(['fiscalDevice:id,name,serial_number', 'user:id,name'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return [
            'devices' => $dashboard,
            'alerts' => $openAlerts,
            'summary' => [
                'total_devices' => count($dashboard),
                'open_devices' => collect($dashboard)->where('status', 'open')->count(),
                'closed_devices' => collect($dashboard)->where('status', 'closed')->count(),
                'open_alerts' => $openAlerts->count(),
                'critical_alerts' => $openAlerts->where('severity', 'critical')->count(),
            ],
        ];
    }

    /**
     * Get audit report: activity by employee for a date range.
     */
    public function getAuditReport(int $companyId, Carbon $from, Carbon $to, ?int $deviceId = null, ?int $userId = null): array
    {
        $query = FiscalDeviceEvent::forCompany($companyId)
            ->between($from, $to)
            ->with(['user:id,name,email', 'fiscalDevice:id,name,serial_number']);

        if ($deviceId) {
            $query->forDevice($deviceId);
        }
        if ($userId) {
            $query->forUser($userId);
        }

        $events = $query->orderBy('event_at')->get();

        // Group by user
        $byUser = $events->groupBy('user_id')->map(function ($userEvents, $userId) {
            $user = $userEvents->first()->user;
            return [
                'user_id' => $userId,
                'user_name' => $user ? $user->name : 'Систем',
                'total_events' => $userEvents->count(),
                'opens' => $userEvents->where('event_type', 'open')->count(),
                'closes' => $userEvents->where('event_type', 'close')->count(),
                'receipts' => $userEvents->where('event_type', 'receipt')->count(),
                'voids' => $userEvents->where('event_type', 'void')->count(),
                'z_reports' => $userEvents->where('event_type', 'z_report')->count(),
                'first_event' => $userEvents->min('event_at'),
                'last_event' => $userEvents->max('event_at'),
            ];
        })->values();

        // Group by device
        $byDevice = $events->groupBy('fiscal_device_id')->map(function ($deviceEvents, $deviceId) {
            $device = $deviceEvents->first()->fiscalDevice;
            return [
                'device_id' => $deviceId,
                'device_name' => $device ? ($device->name ?? $device->serial_number) : 'Unknown',
                'total_events' => $deviceEvents->count(),
                'unique_users' => $deviceEvents->pluck('user_id')->unique()->count(),
                'opens' => $deviceEvents->where('event_type', 'open')->count(),
                'closes' => $deviceEvents->where('event_type', 'close')->count(),
                'receipts' => $deviceEvents->where('event_type', 'receipt')->count(),
                'voids' => $deviceEvents->where('event_type', 'void')->count(),
            ];
        })->values();

        // Daily summary
        $byDay = $events->groupBy(fn ($e) => $e->event_at->toDateString())->map(function ($dayEvents, $date) {
            return [
                'date' => $date,
                'total_events' => $dayEvents->count(),
                'opens' => $dayEvents->where('event_type', 'open')->count(),
                'closes' => $dayEvents->where('event_type', 'close')->count(),
                'receipts' => $dayEvents->where('event_type', 'receipt')->count(),
                'voids' => $dayEvents->where('event_type', 'void')->count(),
            ];
        })->values();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'by_user' => $byUser,
            'by_device' => $byDevice,
            'by_day' => $byDay,
            'total_events' => $events->count(),
        ];
    }
}

// CLAUDE-CHECKPOINT
