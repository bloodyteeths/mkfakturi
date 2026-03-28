<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Scheduled command to check low stock items and optionally notify company owners.
 *
 * Severity levels:
 * - critical: quantity = 0
 * - warning: quantity < 50% of minimum_quantity
 * - low: quantity < minimum_quantity (but >= 50%)
 *
 * Schedule: daily at 08:00 Skopje time
 */
class CheckLowStockCommand extends Command
{
    protected $signature = 'stock:check-low-stock
        {--company= : Check only a specific company ID}
        {--notify : Send email digest to company owners}';

    protected $description = 'Check low stock items and optionally send email alerts to company owners';

    public function handle(StockService $stockService): int
    {
        $companyId = $this->option('company');
        $shouldNotify = $this->option('notify');

        $query = Company::query();
        if ($companyId) {
            $query->where('id', $companyId);
        }

        $companies = $query->get();
        $totalAlerts = 0;
        $companiesNotified = 0;

        foreach ($companies as $company) {
            $lowStockItems = $this->getLowStockByCompany($company->id, $stockService);

            if (empty($lowStockItems)) {
                continue;
            }

            // Group by severity
            $critical = array_filter($lowStockItems, fn ($i) => $i['severity'] === 'critical');
            $warning = array_filter($lowStockItems, fn ($i) => $i['severity'] === 'warning');
            $low = array_filter($lowStockItems, fn ($i) => $i['severity'] === 'low');

            $totalAlerts += count($lowStockItems);

            // Console output
            $this->info("Company: {$company->name} (ID: {$company->id})");
            $this->info("  Critical: " . count($critical) . " | Warning: " . count($warning) . " | Low: " . count($low));

            if ($this->getOutput()->isVerbose()) {
                $tableData = array_map(fn ($i) => [
                    $i['name'],
                    $i['sku'] ?? '-',
                    $i['current_quantity'],
                    $i['minimum_quantity'],
                    strtoupper($i['severity']),
                ], $lowStockItems);

                $this->table(['Name', 'SKU', 'Current Qty', 'Min Qty', 'Severity'], $tableData);
            }

            // Send email notification
            if ($shouldNotify) {
                $owner = $company->owner;
                if ($owner && $owner->email) {
                    $this->sendLowStockEmail($owner, $company, $lowStockItems);
                    $companiesNotified++;
                } else {
                    $this->warn("  No owner email for company {$company->name}, skipping notification.");
                }
            }
        }

        $this->info("Total: {$totalAlerts} low stock items across {$companies->count()} companies.");
        if ($shouldNotify) {
            $this->info("Notifications sent to {$companiesNotified} company owners.");
        }

        return self::SUCCESS;
    }

    /**
     * Get low stock items for a company, grouped by severity.
     */
    private function getLowStockByCompany(int $companyId, StockService $stockService): array
    {
        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->whereNotNull('minimum_quantity')
            ->where('minimum_quantity', '>', 0)
            ->with(['unit'])
            ->get();

        $lowStockItems = [];

        foreach ($items as $item) {
            $stock = $stockService->getItemStock($companyId, $item->id);
            $currentQty = $stock['quantity'];

            if ($currentQty > $item->minimum_quantity) {
                continue;
            }

            // Determine severity
            if ($currentQty <= 0) {
                $severity = 'critical';
            } elseif ($currentQty < ($item->minimum_quantity * 0.5)) {
                $severity = 'warning';
            } else {
                $severity = 'low';
            }

            $lowStockItems[] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'unit_name' => $item->unit?->name,
                'current_quantity' => $currentQty,
                'minimum_quantity' => $item->minimum_quantity,
                'severity' => $severity,
            ];
        }

        // Sort: critical first, then warning, then low
        $severityOrder = ['critical' => 0, 'warning' => 1, 'low' => 2];
        usort($lowStockItems, fn ($a, $b) => ($severityOrder[$a['severity']] ?? 3) <=> ($severityOrder[$b['severity']] ?? 3));

        return $lowStockItems;
    }

    /**
     * Send low stock email digest to company owner.
     */
    private function sendLowStockEmail($user, $company, array $items): void
    {
        $count = count($items);
        $criticalCount = count(array_filter($items, fn ($i) => $i['severity'] === 'critical'));

        $subject = "Ниска залиха: {$count} артикли бараат внимание";
        if ($criticalCount > 0) {
            $subject = "⚠ Ниска залиха: {$criticalCount} критични, {$count} вкупно — {$company->name}";
        }

        $severityLabels = [
            'critical' => 'КРИТИЧНО',
            'warning' => 'ПРЕДУПРЕДУВАЊЕ',
            'low' => 'НИСКО',
        ];

        $severityColors = [
            'critical' => '#dc2626',
            'warning' => '#d97706',
            'low' => '#2563eb',
        ];

        $body = "<h2>Известување за ниска залиха</h2>";
        $body .= "<p>Компанија: <strong>{$company->name}</strong></p>";
        $body .= "<p>{$count} артикли се под минимално ниво на залиха.</p>";
        $body .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%; font-size:14px;'>";
        $body .= "<tr style='background:#f3f4f6;'><th>Артикал</th><th>SKU</th><th>Тековна кол.</th><th>Минимум</th><th>Статус</th></tr>";

        foreach ($items as $item) {
            $sevColor = $severityColors[$item['severity']] ?? '#6b7280';
            $sevLabel = $severityLabels[$item['severity']] ?? $item['severity'];

            $body .= "<tr>";
            $body .= "<td>{$item['name']}</td>";
            $body .= "<td>" . ($item['sku'] ?? '-') . "</td>";
            $body .= "<td style='text-align:right;'>" . round($item['current_quantity']) . "</td>";
            $body .= "<td style='text-align:right;'>{$item['minimum_quantity']}</td>";
            $body .= "<td style='color:{$sevColor}; font-weight:bold;'>{$sevLabel}</td>";
            $body .= "</tr>";
        }

        $body .= "</table>";
        $body .= "<p style='margin-top:16px;'><a href='https://app.facturino.mk/admin/stock/low-stock' style='color:#4f46e5;'>Прегледај ниска залиха →</a></p>";

        try {
            Mail::html($body, function ($m) use ($user, $subject) {
                $m->to($user->email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
                $m->getSymfonyMessage()->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast');
            });

            Log::info('Low stock notification sent', [
                'user_email' => $user->email,
                'items_count' => count($items),
            ]);
        } catch (\Exception $e) {
            Log::warning("Low stock notification email failed: " . $e->getMessage());
        }
    }
}

// CLAUDE-CHECKPOINT
