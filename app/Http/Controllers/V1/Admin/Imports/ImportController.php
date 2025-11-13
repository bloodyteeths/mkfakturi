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
        \Log::info('[ImportController] Upload started', [
            'has_file' => $request->hasFile('file'),
            'type' => $request->input('type'),
            'user_id' => $request->user()?->id,
        ]);

        try {
            $request->validate([
                'file' => 'required|file|mimes:csv,xls,xlsx,xml|max:51200', // 50MB
                'type' => 'required|string|in:universal_migration,customers,invoices,items,payments,expenses,complete',
            ]);
        } catch (\Exception $e) {
            \Log::error('[ImportController] Validation failed', [
                'error' => $e->getMessage(),
                'errors' => $e->errors ?? []
            ]);
            throw $e;
        }

        $user = $request->user();
        $companyId = $request->header('company');

        \Log::info('[ImportController] User and company loaded', [
            'user_id' => $user->id,
            'company_id' => $companyId,
        ]);

        // Map universal_migration to complete type for database
        $importType = $request->type === 'universal_migration' ? 'complete' : $request->type;

        // Store the uploaded file
        $file = $request->file('file');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        \Log::info('[ImportController] Storing file', [
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        try {
            $path = $file->storeAs('imports/' . $companyId, $filename, 'local');
            \Log::info('[ImportController] File stored successfully', ['path' => $path]);
        } catch (\Exception $e) {
            \Log::error('[ImportController] File storage failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        // Create import job
        try {
            $importJob = ImportJob::create([
                'company_id' => $companyId,
                'creator_id' => $user->id,
                'name' => 'Import from ' . $file->getClientOriginalName(),
                'type' => $importType,
                'file_path' => $path,
                'file_type' => $file->getClientOriginalExtension(),
                'file_info' => [
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ],
                'status' => 'pending',
            ]);
            \Log::info('[ImportController] ImportJob created', ['id' => $importJob->id]);
        } catch (\Exception $e) {
            \Log::error('[ImportController] ImportJob creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }

        $responseData = $importJob->fresh();

        \Log::info('[ImportController] Upload completed successfully', [
            'import_id' => $importJob->id,
            'response_data' => $responseData->toArray(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    /**
     * Get import job details
     */
    public function show(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->header('company'))
            ->findOrFail($id);

        // Parse CSV and detect fields if not already done
        $data = $importJob->toArray();

        if (!isset($data['detected_fields']) && $importJob->file_path) {
            \Log::info('[ImportController] show() - detecting fields', [
                'import_id' => $id,
                'file_path' => $importJob->file_path,
            ]);

            try {
                $filePath = storage_path('app/' . $importJob->file_path);

                \Log::info('[ImportController] File path constructed', [
                    'file_path' => $filePath,
                    'exists' => file_exists($filePath),
                ]);

                if (file_exists($filePath)) {
                    $file = fopen($filePath, 'r');

                    // Read headers
                    $headers = fgetcsv($file);

                    // Read sample rows
                    $sampleRows = [];
                    for ($i = 0; $i < 3 && !feof($file); $i++) {
                        $row = fgetcsv($file);
                        if ($row) {
                            $sampleRows[] = $row;
                        }
                    }

                    fclose($file);

                    // Transform headers into objects with metadata
                    $detectedFields = [];
                    if ($headers) {
                        foreach ($headers as $index => $headerName) {
                            // Collect sample values for this column
                            $samples = [];
                            foreach ($sampleRows as $row) {
                                if (isset($row[$index])) {
                                    $samples[] = $row[$index];
                                }
                            }

                            $detectedFields[] = [
                                'name' => $headerName,
                                'type' => 'string',
                                'sample_data' => $samples,
                                'index' => $index,
                            ];
                        }
                    }

                    // Generate mapping suggestions
                    $mappingSuggestions = $this->generateMappingSuggestions($detectedFields);
                    $confidence = count($mappingSuggestions) / max(count($detectedFields), 1);

                    $data['detected_fields'] = $detectedFields;
                    $data['mapping_suggestions'] = $mappingSuggestions;
                    $data['auto_mapping_confidence'] = round($confidence, 2);

                    \Log::info('[ImportController] Fields detected successfully', [
                        'count' => count($detectedFields),
                        'field_names' => array_column($detectedFields, 'name'),
                        'suggestions' => $mappingSuggestions,
                        'confidence' => $data['auto_mapping_confidence'],
                    ]);
                } else {
                    \Log::warning('[ImportController] File not found', [
                        'file_path' => $filePath,
                    ]);
                    $data['detected_fields'] = [];
                    $data['mapping_suggestions'] = [];
                    $data['auto_mapping_confidence'] = 0;
                }
            } catch (\Exception $e) {
                \Log::error('[ImportController] Field detection failed', [
                    'import_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                $data['detected_fields'] = [];
                $data['mapping_suggestions'] = [];
                $data['auto_mapping_confidence'] = 0;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Save field mappings
     */
    public function saveMapping(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->header('company'))
            ->findOrFail($id);

        $request->validate([
            'mappings' => 'required|array',
        ]);

        $importJob->update([
            'mapping_config' => $request->mappings,
            'status' => 'mapping',
        ]);

        return response()->json([
            'success' => true,
            'data' => $importJob->fresh(),
        ]);
    }

    /**
     * Validate import data
     */
    public function validateData(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->header('company'))
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
            'validation_rules' => $validationResults,
            'status' => 'validating',
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
        $importJob = ImportJob::where('company_id', $request->header('company'))
            ->findOrFail($id);

        // TODO: Implement actual import logic
        $importJob->update([
            'status' => 'committing',
            'started_at' => now(),
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
        $importJob = ImportJob::where('company_id', $request->header('company'))
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $importJob->status,
                'progress' => $importJob->progressPercentage ?? 0,
                'total_records' => $importJob->total_records,
                'processed_records' => $importJob->processed_records,
                'successful_records' => $importJob->successful_records,
                'failed_records' => $importJob->failed_records,
            ],
        ]);
    }

    /**
     * Cancel import
     */
    public function destroy(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->header('company'))
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
        $importJob = ImportJob::where('company_id', $request->header('company'))
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $importJob->logs()->get(),
        ]);
    }

    /**
     * Generate mapping suggestions based on field names
     */
    private function generateMappingSuggestions($detectedFields)
    {
        $suggestions = [];

        // Define mapping rules (CSV field name => target field name)
        $mappingRules = [
            // Customer fields
            'name' => 'name',
            'customer_name' => 'name',
            'company_name' => 'name',
            'email' => 'email',
            'customer_email' => 'email',
            'phone' => 'phone',
            'telephone' => 'phone',
            'mobile' => 'phone',
            'address' => 'billing_address_street_1',
            'street' => 'billing_address_street_1',
            'city' => 'billing_address_city',
            'zip' => 'billing_address_zip',
            'postal_code' => 'billing_address_zip',
            'country' => 'billing_address_country',
            'vat_number' => 'vat_number',
            'tax_id' => 'vat_number',
            'website' => 'website',
            'currency' => 'currency',

            // Invoice fields
            'invoice_number' => 'invoice_number',
            'invoice_date' => 'invoice_date',
            'due_date' => 'due_date',
            'total' => 'total',
            'amount' => 'total',
            'subtotal' => 'sub_total',
            'tax' => 'tax',
            'discount' => 'discount',
            'notes' => 'notes',
            'description' => 'notes',

            // Item fields
            'item_name' => 'name',
            'product_name' => 'name',
            'quantity' => 'quantity',
            'qty' => 'quantity',
            'price' => 'price',
            'unit_price' => 'price',
            'unit' => 'unit',
        ];

        foreach ($detectedFields as $field) {
            $fieldName = $field['name'];
            $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '_', $fieldName)));

            // Direct match
            if (isset($mappingRules[$normalizedName])) {
                $suggestions[$fieldName] = $mappingRules[$normalizedName];
                continue;
            }

            // Fuzzy match - check if any rule key is contained in the field name
            foreach ($mappingRules as $ruleKey => $ruleValue) {
                if (str_contains($normalizedName, $ruleKey) || str_contains($ruleKey, $normalizedName)) {
                    $suggestions[$fieldName] = $ruleValue;
                    break;
                }
            }
        }

        return $suggestions;
    }
}
// CLAUDE-CHECKPOINT
