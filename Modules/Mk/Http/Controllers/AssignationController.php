<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Mk\Models\Assignation;

class AssignationController extends Controller
{
    /**
     * List assignations for the current company with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $query = Assignation::where('company_id', $companyId)
            ->orderBy(
                $request->get('orderByField', 'assignation_date'),
                $request->get('orderBy', 'desc')
            );

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('assignation_number', 'LIKE', '%' . $search . '%')
                  ->orWhere('assignor_name', 'LIKE', '%' . $search . '%')
                  ->orWhere('assignee_name', 'LIKE', '%' . $search . '%')
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

        $items = $query->paginate((int) $limit);

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Show a single assignation.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $assignation = Assignation::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$assignation) {
            return response()->json(['success' => false, 'message' => 'Assignation not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $assignation,
        ]);
    }

    /**
     * Create a new draft assignation.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'assignation_date' => 'required|date',
            'role' => 'required|in:assignor,assignee,debtor',
            'assignor_name' => 'required|string|max:255',
            'assignee_name' => 'required|string|max:255',
            'debtor_name' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'assignor_vat_id' => 'nullable|string|max:50',
            'assignor_tax_id' => 'nullable|string|max:50',
            'assignee_vat_id' => 'nullable|string|max:50',
            'assignee_tax_id' => 'nullable|string|max:50',
            'debtor_vat_id' => 'nullable|string|max:50',
            'debtor_tax_id' => 'nullable|string|max:50',
            'assignor_to_assignee_doc' => 'nullable|string|max:255',
            'assignor_to_debtor_doc' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $companyId = (int) $request->header('company');

        $lastNumber = Assignation::where('company_id', $companyId)
            ->whereYear('assignation_date', now()->year)
            ->count();
        $number = 'АСГ-' . now()->year . '-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        $assignation = Assignation::create(array_merge(
            $request->only([
                'assignation_date', 'role', 'assignor_name', 'assignor_vat_id', 'assignor_tax_id',
                'assignee_name', 'assignee_vat_id', 'assignee_tax_id',
                'debtor_name', 'debtor_vat_id', 'debtor_tax_id',
                'amount', 'assignor_to_assignee_doc', 'assignor_to_debtor_doc', 'notes',
            ]),
            [
                'company_id' => $companyId,
                'assignation_number' => $number,
                'creator_id' => Auth::id(),
                'status' => 'draft',
            ]
        ));

        return response()->json([
            'success' => true,
            'data' => $assignation,
            'message' => 'Assignation created successfully',
        ], 201);
    }

    /**
     * Confirm a draft assignation.
     */
    public function confirm(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $assignation = Assignation::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$assignation) {
            return response()->json(['success' => false, 'message' => 'Assignation not found'], 404);
        }

        if ($assignation->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft assignations can be confirmed',
            ], 422);
        }

        $assignation->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $assignation->fresh(),
            'message' => 'Assignation confirmed successfully',
        ]);
    }

    /**
     * Cancel an assignation.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $assignation = Assignation::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$assignation) {
            return response()->json(['success' => false, 'message' => 'Assignation not found'], 404);
        }

        if ($assignation->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Assignation is already cancelled',
            ], 422);
        }

        $assignation->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'data' => $assignation->fresh(),
            'message' => 'Assignation cancelled',
        ]);
    }

    /**
     * Generate and return PDF for an assignation.
     */
    public function pdf(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');

        $assignation = Assignation::where('company_id', $companyId)
            ->where('id', $id)
            ->first();

        if (!$assignation) {
            return response()->json(['success' => false, 'message' => 'Assignation not found'], 404);
        }

        $company = Company::find($companyId);

        $amountWords = '';
        try {
            $service = app(\Modules\Mk\Services\AmountToWordsService::class);
            $amountWords = $service->convert($assignation->amount, 'MKD');
        } catch (\Exception $e) {
            $amountWords = '';
        }

        $pdf = \PDF::loadView('app.pdf.reports.asignacija', [
            'assignation' => $assignation,
            'company' => $company,
            'amount_words' => $amountWords,
            'amount_formatted' => number_format($assignation->amount / 100, 2, '.', ','),
        ]);
        $pdf->setOptions(['isRemoteEnabled' => true, 'defaultFont' => 'DejaVu Sans']);

        $filename = sprintf(
            'asignacija_%s_%s.pdf',
            $assignation->assignation_number,
            $assignation->assignation_date->format('Y-m-d')
        );

        return $pdf->stream($filename);
    }
}
// CLAUDE-CHECKPOINT
