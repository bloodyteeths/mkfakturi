<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Mk\Models\TravelOrder;
use Modules\Mk\Services\TravelOrderService;

class PartnerTravelOrderController extends Controller
{
    protected TravelOrderService $service;

    public function __construct(TravelOrderService $service)
    {
        $this->service = $service;
    }

    /**
     * List travel orders for a partner's client company.
     */
    public function index(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $filters = [
            'status' => $request->query('status'),
            'type' => $request->query('type'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'limit' => $request->query('limit', 15),
        ];

        $result = $this->service->list($company, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Show a single travel order.
     */
    public function show(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $order = TravelOrder::forCompany($company)
            ->with(['employee', 'segments', 'expenses', 'approvedByUser'])
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
     * Create a new travel order for a partner's client company.
     */
    public function store(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $request->validate([
            'type' => 'required|in:domestic,foreign',
            'purpose' => 'required|string|max:1000',
            'departure_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:departure_date',
            'employee_id' => 'nullable|integer',
            'advance_amount' => 'nullable|integer|min:0',
            'cost_center_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:2000',
            'segments' => 'required|array|min:1',
            'segments.*.from_city' => 'required|string|max:150',
            'segments.*.to_city' => 'required|string|max:150',
            'segments.*.country_code' => 'nullable|string|max:2',
            'segments.*.departure_at' => 'required|date',
            'segments.*.arrival_at' => 'required|date',
            'segments.*.transport_type' => 'required|in:car,bus,train,plane,other',
            'segments.*.distance_km' => 'nullable|numeric|min:0',
            'segments.*.accommodation_provided' => 'nullable|boolean',
            'segments.*.meals_provided' => 'nullable|boolean',
            'expenses' => 'nullable|array',
            'expenses.*.category' => 'required|in:transport,accommodation,meals,other',
            'expenses.*.description' => 'required|string|max:255',
            'expenses.*.amount' => 'required|integer|min:0',
            'expenses.*.currency_code' => 'nullable|string|max:3',
            'expenses.*.receipt_path' => 'nullable|string|max:500',
        ]);

        try {
            $order = $this->service->create($company, $request->all(), $request->user()->id);

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
     * Approve a travel order.
     */
    public function approve(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $order = TravelOrder::forCompany($company)->where('id', $id)->first();

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
    public function settle(Request $request, int $company, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            return response()->json(['success' => false, 'message' => 'Partner not found'], 404);
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            return response()->json(['success' => false, 'message' => 'No access to this company'], 403);
        }

        $order = TravelOrder::forCompany($company)->where('id', $id)->first();

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
     * Download travel order PDF for a partner's client company.
     */
    public function pdf(Request $request, int $company, int $id): Response
    {
        $partner = $this->getPartnerFromRequest($request);
        if (! $partner) {
            abort(404, 'Partner not found');
        }
        if (! $this->hasCompanyAccess($partner, $company)) {
            abort(403, 'Access denied');
        }

        $order = TravelOrder::forCompany($company)
            ->with(['employee', 'segments', 'expenses', 'approvedByUser', 'company.address'])
            ->where('id', $id)
            ->first();

        if (! $order) {
            abort(404, 'Travel order not found');
        }

        $employee = $order->employee;
        $companyModel = $order->company;

        $pdf = Pdf::loadView('app.pdf.reports.travel-order', [
            'order' => $order,
            'employee' => $employee,
            'company' => $companyModel,
        ]);
        $pdf->setPaper('A4', 'portrait');

        $filename = "paten-nalog-{$order->travel_number}.pdf";

        return $pdf->download($filename);
    }

    // ---- Partner access helpers ----

    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;

            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
