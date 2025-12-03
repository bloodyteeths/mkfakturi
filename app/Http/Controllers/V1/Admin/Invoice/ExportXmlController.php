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

            // Apply digital signature if requested and certificate is configured
            $wasSigned = false;
            if ($includeSignature || $format === 'ubl_signed') {
                try {
                    $xmlSigner = new MkXmlSigner;

                    // Check if signing is configured
                    $config = $xmlSigner->validateConfiguration();
                    if ($config['is_valid']) {
                        // Certificate is configured, sign the XML
                        $xmlContent = $xmlSigner->signUblInvoice($xmlContent);
                        $wasSigned = true;

                        Log::info('XML signed successfully', [
                            'invoice_id' => $invoice->id,
                        ]);
                    } else {
                        // Certificate not configured, skip signing gracefully
                        Log::info('XML signing skipped - certificate not configured', [
                            'invoice_id' => $invoice->id,
                            'errors' => $config['errors'],
                            'warnings' => $config['warnings'] ?? [],
                        ]);
                    }

                } catch (\Exception $e) {
                    // Signing failed, but don't block the export - just log and continue unsigned
                    Log::warning('XML signing failed, exporting unsigned', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Log successful export
            Log::info('Invoice XML exported', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'format' => $format,
                'signed' => $wasSigned,
                'signature_requested' => $includeSignature,
                'user_id' => auth()->id(),
            ]);

            // Generate filename - only add -signed suffix if actually signed
            $suffix = $wasSigned ? '-signed' : '';
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
