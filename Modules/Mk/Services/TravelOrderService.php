<?php

namespace Modules\Mk\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\TravelOrder;
use Modules\Mk\Models\TravelSegment;
use Modules\Mk\Models\TravelExpense;
use Modules\Mk\Models\CurrencyExchangeRate;

class TravelOrderService
{
    protected TravelOrderGLService $glService;
    protected PerDiemService $perDiemService;

    public function __construct(TravelOrderGLService $glService, PerDiemService $perDiemService)
    {
        $this->glService = $glService;
        $this->perDiemService = $perDiemService;
    }

    /**
     * Mileage rate: 30% of fuel price per km (~15 MKD/km).
     * Stored in cents: 15 * 100 = 1500
     */
    private const MILEAGE_RATE_PER_KM = 1500; // 15 MKD/km in cents

    /**
     * List travel orders with filters and pagination.
     */
    public function list(int $companyId, array $filters = []): array
    {
        $query = TravelOrder::forCompany($companyId)
            ->with(['employee:id,first_name,last_name', 'segments', 'expenses', 'vehicles', 'crew'])
            ->orderBy('departure_date', 'desc');

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['transport_type_category'])) {
            $query->where('transport_type_category', $filters['transport_type_category']);
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
     * Create a travel order with segments, expenses, vehicles, crew, and cargo.
     */
    public function create(int $companyId, array $data, int $userId): TravelOrder
    {
        return DB::transaction(function () use ($companyId, $data, $userId) {
            $order = TravelOrder::create([
                'company_id' => $companyId,
                'employee_id' => $data['employee_id'] ?? null,
                'type' => $data['type'],
                'transport_type_category' => $data['transport_type_category'] ?? 'business_trip',
                'transport_mode' => $data['transport_mode'] ?? null,
                'purpose' => $data['purpose'],
                'departure_date' => $data['departure_date'],
                'return_date' => $data['return_date'],
                'status' => 'draft',
                'advance_amount' => (int) ($data['advance_amount'] ?? 0),
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncSegments($order, $data['segments'] ?? []);
            $this->syncExpenses($order, $data['expenses'] ?? []);
            $this->syncVehicles($order, $data['vehicles'] ?? []);
            $this->syncCrew($order, $data['crew'] ?? []);
            $this->syncCargo($order, $data['cargo'] ?? []);

            $this->recalculateTotals($order);

            return $order->fresh(['segments', 'expenses', 'employee', 'vehicles', 'crew', 'cargo']);
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
                'transport_type_category' => $data['transport_type_category'] ?? $order->transport_type_category,
                'transport_mode' => $data['transport_mode'] ?? $order->transport_mode,
                'purpose' => $data['purpose'] ?? $order->purpose,
                'departure_date' => $data['departure_date'] ?? $order->departure_date,
                'return_date' => $data['return_date'] ?? $order->return_date,
                'advance_amount' => isset($data['advance_amount']) ? (int) $data['advance_amount'] : $order->advance_amount,
                'cost_center_id' => $data['cost_center_id'] ?? $order->cost_center_id,
                'notes' => $data['notes'] ?? $order->notes,
            ]);

            if (isset($data['segments'])) {
                $order->segments()->delete();
                $this->syncSegments($order, $data['segments']);
            }

            if (isset($data['expenses'])) {
                $order->expenses()->delete();
                $this->syncExpenses($order, $data['expenses']);
            }

            if (isset($data['vehicles'])) {
                $order->vehicles()->delete();
                $this->syncVehicles($order, $data['vehicles']);
            }

            if (isset($data['crew'])) {
                $order->crew()->delete();
                $this->syncCrew($order, $data['crew']);
            }

            if (isset($data['cargo'])) {
                $order->cargo()->delete();
                $this->syncCargo($order, $data['cargo']);
            }

            $this->recalculateTotals($order);

            return $order->fresh(['segments', 'expenses', 'employee', 'vehicles', 'crew', 'cargo']);
        });
    }

    /**
     * Approve a travel order.
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

        return $order->fresh(['segments', 'expenses', 'employee', 'vehicles', 'crew', 'cargo']);
    }

    /**
     * Settle a travel order. Calculates reimbursement and posts GL entries.
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

        // Post itemized journal entries to IFRS ledger
        try {
            $this->glService->postSettlement($order);
        } catch (\Exception $e) {
            Log::warning('TravelOrder: GL posting failed but settlement succeeded', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $order->fresh(['segments', 'expenses', 'employee', 'vehicles', 'crew', 'cargo']);
    }

    /**
     * Reject a travel order.
     */
    public function reject(TravelOrder $order): TravelOrder
    {
        if (!in_array($order->status, ['draft', 'pending_approval'])) {
            throw new \InvalidArgumentException('Only draft or pending approval orders can be rejected.');
        }

        $order->update(['status' => 'rejected']);

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
     * MK rules:
     * - Full day (>12h or per 24h): 100% of daily rate
     * - Half day (8-12h): 50% of daily rate
     * - Under 8h: no per-diem
     * - Meal reductions: breakfast -10%, lunch -30%, dinner -30%
     */
    public function calculatePerDiem(TravelOrder $order): int
    {
        $totalPerDiem = 0;
        $order->load('segments');

        foreach ($order->segments as $segment) {
            $departure = Carbon::parse($segment->departure_at);
            $arrival = Carbon::parse($segment->arrival_at);
            $hours = $departure->floatDiffInHours($arrival);

            $countryCode = $segment->country_code ?? 'MK';
            if ($order->type === 'domestic') {
                $countryCode = 'MK';
            }

            $meals = [
                'breakfast' => (bool) $segment->breakfast_provided,
                'lunch' => (bool) $segment->lunch_provided,
                'dinner' => (bool) $segment->dinner_provided,
            ];

            // Backward compat: if old meals_provided is true but granular fields are all false
            if ($segment->meals_provided && !$meals['breakfast'] && !$meals['lunch'] && !$meals['dinner']) {
                $meals = ['breakfast' => true, 'lunch' => true, 'dinner' => false];
            }

            $date = $departure->toDateString();
            $calc = $this->perDiemService->calculatePerDiem(
                $hours,
                $countryCode,
                $meals,
                $order->company_id,
                $date
            );

            // If foreign segment has a custom per_diem_rate, use it instead of lookup
            if ($order->type === 'foreign' && $segment->per_diem_rate > 0) {
                $customRate = (float) $segment->per_diem_rate;
                $currency = $segment->per_diem_currency ?: 'EUR';
                $exchangeRate = $this->perDiemService->getExchangeRate($currency, $date) ?? 61.5395;

                $days = $calc['days'];
                $baseAmount = $customRate * $days;

                // Apply meal reductions
                $reductionPct = $calc['reductions'];
                $finalAmount = max(0, $baseAmount - ($baseAmount * $reductionPct));
                $amountMkd = round($finalAmount * $exchangeRate, 2);

                $calc = [
                    'amount' => round($finalAmount, 2),
                    'amount_mkd' => $amountMkd,
                    'currency' => $currency,
                    'days' => $days,
                    'rate' => $customRate,
                    'reductions' => $reductionPct,
                ];
            }

            // Convert to cents for storage
            $amountMkdCents = (int) round($calc['amount_mkd'] * 100);
            $amountCents = (int) round($calc['amount'] * 100);

            $segment->update([
                'per_diem_rate' => $calc['rate'],
                'per_diem_days' => $calc['days'],
                'per_diem_amount' => $amountCents,
                'per_diem_currency' => $calc['currency'],
                'per_diem_amount_mkd' => $amountMkdCents,
            ]);

            // For grand total, always use MKD amount
            $totalPerDiem += $amountMkdCents;
        }

        return $totalPerDiem;
    }

    /**
     * Calculate mileage cost for car transport segments.
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
     * Calculate total km from vehicle odometers or segment distances.
     */
    public function calculateTotalKm(TravelOrder $order): int
    {
        $order->load('vehicles', 'segments');

        // Prefer odometer readings from vehicles
        $odometerKm = 0;
        $hasOdometer = false;
        foreach ($order->vehicles as $vehicle) {
            if ($vehicle->odometer_start !== null && $vehicle->odometer_end !== null) {
                $odometerKm += max(0, $vehicle->odometer_end - $vehicle->odometer_start);
                $hasOdometer = true;
            }
        }

        if ($hasOdometer) {
            return $odometerKm;
        }

        // Fallback: sum segment distances
        return (int) $order->segments->sum('distance_km');
    }

    /**
     * Calculate fuel consumption from vehicle data.
     */
    public function calculateFuelConsumption(TravelOrder $order): ?float
    {
        $order->load('vehicles');

        $totalConsumed = 0;
        $hasData = false;

        foreach ($order->vehicles as $vehicle) {
            $consumed = $vehicle->fuel_consumed;
            if ($consumed !== null) {
                $totalConsumed += $consumed;
                $hasData = true;
            }
        }

        return $hasData ? round($totalConsumed, 2) : null;
    }

    /**
     * Recalculate all totals on the order.
     */
    protected function recalculateTotals(TravelOrder $order): void
    {
        $totalPerDiem = $this->calculatePerDiem($order);
        $totalMileage = $this->calculateMileage($order);
        $totalKm = $this->calculateTotalKm($order);
        $totalFuelConsumed = $this->calculateFuelConsumption($order);

        $order->load('expenses');

        // Calculate expense totals by category (in MKD cents)
        $totalExpenses = 0;
        $totalFuelCost = 0;
        $totalTollCost = 0;
        $totalForwardingCost = 0;

        foreach ($order->expenses as $expense) {
            // Use amount_mkd if available (foreign currency converted), else amount
            $amountCents = $expense->amount_mkd ?? $expense->amount;
            $totalExpenses += $amountCents;

            switch ($expense->category) {
                case 'fuel':
                    $totalFuelCost += $amountCents;
                    break;
                case 'tolls':
                    $totalTollCost += $amountCents;
                    break;
                case 'forwarding':
                    $totalForwardingCost += $amountCents;
                    break;
            }
        }

        $grandTotal = $totalPerDiem + $totalMileage + $totalExpenses;

        $order->update([
            'total_per_diem' => $totalPerDiem,
            'total_mileage_cost' => $totalMileage,
            'total_expenses' => $totalExpenses,
            'total_km' => $totalKm,
            'total_fuel_consumed' => $totalFuelConsumed,
            'total_fuel_cost' => $totalFuelCost,
            'total_toll_cost' => $totalTollCost,
            'total_forwarding_cost' => $totalForwardingCost,
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
            'total_fuel_cost' => (clone $orders)->whereIn('status', ['approved', 'settled'])->sum('total_fuel_cost'),
            'total_grand' => (clone $orders)->whereIn('status', ['approved', 'settled'])->sum('grand_total'),
        ];
    }

    // ─── Private sync helpers ───

    private function syncSegments(TravelOrder $order, array $segments): void
    {
        foreach ($segments as $seg) {
            $order->segments()->create([
                'from_city' => $seg['from_city'],
                'to_city' => $seg['to_city'],
                'country_code' => $seg['country_code'] ?? null,
                'country_name' => $seg['country_name'] ?? null,
                'departure_at' => $seg['departure_at'],
                'arrival_at' => $seg['arrival_at'],
                'transport_type' => $seg['transport_type'] ?? 'car',
                'distance_km' => $seg['distance_km'] ?? null,
                'per_diem_rate' => $seg['per_diem_rate'] ?? null,
                'per_diem_currency' => $seg['per_diem_currency'] ?? null,
                'accommodation_provided' => $seg['accommodation_provided'] ?? false,
                'meals_provided' => $seg['meals_provided'] ?? false,
                'breakfast_provided' => $seg['breakfast_provided'] ?? false,
                'lunch_provided' => $seg['lunch_provided'] ?? false,
                'dinner_provided' => $seg['dinner_provided'] ?? false,
            ]);
        }
    }

    private function syncExpenses(TravelOrder $order, array $expenses): void
    {
        $categoryGlMap = config('travel-expenses.categories', []);

        foreach ($expenses as $exp) {
            $category = $exp['category'] ?? 'other';
            $glCode = $categoryGlMap[$category]['gl_code'] ?? '449';
            $currencyCode = $exp['currency_code'] ?? 'MKD';
            $amount = (int) ($exp['amount'] ?? 0);

            // Currency conversion
            $exchangeRate = null;
            $amountMkd = $amount; // default: same as amount (MKD)

            if (strtoupper($currencyCode) !== 'MKD') {
                $rate = CurrencyExchangeRate::latestRate($currencyCode);
                $exchangeRate = $rate ? (float) $rate->rate_to_mkd : null;
                if ($exchangeRate) {
                    $amountMkd = (int) round(($amount / 100) * $exchangeRate * 100);
                }
            }

            $order->expenses()->create([
                'category' => $category,
                'description' => $exp['description'] ?? '',
                'amount' => $amount,
                'currency_code' => $currencyCode,
                'gl_account_code' => $glCode,
                'exchange_rate' => $exchangeRate ?? ($exp['exchange_rate'] ?? null),
                'amount_mkd' => $amountMkd,
                'vat_amount' => $exp['vat_amount'] ?? null,
                'receipt_number' => $exp['receipt_number'] ?? null,
                'receipt_path' => $exp['receipt_path'] ?? null,
            ]);
        }
    }

    private function syncVehicles(TravelOrder $order, array $vehicles): void
    {
        foreach ($vehicles as $v) {
            $order->vehicles()->create([
                'vehicle_type' => $v['vehicle_type'] ?? 'truck',
                'make' => $v['make'] ?? null,
                'model' => $v['model'] ?? null,
                'registration_plate' => $v['registration_plate'],
                'capacity_tonnes' => $v['capacity_tonnes'] ?? null,
                'fuel_type' => $v['fuel_type'] ?? 'diesel',
                'odometer_start' => $v['odometer_start'] ?? null,
                'odometer_end' => $v['odometer_end'] ?? null,
                'fuel_start_liters' => $v['fuel_start_liters'] ?? null,
                'fuel_end_liters' => $v['fuel_end_liters'] ?? null,
                'fuel_added_liters' => $v['fuel_added_liters'] ?? null,
                'fuel_norm_per_100km' => $v['fuel_norm_per_100km'] ?? config('travel-expenses.fuel_norms.' . ($v['vehicle_type'] ?? 'truck')),
            ]);
        }
    }

    private function syncCrew(TravelOrder $order, array $crew): void
    {
        foreach ($crew as $c) {
            $order->crew()->create([
                'name' => $c['name'],
                'role' => $c['role'] ?? 'driver',
                'license_number' => $c['license_number'] ?? null,
                'license_category' => $c['license_category'] ?? null,
                'cpc_number' => $c['cpc_number'] ?? null,
            ]);
        }
    }

    private function syncCargo(TravelOrder $order, array $cargo): void
    {
        foreach ($cargo as $c) {
            $order->cargo()->create([
                'travel_segment_id' => $c['travel_segment_id'] ?? null,
                'cmr_number' => $c['cmr_number'] ?? null,
                'sender_name' => $c['sender_name'] ?? null,
                'sender_address' => $c['sender_address'] ?? null,
                'receiver_name' => $c['receiver_name'] ?? null,
                'receiver_address' => $c['receiver_address'] ?? null,
                'goods_description' => $c['goods_description'] ?? null,
                'packages_count' => $c['packages_count'] ?? null,
                'gross_weight_kg' => $c['gross_weight_kg'] ?? null,
                'loading_place' => $c['loading_place'] ?? null,
                'unloading_place' => $c['unloading_place'] ?? null,
            ]);
        }
    }
}

// CLAUDE-CHECKPOINT
