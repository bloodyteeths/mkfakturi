<?php

namespace Modules\Mk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Models\TravelOrder;
use Modules\Mk\Models\TravelSegment;
use Modules\Mk\Models\TravelExpense;

class TravelOrderService
{
    /**
     * MK Legal Constants for domestic per-diem.
     * Base salary: 33,370 MKD, rate: 8% = ~2,670 MKD/day
     * Stored in cents: 2670 * 100 = 267000
     */
    private const DOMESTIC_PER_DIEM_BASE = 3337000; // 33,370 MKD in cents
    private const DOMESTIC_PER_DIEM_RATE = 0.08;
    private const DOMESTIC_PER_DIEM_FULL = 267000; // ~2,670 MKD in cents (full day)
    private const DOMESTIC_PER_DIEM_HALF = 133500; // ~1,335 MKD in cents (half day)

    /**
     * Mileage rate: 30% of fuel price per km (~15 MKD/km)
     * Stored in cents: 15 * 100 = 1500
     */
    private const MILEAGE_RATE_PER_KM = 1500; // 15 MKD/km in cents

    /**
     * Meal reduction when accommodation is provided: 50%.
     */
    private const MEAL_REDUCTION_RATE = 0.50;

    /**
     * List travel orders with filters and pagination.
     */
    public function list(int $companyId, array $filters = []): array
    {
        $query = TravelOrder::forCompany($companyId)
            ->with(['employee:id,first_name,last_name', 'segments', 'expenses'])
            ->orderBy('departure_date', 'desc');

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('departure_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('return_date', '<=', $filters['date_to']);
        }

        $limit = $filters['limit'] ?? 15;

        if ($limit === 'all') {
            return [
                'data' => $query->get(),
                'meta' => null,
            ];
        }

        $paginated = $query->paginate((int) $limit);

        return [
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ];
    }

    /**
     * Create a travel order with segments and expenses.
     */
    public function create(int $companyId, array $data, int $userId): TravelOrder
    {
        return DB::transaction(function () use ($companyId, $data, $userId) {
            $order = TravelOrder::create([
                'company_id' => $companyId,
                'employee_id' => $data['employee_id'] ?? null,
                'type' => $data['type'],
                'purpose' => $data['purpose'],
                'departure_date' => $data['departure_date'],
                'return_date' => $data['return_date'],
                'status' => 'draft',
                'advance_amount' => (int) ($data['advance_amount'] ?? 0),
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create segments
            if (!empty($data['segments'])) {
                foreach ($data['segments'] as $segmentData) {
                    $order->segments()->create([
                        'from_city' => $segmentData['from_city'],
                        'to_city' => $segmentData['to_city'],
                        'country_code' => $segmentData['country_code'] ?? null,
                        'departure_at' => $segmentData['departure_at'],
                        'arrival_at' => $segmentData['arrival_at'],
                        'transport_type' => $segmentData['transport_type'] ?? 'car',
                        'distance_km' => $segmentData['distance_km'] ?? null,
                        'accommodation_provided' => $segmentData['accommodation_provided'] ?? false,
                        'meals_provided' => $segmentData['meals_provided'] ?? false,
                    ]);
                }
            }

            // Create expenses
            if (!empty($data['expenses'])) {
                foreach ($data['expenses'] as $expenseData) {
                    $order->expenses()->create([
                        'category' => $expenseData['category'],
                        'description' => $expenseData['description'],
                        'amount' => (int) $expenseData['amount'],
                        'currency_code' => $expenseData['currency_code'] ?? 'MKD',
                        'receipt_path' => $expenseData['receipt_path'] ?? null,
                    ]);
                }
            }

            // Calculate per-diem and totals
            $this->recalculateTotals($order);

            return $order->fresh(['segments', 'expenses', 'employee']);
        });
    }

    /**
     * Update a travel order (draft only).
     */
    public function update(TravelOrder $order, array $data): TravelOrder
    {
        if ($order->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft travel orders can be updated.');
        }

        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'employee_id' => $data['employee_id'] ?? $order->employee_id,
                'type' => $data['type'] ?? $order->type,
                'purpose' => $data['purpose'] ?? $order->purpose,
                'departure_date' => $data['departure_date'] ?? $order->departure_date,
                'return_date' => $data['return_date'] ?? $order->return_date,
                'advance_amount' => isset($data['advance_amount']) ? (int) $data['advance_amount'] : $order->advance_amount,
                'cost_center_id' => $data['cost_center_id'] ?? $order->cost_center_id,
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            // Replace segments
            if (isset($data['segments'])) {
                $order->segments()->delete();
                foreach ($data['segments'] as $segmentData) {
                    $order->segments()->create([
                        'from_city' => $segmentData['from_city'],
                        'to_city' => $segmentData['to_city'],
                        'country_code' => $segmentData['country_code'] ?? null,
                        'departure_at' => $segmentData['departure_at'],
                        'arrival_at' => $segmentData['arrival_at'],
                        'transport_type' => $segmentData['transport_type'] ?? 'car',
                        'distance_km' => $segmentData['distance_km'] ?? null,
                        'accommodation_provided' => $segmentData['accommodation_provided'] ?? false,
                        'meals_provided' => $segmentData['meals_provided'] ?? false,
                    ]);
                }
            }

            // Replace expenses
            if (isset($data['expenses'])) {
                $order->expenses()->delete();
                foreach ($data['expenses'] as $expenseData) {
                    $order->expenses()->create([
                        'category' => $expenseData['category'],
                        'description' => $expenseData['description'],
                        'amount' => (int) $expenseData['amount'],
                        'currency_code' => $expenseData['currency_code'] ?? 'MKD',
                        'receipt_path' => $expenseData['receipt_path'] ?? null,
                    ]);
                }
            }

            $this->recalculateTotals($order);

            return $order->fresh(['segments', 'expenses', 'employee']);
        });
    }

    /**
     * Approve a travel order. Calculates totals and sets status.
     */
    public function approve(TravelOrder $order, int $approvedBy): TravelOrder
    {
        if (!in_array($order->status, ['draft', 'pending_approval'])) {
            throw new \InvalidArgumentException('Only draft or pending approval orders can be approved.');
        }

        $this->recalculateTotals($order);

        $order->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
        ]);

        return $order->fresh(['segments', 'expenses', 'employee']);
    }

    /**
     * Settle a travel order. Calculates reimbursement.
     */
    public function settle(TravelOrder $order): TravelOrder
    {
        if ($order->status !== 'approved') {
            throw new \InvalidArgumentException('Only approved travel orders can be settled.');
        }

        $this->recalculateTotals($order);

        $reimbursement = $order->grand_total - $order->advance_amount;

        $order->update([
            'status' => 'settled',
            'reimbursement_amount' => $reimbursement,
        ]);

        return $order->fresh(['segments', 'expenses', 'employee']);
    }

    /**
     * Reject a travel order.
     */
    public function reject(TravelOrder $order): TravelOrder
    {
        if (!in_array($order->status, ['draft', 'pending_approval'])) {
            throw new \InvalidArgumentException('Only draft or pending approval orders can be rejected.');
        }

        $order->update([
            'status' => 'rejected',
        ]);

        return $order->fresh(['segments', 'expenses', 'employee']);
    }

    /**
     * Soft delete a travel order (draft only).
     */
    public function delete(TravelOrder $order): bool
    {
        if ($order->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft travel orders can be deleted.');
        }

        return $order->delete();
    }

    /**
     * Calculate per-diem amounts for all segments.
     *
     * MK domestic rules:
     * - Half day (6-12h): 50% of daily rate
     * - Full day (>12h or overnight): 100% of daily rate
     * - 50% meal reduction if meals are provided
     */
    public function calculatePerDiem(TravelOrder $order): int
    {
        $totalPerDiem = 0;

        $order->load('segments');

        foreach ($order->segments as $segment) {
            $departure = Carbon::parse($segment->departure_at);
            $arrival = Carbon::parse($segment->arrival_at);
            $hours = $departure->diffInHours($arrival);

            // Calculate number of days
            if ($hours < 6) {
                $days = 0;
                $dailyRate = 0;
            } elseif ($hours <= 12) {
                // Half day
                $days = 0.5;
                $dailyRate = self::DOMESTIC_PER_DIEM_HALF;
            } else {
                // Full day(s)
                $days = max(1, ceil($hours / 24));
                $dailyRate = self::DOMESTIC_PER_DIEM_FULL;
            }

            if ($order->type === 'foreign' && $segment->per_diem_rate) {
                // Foreign per-diem: use country-based rate (stored as decimal, convert to cents)
                $dailyRate = (int) ($segment->per_diem_rate * 100);
            }

            $segmentPerDiem = (int) ($dailyRate * $days);

            // 50% meal reduction if meals are provided
            if ($segment->meals_provided && $segmentPerDiem > 0) {
                $segmentPerDiem = (int) ($segmentPerDiem * self::MEAL_REDUCTION_RATE);
            }

            // Update segment
            $segment->update([
                'per_diem_rate' => $dailyRate / 100,
                'per_diem_days' => $days,
                'per_diem_amount' => $segmentPerDiem,
            ]);

            $totalPerDiem += $segmentPerDiem;
        }

        return $totalPerDiem;
    }

    /**
     * Calculate mileage cost for car transport segments.
     * Rate: 30% of fuel price per km (~15 MKD/km)
     */
    public function calculateMileage(TravelOrder $order): int
    {
        $totalMileage = 0;

        $order->load('segments');

        foreach ($order->segments as $segment) {
            if ($segment->transport_type === 'car' && $segment->distance_km > 0) {
                $cost = (int) ($segment->distance_km * self::MILEAGE_RATE_PER_KM);
                $totalMileage += $cost;
            }
        }

        return $totalMileage;
    }

    /**
     * Recalculate all totals on the order.
     */
    protected function recalculateTotals(TravelOrder $order): void
    {
        $totalPerDiem = $this->calculatePerDiem($order);
        $totalMileage = $this->calculateMileage($order);

        $order->load('expenses');
        $totalExpenses = $order->expenses->sum('amount');

        $grandTotal = $totalPerDiem + $totalMileage + $totalExpenses;

        $order->update([
            'total_per_diem' => $totalPerDiem,
            'total_mileage_cost' => $totalMileage,
            'total_expenses' => $totalExpenses,
            'grand_total' => $grandTotal,
        ]);
    }

    /**
     * Get summary statistics for a company's travel orders.
     */
    public function getSummary(int $companyId): array
    {
        $orders = TravelOrder::forCompany($companyId);

        return [
            'total_orders' => (clone $orders)->count(),
            'pending_approval' => (clone $orders)->where('status', 'pending_approval')->count(),
            'total_per_diem' => (clone $orders)->whereIn('status', ['approved', 'settled'])->sum('total_per_diem'),
            'total_expenses' => (clone $orders)->whereIn('status', ['approved', 'settled'])->sum('total_expenses'),
            'total_grand' => (clone $orders)->whereIn('status', ['approved', 'settled'])->sum('grand_total'),
        ];
    }
}

// CLAUDE-CHECKPOINT
