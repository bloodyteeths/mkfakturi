<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Mk\Services\OpenItemsStatementService;

class OpenItemsStatementController extends Controller
{
    protected OpenItemsStatementService $service;

    public function __construct(OpenItemsStatementService $service)
    {
        $this->service = $service;
    }

    /**
     * Get IOS data for a customer.
     */
    public function customer(Request $request, int $customer): JsonResponse
    {
        try {
            $request->validate([
                'as_of_date' => 'required|date',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');
        $asOfDate = $request->query('as_of_date');

        try {
            $data = $this->service->generateForCustomer($companyId, $customer, $asOfDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate IOS: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download IOS PDF for a customer.
     */
    public function customerPdf(Request $request, int $customer)
    {
        try {
            $request->validate([
                'as_of_date' => 'required|date',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');
        $asOfDate = $request->query('as_of_date');

        try {
            $pdfContent = $this->service->generatePdf('customer', $companyId, $customer, $asOfDate);
            $filename = 'ios-customer-' . $customer . '-' . $asOfDate . '.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get IOS data for a supplier.
     */
    public function supplier(Request $request, int $supplier): JsonResponse
    {
        try {
            $request->validate([
                'as_of_date' => 'required|date',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');
        $asOfDate = $request->query('as_of_date');

        try {
            $data = $this->service->generateForSupplier($companyId, $supplier, $asOfDate);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate IOS: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download IOS PDF for a supplier.
     */
    public function supplierPdf(Request $request, int $supplier)
    {
        try {
            $request->validate([
                'as_of_date' => 'required|date',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $companyId = (int) $request->header('company');
        $asOfDate = $request->query('as_of_date');

        try {
            $pdfContent = $this->service->generatePdf('supplier', $companyId, $supplier, $asOfDate);
            $filename = 'ios-supplier-' . $supplier . '-' . $asOfDate . '.pdf';

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }
}
// CLAUDE-CHECKPOINT
