<?php

namespace App\Http\Controllers\V1\Admin\Imports;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    /**
     * Upload and create new import job
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx,xml|max:51200', // 50MB
            'type' => 'required|string|in:universal_migration,customers,items,invoices,expenses',
        ]);

        $user = $request->user();
        $company = $user->company;

        // Store the uploaded file
        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('imports/' . $company->id, $filename, 'local');

        // Create import job
        $importJob = ImportJob::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'type' => $request->type,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'status' => 'uploaded',
            'step' => 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $importJob->fresh(),
        ]);
    }

    /**
     * Get import job details
     */
    public function show(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $importJob,
        ]);
    }

    /**
     * Save field mappings
     */
    public function saveMapping(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        $request->validate([
            'mappings' => 'required|array',
        ]);

        $importJob->update([
            'field_mappings' => $request->mappings,
            'step' => 2,
            'status' => 'mapped',
        ]);

        return response()->json([
            'success' => true,
            'data' => $importJob->fresh(),
        ]);
    }

    /**
     * Validate import data
     */
    public function validate(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        // TODO: Implement validation logic
        $validationResults = [
            'total_records' => 10,
            'valid_records' => 9,
            'invalid_records' => 1,
            'errors' => [],
            'warnings' => [],
        ];

        $importJob->update([
            'validation_results' => $validationResults,
            'step' => 3,
            'status' => 'validated',
        ]);

        return response()->json([
            'success' => true,
            'data' => $validationResults,
        ]);
    }

    /**
     * Commit the import
     */
    public function commit(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        // TODO: Implement actual import logic
        $importJob->update([
            'step' => 4,
            'status' => 'processing',
        ]);

        // Simulate processing (in production, this would be a queued job)
        $importJob->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $importJob->fresh(),
        ]);
    }

    /**
     * Get import progress
     */
    public function progress(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $importJob->status,
                'step' => $importJob->step,
                'progress' => $importJob->progress ?? 0,
                'results' => $importJob->results,
            ],
        ]);
    }

    /**
     * Cancel import
     */
    public function destroy(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        // Delete file
        if ($importJob->file_path && Storage::disk('local')->exists($importJob->file_path)) {
            Storage::disk('local')->delete($importJob->file_path);
        }

        $importJob->delete();

        return response()->json([
            'success' => true,
            'message' => 'Import cancelled successfully',
        ]);
    }

    /**
     * Download CSV template
     */
    public function downloadTemplate(Request $request, $type)
    {
        $templates = [
            'customers' => "name,email,phone,address,vat_number,website,currency\nExample Company,email@example.com,+38970123456,Address Line 1,MK1234567890123,https://example.com,MKD",
            'items' => "name,description,price,unit,category,sku,tax_type,tax_rate\nConsulting Services,Professional services,100.00,hour,Services,SERV-001,Standard,18",
            'invoices' => "invoice_number,customer_name,invoice_date,due_date,total,subtotal,tax,status,currency,notes\nINV-001,Customer Name,2025-01-01,2025-02-01,1180.00,1000.00,180.00,SENT,MKD,Notes here",
            'invoice_with_items' => "invoice_number,customer_name,invoice_date,due_date,item_name,quantity,unit_price,tax_rate,currency\nINV-001,Customer Name,2025-01-01,2025-02-01,Service,1,1000.00,18,MKD",
        ];

        if (!isset($templates[$type])) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        $filename = $type . '_import_template.csv';

        return response($templates[$type], 200)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get import logs
     */
    public function logs(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->user()->company_id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $importJob->logs ?? [],
        ]);
    }
}
// CLAUDE-CHECKPOINT
