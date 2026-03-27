<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PayrollEmployee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mk\Models\TravelOrder;
use Modules\Mk\Services\PerDiemService;
use Modules\Mk\Services\TravelOrderService;

class TravelOrderController extends Controller
{
    protected TravelOrderService $service;
    protected PerDiemService $perDiemService;

    public function __construct(TravelOrderService $service, PerDiemService $perDiemService)
    {
        $this->service = $service;
        $this->perDiemService = $perDiemService;
    }

    /**
     * List travel orders with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $filters = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'transport_type_category' => $request->query('transport_type_category'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'limit' => $request->query('limit', 15),
        ];

        $result = $this->service->list($companyId, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Show a single travel order.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)
            ->with(['employee', 'segments', 'expenses', 'approvedByUser', 'vehicles', 'crew', 'cargo'])
            ->where('id', $id)
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Create a new travel order.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $request->validate([
            'type' => 'required|in:domestic,foreign',
            'transport_type_category' => 'nullable|in:goods_transport,passenger_transport,business_trip',
            'transport_mode' => 'nullable|in:public,own_needs',
            'purpose' => 'required|string|max:1000',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'employee_id' => 'nullable|integer',
            'advance_amount' => 'nullable|integer|min:0',
            'cost_center_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            // Segments
            'segments' => 'required|array|min:1',
            'segments.*.from_city' => 'required|string|max:150',
            'segments.*.to_city' => 'required|string|max:150',
            'segments.*.country_code' => 'nullable|string|max:2',
            'segments.*.country_name' => 'nullable|string|max:100',
            'segments.*.departure_at' => 'required|date',
            'segments.*.arrival_at' => 'required|date',
            'segments.*.transport_type' => 'required|in:car,bus,train,plane,other',
            'segments.*.distance_km' => 'nullable|numeric|min:0',
            'segments.*.per_diem_rate' => 'nullable|numeric|min:0',
            'segments.*.per_diem_currency' => 'nullable|string|max:3',
            'segments.*.accommodation_provided' => 'nullable|boolean',
            'segments.*.meals_provided' => 'nullable|boolean',
            'segments.*.breakfast_provided' => 'nullable|boolean',
            'segments.*.lunch_provided' => 'nullable|boolean',
            'segments.*.dinner_provided' => 'nullable|boolean',
            // Expenses (expanded categories)
            'expenses' => 'nullable|array',
            'expenses.*.category' => 'required|in:transport,accommodation,meals,other,per_diem,fuel,tolls,forwarding,vehicle_maintenance,communication',
            'expenses.*.description' => 'required|string|max:255',
            'expenses.*.amount' => 'required|integer|min:0',
            'expenses.*.currency_code' => 'nullable|string|max:3',
            'expenses.*.exchange_rate' => 'nullable|numeric|min:0',
            'expenses.*.vat_amount' => 'nullable|integer|min:0',
            'expenses.*.receipt_number' => 'nullable|string|max:50',
            'expenses.*.receipt_path' => 'nullable|string|max:500',
            // Vehicles
            'vehicles' => 'nullable|array',
            'vehicles.*.vehicle_type' => 'required|in:truck,trailer,car,van',
            'vehicles.*.make' => 'nullable|string|max:100',
            'vehicles.*.model' => 'nullable|string|max:100',
            'vehicles.*.registration_plate' => 'required|string|max:20',
            'vehicles.*.capacity_tonnes' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_type' => 'nullable|in:diesel,petrol,lpg,cng',
            'vehicles.*.odometer_start' => 'nullable|integer|min:0',
            'vehicles.*.odometer_end' => 'nullable|integer|min:0',
            'vehicles.*.fuel_start_liters' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_end_liters' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_added_liters' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_norm_per_100km' => 'nullable|numeric|min:0',
            // Crew
            'crew' => 'nullable|array',
            'crew.*.name' => 'required|string|max:150',
            'crew.*.role' => 'nullable|in:driver,co_driver,crew',
            'crew.*.license_number' => 'nullable|string|max:50',
            'crew.*.license_category' => 'nullable|string|max:10',
            'crew.*.cpc_number' => 'nullable|string|max:50',
            // Cargo
            'cargo' => 'nullable|array',
            'cargo.*.cmr_number' => 'nullable|string|max:50',
            'cargo.*.sender_name' => 'nullable|string|max:200',
            'cargo.*.sender_address' => 'nullable|string',
            'cargo.*.receiver_name' => 'nullable|string|max:200',
            'cargo.*.receiver_address' => 'nullable|string',
            'cargo.*.goods_description' => 'nullable|string',
            'cargo.*.packages_count' => 'nullable|integer|min:0',
            'cargo.*.gross_weight_kg' => 'nullable|numeric|min:0',
            'cargo.*.loading_place' => 'nullable|string|max:200',
            'cargo.*.unloading_place' => 'nullable|string|max:200',
        ]);

        try {
            $order = $this->service->create($companyId, $request->all(), $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Travel order created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a travel order (draft only).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        $request->validate([
            'type' => 'nullable|in:domestic,foreign',
            'transport_type_category' => 'nullable|in:goods_transport,passenger_transport,business_trip',
            'transport_mode' => 'nullable|in:public,own_needs',
            'purpose' => 'nullable|string|max:1000',
            'departure_date' => 'nullable|date',
            'return_date' => 'nullable|date',
            'employee_id' => 'nullable|integer',
            'advance_amount' => 'nullable|integer|min:0',
            'cost_center_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            // Segments
            'segments' => 'nullable|array|min:1',
            'segments.*.from_city' => 'required|string|max:150',
            'segments.*.to_city' => 'required|string|max:150',
            'segments.*.country_code' => 'nullable|string|max:2',
            'segments.*.country_name' => 'nullable|string|max:100',
            'segments.*.departure_at' => 'required|date',
            'segments.*.arrival_at' => 'required|date',
            'segments.*.transport_type' => 'required|in:car,bus,train,plane,other',
            'segments.*.distance_km' => 'nullable|numeric|min:0',
            'segments.*.per_diem_rate' => 'nullable|numeric|min:0',
            'segments.*.per_diem_currency' => 'nullable|string|max:3',
            'segments.*.accommodation_provided' => 'nullable|boolean',
            'segments.*.meals_provided' => 'nullable|boolean',
            'segments.*.breakfast_provided' => 'nullable|boolean',
            'segments.*.lunch_provided' => 'nullable|boolean',
            'segments.*.dinner_provided' => 'nullable|boolean',
            // Expenses (expanded categories)
            'expenses' => 'nullable|array',
            'expenses.*.category' => 'required|in:transport,accommodation,meals,other,per_diem,fuel,tolls,forwarding,vehicle_maintenance,communication',
            'expenses.*.description' => 'required|string|max:255',
            'expenses.*.amount' => 'required|integer|min:0',
            'expenses.*.currency_code' => 'nullable|string|max:3',
            'expenses.*.exchange_rate' => 'nullable|numeric|min:0',
            'expenses.*.vat_amount' => 'nullable|integer|min:0',
            'expenses.*.receipt_number' => 'nullable|string|max:50',
            'expenses.*.receipt_path' => 'nullable|string|max:500',
            // Vehicles
            'vehicles' => 'nullable|array',
            'vehicles.*.vehicle_type' => 'required|in:truck,trailer,car,van',
            'vehicles.*.make' => 'nullable|string|max:100',
            'vehicles.*.model' => 'nullable|string|max:100',
            'vehicles.*.registration_plate' => 'required|string|max:20',
            'vehicles.*.capacity_tonnes' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_type' => 'nullable|in:diesel,petrol,lpg,cng',
            'vehicles.*.odometer_start' => 'nullable|integer|min:0',
            'vehicles.*.odometer_end' => 'nullable|integer|min:0',
            'vehicles.*.fuel_start_liters' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_end_liters' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_added_liters' => 'nullable|numeric|min:0',
            'vehicles.*.fuel_norm_per_100km' => 'nullable|numeric|min:0',
            // Crew
            'crew' => 'nullable|array',
            'crew.*.name' => 'required|string|max:150',
            'crew.*.role' => 'nullable|in:driver,co_driver,crew',
            'crew.*.license_number' => 'nullable|string|max:50',
            'crew.*.license_category' => 'nullable|string|max:10',
            'crew.*.cpc_number' => 'nullable|string|max:50',
            // Cargo
            'cargo' => 'nullable|array',
            'cargo.*.cmr_number' => 'nullable|string|max:50',
            'cargo.*.sender_name' => 'nullable|string|max:200',
            'cargo.*.sender_address' => 'nullable|string',
            'cargo.*.receiver_name' => 'nullable|string|max:200',
            'cargo.*.receiver_address' => 'nullable|string',
            'cargo.*.goods_description' => 'nullable|string',
            'cargo.*.packages_count' => 'nullable|integer|min:0',
            'cargo.*.gross_weight_kg' => 'nullable|numeric|min:0',
            'cargo.*.loading_place' => 'nullable|string|max:200',
            'cargo.*.unloading_place' => 'nullable|string|max:200',
        ]);

        try {
            $updated = $this->service->update($order, $request->all());

            return response()->json([
                'success' => true,
                'data' => $updated,
                'message' => 'Travel order updated successfully.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve a travel order.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $approved = $this->service->approve($order, $request->user()->id);

            return response()->json([
                'success' => true,
                'data' => $approved,
                'message' => 'Travel order approved.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Settle a travel order.
     */
    public function settle(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $settled = $this->service->settle($order);

            return response()->json([
                'success' => true,
                'data' => $settled,
                'message' => 'Travel order settled.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject a travel order.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $rejected = $this->service->reject($order);

            return response()->json([
                'success' => true,
                'data' => $rejected,
                'message' => 'Travel order rejected.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Soft delete a travel order (draft only).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $order = TravelOrder::forCompany($companyId)->where('id', $id)->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Travel order not found'], 404);
        }

        try {
            $this->service->delete($order);

            return response()->json([
                'success' => true,
                'message' => 'Travel order deleted.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download travel order as PDF.
     * Routes to goods transport template when transport_type_category is 'goods_transport'.
     */
    public function pdf(Request $request, int $id): Response
    {
        $companyId = (int) $request->header('company');

        $relationships = ['employee', 'segments', 'expenses', 'approvedByUser', 'company.address'];

        // Goods transport orders need vehicles, crew, and cargo for the PDF
        $isGoodsTransport = TravelOrder::forCompany($companyId)
            ->where('id', $id)
            ->value('transport_type_category') === 'goods_transport';

        if ($isGoodsTransport) {
            $relationships = array_merge($relationships, ['vehicles', 'crew', 'cargo']);
        }

        $order = TravelOrder::forCompany($companyId)
            ->with($relationships)
            ->where('id', $id)
            ->first();

        if (! $order) {
            abort(404, 'Travel order not found');
        }

        $employee = $order->employee;
        $company = $order->company;

        if ($isGoodsTransport) {
            $vehicles = $order->vehicles;
            $crew = $order->crew;
            $cargo = $order->cargo;
            $pdf = Pdf::loadView('app.pdf.reports.travel-order-goods', compact('order', 'employee', 'company', 'vehicles', 'crew', 'cargo'));
        } else {
            $pdf = Pdf::loadView('app.pdf.reports.travel-order', compact('order', 'employee', 'company'));
        }

        $pdf->setPaper('A4', 'portrait');

        $filename = "paten-nalog-{$order->travel_number}.pdf";

        return $pdf->download($filename);
    }

    /**
     * Lightweight employee list for travel order dropdowns.
     * No tier check — travel orders are Standard tier, payroll is Business.
     */
    public function employees(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $employees = PayrollEmployee::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'position')
            ->orderBy('first_name')
            ->get()
            ->map(fn ($e) => [
                'id' => $e->id,
                'name' => "{$e->first_name} {$e->last_name}",
                'position' => $e->position,
            ]);

        return response()->json([
            'success' => true,
            'data' => $employees,
        ]);
    }

    /**
     * Get summary statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $summary = $this->service->getSummary($companyId);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get per-diem rates for all countries (for frontend dropdown auto-fill).
     */
    public function perDiemRates(Request $request): JsonResponse
    {
        $rates = $this->perDiemService->getAllRates();

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Get per-diem rate for a specific country.
     */
    public function perDiemRateForCountry(Request $request): JsonResponse
    {
        $countryCode = $request->query('country');
        if (!$countryCode) {
            return response()->json(['success' => false, 'message' => 'country parameter required'], 400);
        }

        $rate = $this->perDiemService->getRateForCountry($countryCode);
        if (!$rate) {
            return response()->json(['success' => false, 'message' => 'Rate not found for country'], 404);
        }

        // Also include exchange rate to MKD
        $exchangeRate = $this->perDiemService->getExchangeRate($rate['currency']);

        return response()->json([
            'success' => true,
            'data' => array_merge($rate, [
                'exchange_rate' => $exchangeRate,
                'rate_mkd' => $exchangeRate ? round($rate['rate'] * $exchangeRate, 2) : null,
            ]),
        ]);
    }

    /**
     * Get all currency exchange rates (for frontend auto-fill).
     */
    public function exchangeRates(Request $request): JsonResponse
    {
        $rates = $this->perDiemService->getAllExchangeRates();

        return response()->json([
            'success' => true,
            'data' => $rates,
        ]);
    }

    /**
     * Get expense category config (for frontend: categories with GL codes and labels).
     */
    public function expenseCategories(Request $request): JsonResponse
    {
        $categories = config('travel-expenses.categories', []);
        $locale = $request->query('locale', 'mk');

        $result = [];
        foreach ($categories as $key => $cfg) {
            $labelKey = "label_{$locale}";
            $result[] = [
                'value' => $key,
                'label' => $cfg[$labelKey] ?? $cfg['label_en'] ?? $key,
                'gl_code' => $cfg['gl_code'],
                'gl_name' => $cfg['gl_name'],
                'vat_deductible' => $cfg['vat_deductible'],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}

// CLAUDE-CHECKPOINT
