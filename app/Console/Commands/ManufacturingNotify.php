<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

/**
 * Scheduled command to send manufacturing alerts:
 * - Overdue production orders
 * - Low stock on BOM materials
 * - Failed QC checks pending disposition
 *
 * Schedule: daily at 07:00 Skopje time
 */
class ManufacturingNotifyCommand extends Command
{
    protected $signature = 'manufacturing:notify';
    protected $description = 'Send manufacturing alerts (overdue orders, low stock, QC failures)';

    public function handle(): int
    {
        $companies = DB::table('production_orders')
            ->select('company_id')
            ->distinct()
            ->pluck('company_id');

        $totalAlerts = 0;

        foreach ($companies as $companyId) {
            $alerts = $this->gatherAlerts($companyId);
            if (empty($alerts)) {
                continue;
            }

            $company = \App\Models\Company::find($companyId);
            if (! $company) {
                continue;
            }

            // Send to company owner
            $owner = $company->owner;
            if (! $owner || ! $owner->email) {
                continue;
            }

            $totalAlerts += count($alerts);
            $this->sendAlertEmail($owner, $company, $alerts);
        }

        $this->info("Sent {$totalAlerts} manufacturing alerts across {$companies->count()} companies.");

        return self::SUCCESS;
    }

    private function gatherAlerts(int $companyId): array
    {
        $alerts = [];

        // 1. Overdue orders
        $overdue = ProductionOrder::where('company_id', $companyId)
            ->whereIn('status', [ProductionOrder::STATUS_DRAFT, ProductionOrder::STATUS_IN_PROGRESS])
            ->whereNotNull('expected_completion_date')
            ->where('expected_completion_date', '<', now())
            ->with('outputItem:id,name')
            ->get();

        foreach ($overdue as $order) {
            $daysLate = now()->diffInDays($order->expected_completion_date);
            $alerts[] = [
                'type' => 'overdue',
                'severity' => $daysLate > 3 ? 'critical' : 'warning',
                'message' => "{$order->order_number} ({$order->outputItem?->name}) — {$daysLate} дена задоцнување",
                'order_id' => $order->id,
            ];
        }

        // 2. Low stock on BOM materials (items below minimum_quantity that are used in active orders)
        $activeOrderIds = ProductionOrder::where('company_id', $companyId)
            ->where('status', ProductionOrder::STATUS_IN_PROGRESS)
            ->pluck('id');

        if ($activeOrderIds->isNotEmpty()) {
            // Get distinct material item IDs with minimum_quantity set
            $materialItems = DB::table('production_order_materials as m')
                ->join('items as i', 'i.id', '=', 'm.item_id')
                ->whereIn('m.production_order_id', $activeOrderIds)
                ->whereNotNull('i.minimum_quantity')
                ->where('i.minimum_quantity', '>', 0)
                ->select('i.id', 'i.name', 'i.minimum_quantity')
                ->distinct()
                ->get();

            // Check actual stock via stock_movements (not stale items.quantity)
            $stockService = app(\App\Services\StockService::class);
            foreach ($materialItems as $item) {
                $stock = $stockService->getItemStock($companyId, $item->id);
                $currentQty = $stock['current_quantity'] ?? $stock['quantity'] ?? 0;

                if ($currentQty <= $item->minimum_quantity) {
                    $alerts[] = [
                        'type' => 'low_stock',
                        'severity' => $currentQty <= 0 ? 'critical' : 'warning',
                        'message' => "{$item->name} — залиха: " . round($currentQty) . " (мин: {$item->minimum_quantity})",
                    ];
                }
            }
        }

        // 3. Failed QC checks without disposition
        $pendingDisposition = DB::table('production_qc_checks as qc')
            ->join('production_orders as po', 'po.id', '=', 'qc.production_order_id')
            ->where('po.company_id', $companyId)
            ->whereIn('qc.result', ['fail', 'conditional'])
            ->where('qc.disposition', 'none')
            ->where('qc.quantity_rejected', '>', 0)
            ->select('po.order_number', 'qc.quantity_rejected', 'qc.check_date')
            ->get();

        foreach ($pendingDisposition as $qc) {
            $alerts[] = [
                'type' => 'qc_pending',
                'severity' => 'warning',
                'message' => "{$qc->order_number} — {$qc->quantity_rejected} одбиени, чека диспозиција",
            ];
        }

        return $alerts;
    }

    private function sendAlertEmail($user, $company, array $alerts): void
    {
        $critical = collect($alerts)->where('severity', 'critical')->count();
        $warning = collect($alerts)->where('severity', 'warning')->count();

        $subject = "⚠ Производство: {$critical} критични, {$warning} предупредувања — {$company->name}";

        $body = "<h2>Производствени известувања</h2>";
        $body .= "<p>Компанија: <strong>{$company->name}</strong></p>";
        $body .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; font-size:14px;'>";
        $body .= "<tr style='background:#f3f4f6;'><th>Тип</th><th>Приоритет</th><th>Детали</th></tr>";

        $typeLabels = [
            'overdue' => 'Задоцнет налог',
            'low_stock' => 'Ниска залиха',
            'qc_pending' => 'QC чека одлука',
        ];

        foreach ($alerts as $alert) {
            $sevColor = $alert['severity'] === 'critical' ? '#dc2626' : '#d97706';
            $body .= "<tr>";
            $body .= "<td>" . ($typeLabels[$alert['type']] ?? $alert['type']) . "</td>";
            $body .= "<td style='color:{$sevColor}; font-weight:bold;'>" . strtoupper($alert['severity']) . "</td>";
            $body .= "<td>{$alert['message']}</td>";
            $body .= "</tr>";
        }

        $body .= "</table>";
        $body .= "<p style='margin-top:16px;'><a href='https://app.facturino.mk/admin/manufacturing' style='color:#4f46e5;'>Отвори производство →</a></p>";

        try {
            Mail::html($body, function ($m) use ($user, $subject) {
                $m->to($user->email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
                $m->getSymfonyMessage()->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });
        } catch (\Exception $e) {
            \Log::warning("Manufacturing notify email failed: " . $e->getMessage());
        }
    }
}

// CLAUDE-CHECKPOINT
