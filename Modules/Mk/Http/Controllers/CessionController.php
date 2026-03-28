<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Mk\Models\Cession;

class CessionController extends Controller
{
    /**
     * List cessions for the current company with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $query = Cession::where('company_id', $companyId)
            ->orderBy(
                $request->get('orderByField', 'cession_date'),
                $request->get('orderBy', 'desc')
            );

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('cession_number', 'LIKE', '%' . $search . '%')
                  ->orWhere('cedent_name', 'LIKE', '%' . $search . '%')
                  ->orWhere('cessionary_name', 'LIKE', '%' . $search . '%')
                  ->orWhere('debtor_name', 'LIKE', '%' . $search . '%');
            });
        }

        $limit = $request->query('limit', 15);
        if ($limit === 'all') {
            return response()->json([
                'success' => true,
                'data' => $query->get(),
            ]);
        }

        $cessions = $query->paginate((int) $limit);

        return response()->json([
            'success' => true,
            'data' => $cessions->items(),
            'meta' => [
                'current_page' => $cessions->currentPage(),
                'last_page' => $cessions->lastPage(),
                'per_page' => $cessions->perPage(),
                'total' => $cessions->total(),
            ],
        ]);
    }

    /**
     * Show a single cession.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $cession = Cession::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$cession) {
            return response()->json(['success' => false, 'message' => 'Cession not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $cession,
        ]);
    }

    /**
     * Create a new draft cession.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cession_date' => 'required|date',
            'role' => 'required|in:cedent,cessionary,debtor',
            'cedent_name' => 'required|string|max:255',
            'cessionary_name' => 'required|string|max:255',
            'debtor_name' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'cedent_vat_id' => 'nullable|string|max:50',
            'cedent_tax_id' => 'nullable|string|max:50',
            'cessionary_vat_id' => 'nullable|string|max:50',
            'cessionary_tax_id' => 'nullable|string|max:50',
            'debtor_vat_id' => 'nullable|string|max:50',
            'debtor_tax_id' => 'nullable|string|max:50',
            'original_document_type' => 'nullable|string|max:50',
            'original_document_id' => 'nullable|integer',
            'original_document_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:2000',
        ]);

        $companyId = (int) $request->header('company');

        // Generate sequential number
        $lastNumber = Cession::where('company_id', $companyId)
            ->whereYear('cession_date', now()->year)
            ->count();
        $number = 'ЦЕС-' . now()->year . '-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        $cession = Cession::create(array_merge(
            $request->only([
                'cession_date', 'role', 'cedent_name', 'cedent_vat_id', 'cedent_tax_id',
                'cessionary_name', 'cessionary_vat_id', 'cessionary_tax_id',
                'debtor_name', 'debtor_vat_id', 'debtor_tax_id',
                'amount', 'original_document_type', 'original_document_id',
                'original_document_number', 'notes',
            ]),
            [
                'company_id' => $companyId,
                'cession_number' => $number,
                'creator_id' => Auth::id(),
                'status' => 'draft',
            ]
        ));

        return response()->json([
            'success' => true,
            'data' => $cession,
            'message' => 'Cession created successfully',
        ], 201);
    }

    /**
     * Confirm a draft cession.
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $cession = Cession::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$cession) {
            return response()->json(['success' => false, 'message' => 'Cession not found'], 404);
        }

        if ($cession->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft cessions can be confirmed',
            ], 422);
        }

        $cession->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $cession->fresh(),
            'message' => 'Cession confirmed successfully',
        ]);
    }

    /**
     * Cancel a cession.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $cession = Cession::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$cession) {
            return response()->json(['success' => false, 'message' => 'Cession not found'], 404);
        }

        if ($cession->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cession is already cancelled',
            ], 422);
        }

        $cession->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'data' => $cession->fresh(),
            'message' => 'Cession cancelled',
        ]);
    }

    /**
     * Generate and return PDF for a cession.
     */
    public function pdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');

        $cession = Cession::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$cession) {
            return response()->json(['success' => false, 'message' => 'Cession not found'], 404);
        }

        $company = Company::find($companyId);

        $amountWords = '';
        try {
            $service = app(\Modules\Mk\Services\AmountToWordsService::class);
            $amountWords = $service->convert($cession->amount, 'MKD');
        } catch (\Exception $e) {
            $amountWords = '';
        }

        $pdf = \PDF::loadView('app.pdf.reports.cesija', [
            'cession' => $cession,
            'company' => $company,
            'amount_words' => $amountWords,
            'amount_formatted' => number_format($cession->amount / 100, 2, '.', ','),
        ]);
        $pdf->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        $filename = sprintf(
            'cesija_%s_%s.pdf',
            $cession->cession_number,
            $cession->cession_date->format('Y-m-d')
        );

        return $pdf->stream($filename);
    }
}
// CLAUDE-CHECKPOINT
