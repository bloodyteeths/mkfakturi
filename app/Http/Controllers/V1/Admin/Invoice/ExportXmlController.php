<?php

namespace App\Http\Controllers\V1\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Services\MkUblMapper;
use Modules\Mk\Services\MkXmlSigner;

class ExportXmlController extends Controller
{
    /**
     * Export invoice as UBL XML with optional digital signature
     */
    public function export(Request $request, Invoice $invoice)
    {
        try {
            // Validate request
            $request->validate([
                'format' => 'required|in:ubl,ubl_signed',
                'include_signature' => 'boolean',
                'validate' => 'boolean',
            ]);

            // Check if user can view this invoice
            $this->authorize('view', $invoice);

            $format = $request->input('format', 'ubl');
            $includeSignature = $request->boolean('include_signature', false);
            $validateXml = $request->boolean('validate', true);

            // Generate UBL XML using MkUblMapper
            $ublMapper = new MkUblMapper;
            $xmlContent = $ublMapper->mapInvoiceToUbl($invoice);

            // Validate XML if requested
            if ($validateXml) {
                $validation = $ublMapper->validateUblXml($xmlContent);

                // Log validation results for debugging
                if (! $validation['is_valid']) {
                    Log::warning('XML validation failed', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'errors' => $validation['errors'],
                        'skipped' => $validation['skipped'] ?? false,
                    ]);

                    return response()->json([
                        'message' => 'XML validation failed',
                        'errors' => $validation['errors'],
                    ], 422);
                }

                // Log if validation was skipped
                if ($validation['skipped'] ?? false) {
                    Log::info('XML validation skipped', [
                        'invoice_id' => $invoice->id,
                        'reason' => $validation['reason'] ?? 'unknown',
                    ]);
                }
            }

            // Apply digital signature if requested
            if ($includeSignature || $format === 'ubl_signed') {
                try {
                    $xmlSigner = new MkXmlSigner;

                    // Validate signer configuration
                    $config = $xmlSigner->validateConfiguration();
                    if (! $config['is_valid']) {
                        return response()->json([
                            'message' => 'XML signing configuration invalid',
                            'errors' => $config['errors'],
                        ], 422);
                    }

                    $xmlContent = $xmlSigner->signUblInvoice($xmlContent);

                } catch (\Exception $e) {
                    Log::error('XML signing failed', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'message' => 'XML signing failed: '.$e->getMessage(),
                    ], 500);
                }
            }

            // Log successful export
            Log::info('Invoice XML exported', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'format' => $format,
                'signed' => $includeSignature,
                'user_id' => auth()->id(),
            ]);

            // Generate filename
            $suffix = $includeSignature || $format === 'ubl_signed' ? '-signed' : '';
            $filename = "invoice-{$invoice->invoice_number}-ubl{$suffix}.xml";

            // Return XML as download
            return response($xmlContent, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($xmlContent),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Unauthorized to export this invoice',
            ], 403);

        } catch (\Exception $e) {
            Log::error('Invoice XML export failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'XML export failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
