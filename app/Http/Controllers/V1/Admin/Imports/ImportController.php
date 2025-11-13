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

                    // Generate mapping suggestions based on import type
                    $mappingSuggestions = $this->generateMappingSuggestions($detectedFields, $importJob->type);
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

        \Log::info('[ImportController] validateData() called', [
            'import_id' => $id,
            'file_path' => $importJob->file_path,
        ]);

        try {
            $filePath = storage_path('app/' . $importJob->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            // Read and parse CSV
            $file = fopen($filePath, 'r');
            $headers = fgetcsv($file);

            $records = [];
            $rowNumber = 1;

            while (($row = fgetcsv($file)) !== false) {
                $recordData = [];
                foreach ($headers as $index => $header) {
                    $recordData[$header] = $row[$index] ?? '';
                }

                // Validate this record with import type
                $validation = $this->validateRecord($recordData, $rowNumber, $importJob->type);

                $records[] = [
                    'row_number' => $rowNumber,
                    'data' => $recordData,
                    'has_errors' => !empty($validation['errors']),
                    'has_warnings' => !empty($validation['warnings']),
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ];

                $rowNumber++;
            }

            fclose($file);

            // Calculate statistics
            $totalRecords = count($records);
            $invalidRecords = count(array_filter($records, fn($r) => $r['has_errors']));
            $validRecords = $totalRecords - $invalidRecords;

            $validationResults = [
                'total_records' => $totalRecords,
                'valid_records' => $validRecords,
                'invalid_records' => $invalidRecords,
                'errors' => [],
                'warnings' => [],
                'preview' => $records,
            ];

            \Log::info('[ImportController] Validation completed', [
                'total_records' => $totalRecords,
                'valid_records' => $validRecords,
                'invalid_records' => $invalidRecords,
            ]);

            $importJob->update([
                'total_records' => $totalRecords,
                'validation_rules' => $validationResults,
                'status' => 'validated',
            ]);

            return response()->json([
                'success' => true,
                'data' => $validationResults,
            ]);

        } catch (\Exception $e) {
            \Log::error('[ImportController] Validation failed', [
                'import_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate a single record based on import type
     *
     * @param array $data The record data to validate
     * @param int $rowNumber The row number for error reporting
     * @param string $importType The import type (customers, invoices, items, payments, expenses, complete)
     * @return array Array with 'errors' and 'warnings' keys
     */
    private function validateRecord($data, $rowNumber, $importType = 'customers')
    {
        $errors = [];
        $warnings = [];

        // Type-specific validation
        switch ($importType) {
            case 'payments':
                // Required fields for payments
                if (empty($data['payment_date'])) {
                    $errors[] = "Row {$rowNumber}: Payment date is required";
                } else {
                    // Validate date format
                    try {
                        \Carbon\Carbon::parse($data['payment_date']);
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: Invalid payment date format. Use YYYY-MM-DD or similar standard format";
                    }
                }

                if (empty($data['amount'])) {
                    $errors[] = "Row {$rowNumber}: Amount is required";
                } else {
                    // Validate amount is numeric and positive
                    $amount = floatval($data['amount']);
                    if (!is_numeric($data['amount'])) {
                        $errors[] = "Row {$rowNumber}: Amount must be a valid number";
                    } elseif ($amount <= 0) {
                        $errors[] = "Row {$rowNumber}: Amount must be greater than 0";
                    }
                }

                if (empty($data['customer_name'])) {
                    $errors[] = "Row {$rowNumber}: Customer name is required";
                }

                // Optional field warnings
                if (empty($data['payment_method'])) {
                    $warnings[] = "Row {$rowNumber}: Payment method is missing";
                } else {
                    // Validate payment method against common methods
                    $commonMethods = [
                        'bank transfer', 'bank_transfer', 'wire transfer',
                        'cash', 'credit card', 'credit_card', 'creditcard',
                        'debit card', 'debit_card', 'debitcard',
                        'paypal', 'stripe', 'check', 'cheque',
                        'online', 'electronic', 'eft', 'ach'
                    ];

                    $normalizedMethod = strtolower(trim($data['payment_method']));
                    $isValid = false;

                    foreach ($commonMethods as $method) {
                        if (str_contains($normalizedMethod, $method) || str_contains($method, $normalizedMethod)) {
                            $isValid = true;
                            break;
                        }
                    }

                    if (!$isValid) {
                        $warnings[] = "Row {$rowNumber}: Payment method '{$data['payment_method']}' is uncommon. Common methods include: Bank Transfer, Cash, Credit Card, etc.";
                    }
                }

                if (empty($data['invoice_number'])) {
                    $warnings[] = "Row {$rowNumber}: Invoice number is missing - payment will not be linked to an invoice";
                }

                if (empty($data['reference'])) {
                    $warnings[] = "Row {$rowNumber}: Reference/transaction ID is missing";
                }
                break;

            case 'invoices':
                // Required fields for invoices
                if (empty($data['invoice_number'])) {
                    $errors[] = "Row {$rowNumber}: Invoice number is required";
                }

                if (empty($data['customer_name'])) {
                    $errors[] = "Row {$rowNumber}: Customer name is required";
                }

                if (empty($data['invoice_date'])) {
                    $errors[] = "Row {$rowNumber}: Invoice date is required";
                } else {
                    // Validate date format
                    try {
                        \Carbon\Carbon::parse($data['invoice_date']);
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: Invalid invoice date format. Use YYYY-MM-DD or similar standard format";
                    }
                }

                if (empty($data['total'])) {
                    $errors[] = "Row {$rowNumber}: Total is required";
                } else {
                    // Validate total is numeric and positive
                    $total = floatval($data['total']);
                    if (!is_numeric($data['total'])) {
                        $errors[] = "Row {$rowNumber}: Total must be a valid number";
                    } elseif ($total <= 0) {
                        $errors[] = "Row {$rowNumber}: Total must be greater than 0";
                    }
                }

                // Optional field warnings
                if (empty($data['due_date'])) {
                    $warnings[] = "Row {$rowNumber}: Due date is missing";
                } else {
                    // Validate date format
                    try {
                        \Carbon\Carbon::parse($data['due_date']);
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: Invalid due date format. Use YYYY-MM-DD or similar standard format";
                    }
                }

                if (empty($data['subtotal'])) {
                    $warnings[] = "Row {$rowNumber}: Subtotal is missing";
                } else {
                    // Validate subtotal is numeric
                    if (!is_numeric($data['subtotal'])) {
                        $errors[] = "Row {$rowNumber}: Subtotal must be a valid number";
                    }
                }

                if (empty($data['tax'])) {
                    $warnings[] = "Row {$rowNumber}: Tax is missing";
                } else {
                    // Validate tax is numeric
                    if (!is_numeric($data['tax'])) {
                        $errors[] = "Row {$rowNumber}: Tax must be a valid number";
                    }
                }

                // Status validation
                if (!empty($data['status'])) {
                    $validStatuses = ['DRAFT', 'SENT', 'VIEWED', 'COMPLETED', 'PAID', 'UNPAID', 'PARTIALLY_PAID', 'OVERDUE'];
                    $normalizedStatus = strtoupper(trim($data['status']));
                    if (!in_array($normalizedStatus, $validStatuses)) {
                        $warnings[] = "Row {$rowNumber}: Status '{$data['status']}' is not a standard value. Valid values: " . implode(', ', $validStatuses);
                    }
                }

                // Currency code validation
                if (!empty($data['currency'])) {
                    $currencyCode = strtoupper(trim($data['currency']));
                    // Standard 3-letter currency codes (ISO 4217)
                    if (strlen($currencyCode) !== 3 || !ctype_alpha($currencyCode)) {
                        $warnings[] = "Row {$rowNumber}: Currency code '{$data['currency']}' should be a 3-letter code (e.g., MKD, USD, EUR)";
                    }
                }
                break;
                // CLAUDE-CHECKPOINT

            case 'items':
                // Required fields for items
                if (empty($data['name'])) {
                    $errors[] = "Row {$rowNumber}: Item name is required";
                }

                if (empty($data['price'])) {
                    $errors[] = "Row {$rowNumber}: Price is required";
                } else {
                    // Validate price is numeric and positive
                    $price = floatval($data['price']);
                    if (!is_numeric($data['price'])) {
                        $errors[] = "Row {$rowNumber}: Price must be a valid number";
                    } elseif ($price <= 0) {
                        $errors[] = "Row {$rowNumber}: Price must be greater than 0";
                    }
                }

                // Tax rate validation (0-100 range)
                if (!empty($data['tax_rate'])) {
                    $taxRate = floatval($data['tax_rate']);
                    if (!is_numeric($data['tax_rate'])) {
                        $errors[] = "Row {$rowNumber}: Tax rate must be a valid number";
                    } elseif ($taxRate < 0 || $taxRate > 100) {
                        $errors[] = "Row {$rowNumber}: Tax rate must be between 0 and 100";
                    }
                }

                // Unit validation (common units)
                if (!empty($data['unit'])) {
                    $validUnits = [
                        'hour', 'hours', 'hr', 'hrs',
                        'piece', 'pieces', 'pcs', 'pc',
                        'kg', 'kilogram', 'kilograms',
                        'g', 'gram', 'grams',
                        'l', 'liter', 'liters', 'litre', 'litres',
                        'ml', 'milliliter', 'milliliters', 'millilitre', 'millilitres',
                        'm', 'meter', 'meters', 'metre', 'metres',
                        'cm', 'centimeter', 'centimeters', 'centimetre', 'centimetres',
                        'mm', 'millimeter', 'millimeters', 'millimetre', 'millimetres',
                        'ft', 'foot', 'feet',
                        'in', 'inch', 'inches',
                        'lb', 'pound', 'pounds',
                        'oz', 'ounce', 'ounces',
                        'day', 'days',
                        'week', 'weeks',
                        'month', 'months',
                        'year', 'years',
                        'item', 'items',
                        'unit', 'units',
                        'box', 'boxes',
                        'pack', 'packs',
                        'set', 'sets',
                        'pair', 'pairs',
                        'dozen',
                        'each',
                    ];

                    $normalizedUnit = strtolower(trim($data['unit']));
                    if (!in_array($normalizedUnit, $validUnits)) {
                        $warnings[] = "Row {$rowNumber}: Unit '{$data['unit']}' is not a standard unit. Common units: hour, piece, kg, liter, meter, etc.";
                    }
                }

                // Optional field warnings
                if (empty($data['description'])) {
                    $warnings[] = "Row {$rowNumber}: Item description is missing";
                }

                if (empty($data['unit'])) {
                    $warnings[] = "Row {$rowNumber}: Unit of measure is missing";
                }

                if (empty($data['category'])) {
                    $warnings[] = "Row {$rowNumber}: Category is missing";
                }

                if (empty($data['sku'])) {
                    $warnings[] = "Row {$rowNumber}: SKU/Product code is missing";
                }

                // Tax warnings
                if (empty($data['tax_type']) && !empty($data['tax_rate'])) {
                    $warnings[] = "Row {$rowNumber}: Tax rate is provided but tax type is missing";
                }

                if (!empty($data['tax_type']) && empty($data['tax_rate'])) {
                    $warnings[] = "Row {$rowNumber}: Tax type is provided but tax rate is missing";
                }
                break;
                // CLAUDE-CHECKPOINT

            case 'expenses':
                // Required fields for expenses
                if (empty($data['expense_date'])) {
                    $errors[] = "Row {$rowNumber}: Expense date is required";
                } else {
                    // Validate date format
                    try {
                        \Carbon\Carbon::parse($data['expense_date']);
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: Invalid expense date format. Use YYYY-MM-DD or similar standard format";
                    }
                }

                if (empty($data['amount'])) {
                    $errors[] = "Row {$rowNumber}: Amount is required";
                } else {
                    // Validate amount is numeric and positive
                    $amount = floatval($data['amount']);
                    if (!is_numeric($data['amount'])) {
                        $errors[] = "Row {$rowNumber}: Amount must be a valid number";
                    } elseif ($amount <= 0) {
                        $errors[] = "Row {$rowNumber}: Amount must be greater than 0";
                    }
                }

                // Optional field warnings
                if (empty($data['category'])) {
                    $warnings[] = "Row {$rowNumber}: Category is missing - expense will be assigned to 'Uncategorized'";
                }

                if (empty($data['notes'])) {
                    $warnings[] = "Row {$rowNumber}: Notes/description is missing";
                }

                if (empty($data['payment_method'])) {
                    $warnings[] = "Row {$rowNumber}: Payment method is missing";
                } else {
                    // Validate payment method against common methods
                    $commonMethods = [
                        'bank transfer', 'bank_transfer', 'wire transfer',
                        'cash', 'credit card', 'credit_card', 'creditcard',
                        'debit card', 'debit_card', 'debitcard',
                        'paypal', 'stripe', 'check', 'cheque',
                        'online', 'electronic', 'eft', 'ach'
                    ];

                    $normalizedMethod = strtolower(trim($data['payment_method']));
                    $isValid = false;

                    foreach ($commonMethods as $method) {
                        if (str_contains($normalizedMethod, $method) || str_contains($method, $normalizedMethod)) {
                            $isValid = true;
                            break;
                        }
                    }

                    if (!$isValid) {
                        $warnings[] = "Row {$rowNumber}: Payment method '{$data['payment_method']}' is uncommon. Common methods include: Bank Transfer, Cash, Credit Card, etc.";
                    }
                }

                // Currency code validation
                if (!empty($data['currency'])) {
                    $currencyCode = strtoupper(trim($data['currency']));
                    // Standard 3-letter currency codes (ISO 4217)
                    if (strlen($currencyCode) !== 3 || !ctype_alpha($currencyCode)) {
                        $warnings[] = "Row {$rowNumber}: Currency code '{$data['currency']}' should be a 3-letter code (e.g., MKD, USD, EUR)";
                    }
                }

                // Customer name validation (optional but warn if provided)
                if (!empty($data['customer_name'])) {
                    $warnings[] = "Row {$rowNumber}: Customer name provided - will attempt to link expense to customer if found";
                }
                break;
                // CLAUDE-CHECKPOINT

            case 'customers':
            default:
                // Required field validation for customers
                if (empty($data['name'])) {
                    $errors[] = "Row {$rowNumber}: Name is required";
                }

                if (empty($data['email'])) {
                    $errors[] = "Row {$rowNumber}: Email is required";
                } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row {$rowNumber}: Invalid email format";
                }

                // Optional field warnings
                if (empty($data['phone'])) {
                    $warnings[] = "Row {$rowNumber}: Phone number is missing";
                }

                if (empty($data['vat_number'])) {
                    $warnings[] = "Row {$rowNumber}: VAT number is missing";
                }
                break;
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
    // CLAUDE-CHECKPOINT

    /**
     * Commit the import
     */
    public function commit(Request $request, $id)
    {
        $importJob = ImportJob::where('company_id', $request->header('company'))
            ->findOrFail($id);

        \Log::info('[ImportController] commit() started', [
            'import_id' => $id,
            'type' => $importJob->type,
            'file_path' => $importJob->file_path,
        ]);

        $importJob->update([
            'status' => 'committing',
            'started_at' => now(),
        ]);

        try {
            $filePath = storage_path('app/' . $importJob->file_path);

            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            // Read CSV and apply field mappings
            $file = fopen($filePath, 'r');
            $headers = fgetcsv($file);

            $mappingConfig = $importJob->mapping_config ?? [];
            $successCount = 0;
            $failCount = 0;
            $errors = [];

            \DB::beginTransaction();

            while (($row = fgetcsv($file)) !== false) {
                try {
                    // Map CSV row to target fields
                    $mappedData = [];
                    foreach ($headers as $index => $csvField) {
                        $targetField = $mappingConfig[$csvField] ?? null;
                        if ($targetField && isset($row[$index])) {
                            $mappedData[$targetField] = $row[$index];
                        }
                    }

                    // Import based on type
                    $this->importRecord($importJob->type, $mappedData, $request->header('company'), $request->user()->id);
                    $successCount++;

                } catch (\Exception $e) {
                    $failCount++;
                    $errors[] = [
                        'row' => $successCount + $failCount,
                        'error' => $e->getMessage(),
                    ];
                    \Log::error('[ImportController] Record import failed', [
                        'row' => $successCount + $failCount,
                        'error' => $e->getMessage(),
                        'data' => $mappedData ?? [],
                    ]);
                }
            }

            fclose($file);
            \DB::commit();

            $importJob->update([
                'status' => 'completed',
                'completed_at' => now(),
                'successful_records' => $successCount,
                'failed_records' => $failCount,
                'processed_records' => $successCount + $failCount,
                'error_details' => $errors,
            ]);

            \Log::info('[ImportController] commit() completed', [
                'import_id' => $id,
                'successful_records' => $successCount,
                'failed_records' => $failCount,
            ]);

            return response()->json([
                'success' => true,
                'data' => $importJob->fresh(),
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();

            \Log::error('[ImportController] commit() failed', [
                'import_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $importJob->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import a single record based on type
     */
    private function importRecord($type, $data, $companyId, $creatorId)
    {
        switch ($type) {
            case 'customers':
            case 'complete':
                return $this->importCustomer($data, $companyId, $creatorId);

            case 'invoices':
                return $this->importInvoice($data, $companyId, $creatorId);

            case 'items':
                return $this->importItem($data, $companyId, $creatorId);

            case 'payments':
                return $this->importPayment($data, $companyId, $creatorId);

            case 'expenses':
                return $this->importExpense($data, $companyId, $creatorId);

            default:
                throw new \Exception('Unsupported import type: ' . $type);
        }
    }

    /**
     * Import a customer record
     */
    private function importCustomer($data, $companyId, $creatorId)
    {
        // Get or create currency
        $currencyCode = $data['currency'] ?? 'MKD';
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            $currency = \App\Models\Currency::where('code', 'MKD')->first();
        }

        // Create customer
        $customer = \App\Models\Customer::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'company_id' => $companyId,
            'creator_id' => $creatorId,
            'currency_id' => $currency ? $currency->id : null,
        ]);

        // Create billing address if address data exists
        if (!empty($data['billing_address_street_1']) || !empty($data['address'])) {
            \App\Models\Address::create([
                'name' => $data['name'],
                'address_street_1' => $data['billing_address_street_1'] ?? $data['address'] ?? null,
                'address_street_2' => $data['billing_address_street_2'] ?? null,
                'city' => $data['billing_address_city'] ?? null,
                'state' => $data['billing_address_state'] ?? null,
                'zip' => $data['billing_address_zip'] ?? null,
                'phone' => $data['phone'] ?? null,
                'type' => 'billing',
                'user_id' => $customer->id,
                'company_id' => $companyId,
            ]);
        }

        \Log::info('[ImportController] Customer imported', [
            'customer_id' => $customer->id,
            'name' => $customer->name,
        ]);

        return $customer;
    }

    /**
     * Import an invoice record
     */
    private function importInvoice($data, $companyId, $creatorId)
    {
        // Lookup customer by name or email
        $customer = null;

        if (!empty($data['customer_name'])) {
            $customer = \App\Models\Customer::where('company_id', $companyId)
                ->where('name', $data['customer_name'])
                ->first();
        }

        if (!$customer && !empty($data['customer_email'])) {
            $customer = \App\Models\Customer::where('company_id', $companyId)
                ->where('email', $data['customer_email'])
                ->first();
        }

        // Handle customer not found
        if (!$customer) {
            throw new \Exception('Customer not found: ' . ($data['customer_name'] ?? $data['customer_email'] ?? 'unknown'));
        }

        // Get or create currency
        $currencyCode = $data['currency'] ?? 'MKD';
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            $currency = \App\Models\Currency::where('code', 'MKD')->first();
        }

        if (!$currency) {
            throw new \Exception('Currency not found: ' . $currencyCode);
        }

        // Parse dates
        $invoiceDate = !empty($data['invoice_date'])
            ? \Carbon\Carbon::parse($data['invoice_date'])
            : now();

        $dueDate = !empty($data['due_date'])
            ? \Carbon\Carbon::parse($data['due_date'])
            : now()->addDays(30);

        // Convert amounts to integer (cents)
        $total = !empty($data['total']) ? (int)round((float)$data['total'] * 100) : 0;
        $subTotal = !empty($data['subtotal']) ? (int)round((float)$data['subtotal'] * 100) : $total;
        $tax = !empty($data['tax']) ? (int)round((float)$data['tax'] * 100) : 0;

        // Map status from CSV to valid invoice status
        $status = $this->mapInvoiceStatus($data['status'] ?? 'DRAFT');
        $paidStatus = $this->mapInvoicePaidStatus($data['status'] ?? 'DRAFT');

        // Create invoice
        $invoice = \App\Models\Invoice::create([
            'invoice_number' => $data['invoice_number'] ?? 'INV-' . time(),
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'customer_id' => $customer->id,
            'company_id' => $companyId,
            'creator_id' => $creatorId,
            'currency_id' => $currency->id,
            'total' => $total,
            'sub_total' => $subTotal,
            'tax' => $tax,
            'due_amount' => $total, // Initially unpaid
            'base_due_amount' => $total, // Assuming 1:1 exchange rate for simplicity
            'status' => $status,
            'paid_status' => $paidStatus,
            'tax_per_item' => 'NO',
            'discount_per_item' => 'NO',
            'discount' => 0,
            'discount_val' => 0,
            'notes' => $data['notes'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'exchange_rate' => 1.0,
            'sent' => in_array($status, [\App\Models\Invoice::STATUS_SENT, \App\Models\Invoice::STATUS_VIEWED, \App\Models\Invoice::STATUS_COMPLETED]),
            'viewed' => in_array($status, [\App\Models\Invoice::STATUS_VIEWED]),
        ]);

        // Generate unique hash
        $invoice->unique_hash = \Vinkla\Hashids\Facades\Hashids::connection(\App\Models\Invoice::class)->encode($invoice->id);
        $invoice->save();

        \Log::info('[ImportController] Invoice imported', [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_id' => $customer->id,
            'total' => $total,
        ]);

        return $invoice;
    }
    // CLAUDE-CHECKPOINT

    /**
     * Map CSV status to valid Invoice status constant
     */
    private function mapInvoiceStatus($status)
    {
        $statusMap = [
            'DRAFT' => \App\Models\Invoice::STATUS_DRAFT,
            'SENT' => \App\Models\Invoice::STATUS_SENT,
            'VIEWED' => \App\Models\Invoice::STATUS_VIEWED,
            'COMPLETED' => \App\Models\Invoice::STATUS_COMPLETED,
            'PAID' => \App\Models\Invoice::STATUS_COMPLETED,
            'UNPAID' => \App\Models\Invoice::STATUS_SENT,
            'OVERDUE' => \App\Models\Invoice::STATUS_SENT,
        ];

        $normalizedStatus = strtoupper(trim($status));
        return $statusMap[$normalizedStatus] ?? \App\Models\Invoice::STATUS_DRAFT;
    }

    /**
     * Map CSV status to valid Invoice paid_status constant
     */
    private function mapInvoicePaidStatus($status)
    {
        $paidStatusMap = [
            'DRAFT' => \App\Models\Invoice::STATUS_UNPAID,
            'SENT' => \App\Models\Invoice::STATUS_UNPAID,
            'VIEWED' => \App\Models\Invoice::STATUS_UNPAID,
            'COMPLETED' => \App\Models\Invoice::STATUS_PAID,
            'PAID' => \App\Models\Invoice::STATUS_PAID,
            'UNPAID' => \App\Models\Invoice::STATUS_UNPAID,
            'PARTIALLY_PAID' => \App\Models\Invoice::STATUS_PARTIALLY_PAID,
            'OVERDUE' => \App\Models\Invoice::STATUS_UNPAID,
        ];

        $normalizedStatus = strtoupper(trim($status));
        return $paidStatusMap[$normalizedStatus] ?? \App\Models\Invoice::STATUS_UNPAID;
    }

    /**
     * Import an item record
     */
    private function importItem($data, $companyId, $creatorId)
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \Exception('Item name is required');
        }

        if (empty($data['price'])) {
            throw new \Exception('Item price is required');
        }

        // Convert price to cents (integer)
        $priceInCents = (int) (floatval($data['price']) * 100);

        // Get or create unit
        $unitId = null;
        if (!empty($data['unit'])) {
            $unit = \App\Models\Unit::where('name', $data['unit'])
                ->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)
                      ->orWhereNull('company_id');
                })
                ->first();

            if (!$unit) {
                // Create new unit for this company
                $unit = \App\Models\Unit::create([
                    'name' => $data['unit'],
                    'company_id' => $companyId,
                ]);
            }

            $unitId = $unit->id;
        }

        // Get currency (default to company's currency)
        $currencyCode = $data['currency'] ?? 'MKD';
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            $currency = \App\Models\Currency::where('code', 'MKD')->first();
        }

        // Create item
        $itemData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $priceInCents,
            'unit' => $data['unit'] ?? null,
            'unit_id' => $unitId,
            'company_id' => $companyId,
            'creator_id' => $creatorId,
            'currency_id' => $currency ? $currency->id : null,
        ];

        $item = \App\Models\Item::create($itemData);

        // Handle tax if tax_type and tax_rate are provided
        if (!empty($data['tax_type']) && !empty($data['tax_rate'])) {
            $taxRate = floatval($data['tax_rate']);

            // Find or create tax type
            $taxType = \App\Models\TaxType::where('name', $data['tax_type'])
                ->where('company_id', $companyId)
                ->first();

            if (!$taxType) {
                $taxType = \App\Models\TaxType::create([
                    'name' => $data['tax_type'],
                    'percent' => $taxRate,
                    'company_id' => $companyId,
                    'collective_tax' => 0,
                ]);
            }

            // Create tax entry for this item
            \App\Models\Tax::create([
                'tax_type_id' => $taxType->id,
                'item_id' => $item->id,
                'company_id' => $companyId,
                'percent' => $taxRate,
                'amount' => 0, // Calculated when item is used
            ]);

            // Mark item as having per-item tax
            $item->update(['tax_per_item' => true]);
        }

        \Log::info('[ImportController] Item imported', [
            'item_id' => $item->id,
            'name' => $item->name,
            'price' => $item->price,
            'unit' => $item->unit,
            'has_tax' => !empty($data['tax_type']),
        ]);

        return $item;
    }
// CLAUDE-CHECKPOINT

    /**
     * Import a payment record
     */
    private function importPayment($data, $companyId, $creatorId)
    {
        // Lookup customer by name
        $customer = null;
        if (!empty($data['customer_name'])) {
            $customer = \App\Models\Customer::where('company_id', $companyId)
                ->where('name', $data['customer_name'])
                ->first();

            if (!$customer) {
                throw new \Exception("Customer not found: {$data['customer_name']}");
            }
        }

        // Lookup invoice by invoice_number
        $invoice = null;
        if (!empty($data['invoice_number'])) {
            $invoice = \App\Models\Invoice::where('company_id', $companyId)
                ->where('invoice_number', $data['invoice_number'])
                ->first();

            if (!$invoice) {
                throw new \Exception("Invoice not found: {$data['invoice_number']}");
            }

            // If customer wasn't provided, use the invoice's customer
            if (!$customer && $invoice->customer_id) {
                $customer = \App\Models\Customer::find($invoice->customer_id);
            }
        }

        // Customer is required
        if (!$customer) {
            throw new \Exception("Customer is required for payment import");
        }

        // Get or create currency
        $currencyCode = $data['currency'] ?? 'MKD';
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            $currency = \App\Models\Currency::where('code', 'MKD')->first();
        }

        // Get or create payment method
        $paymentMethod = null;
        if (!empty($data['payment_method'])) {
            $paymentMethod = \App\Models\PaymentMethod::where('company_id', $companyId)
                ->where('name', $data['payment_method'])
                ->first();

            // Create payment method if it doesn't exist
            if (!$paymentMethod) {
                $paymentMethod = \App\Models\PaymentMethod::create([
                    'name' => $data['payment_method'],
                    'company_id' => $companyId,
                    'type' => \App\Models\PaymentMethod::TYPE_GENERAL,
                ]);
            }
        }

        // Parse payment date
        $paymentDate = !empty($data['payment_date'])
            ? \Carbon\Carbon::parse($data['payment_date'])
            : now();

        // Convert amount to integer (cents)
        $amount = !empty($data['amount']) ? (int)round((float)$data['amount'] * 100) : 0;

        // Create payment record
        $payment = \App\Models\Payment::create([
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'payment_method_id' => $paymentMethod ? $paymentMethod->id : null,
            'invoice_id' => $invoice ? $invoice->id : null,
            'customer_id' => $customer->id,
            'payment_number' => $data['reference'] ?? 'IMP-' . uniqid(),
            'notes' => $data['notes'] ?? null,
            'currency_id' => $currency ? $currency->id : null,
            'company_id' => $companyId,
            'creator_id' => $creatorId,
            'user_id' => $customer->id, // Legacy field
            'exchange_rate' => 1.0,
            'base_amount' => $amount,
        ]);

        // Generate unique hash
        $payment->unique_hash = \Vinkla\Hashids\Facades\Hashids::connection(\App\Models\Payment::class)->encode($payment->id);

        // Generate serial numbers
        $serial = (new \App\Services\SerialNumberFormatter)
            ->setModel($payment)
            ->setCompany($payment->company_id)
            ->setCustomer($payment->customer_id)
            ->setNextNumbers();

        $payment->sequence_number = $serial->nextSequenceNumber;
        $payment->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $payment->save();

        // If payment is linked to an invoice, update invoice paid status
        if ($invoice) {
            $invoice->subtractInvoicePayment($amount);
        }

        \Log::info('[ImportController] Payment imported', [
            'payment_id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'amount' => $amount,
            'customer_name' => $customer->name,
            'invoice_number' => $invoice ? $invoice->invoice_number : null,
        ]);

        return $payment;
    }
    // CLAUDE-CHECKPOINT

    /**
     * Import an expense record
     */
    private function importExpense($data, $companyId, $creatorId)
    {
        // Lookup customer (optional)
        $customer = null;
        if (!empty($data['customer_name'])) {
            $customer = \App\Models\Customer::where('company_id', $companyId)
                ->where('name', $data['customer_name'])
                ->first();

            // If customer not found, log warning but continue (customer is optional)
            if (!$customer) {
                \Log::warning('[ImportController] Customer not found for expense', [
                    'customer_name' => $data['customer_name'],
                    'company_id' => $companyId,
                ]);
            }
        }

        // Get or create currency
        $currencyCode = $data['currency'] ?? 'MKD';
        $currency = \App\Models\Currency::where('code', $currencyCode)->first();

        if (!$currency) {
            $currency = \App\Models\Currency::where('code', 'MKD')->first();
        }

        if (!$currency) {
            throw new \Exception('Currency not found: ' . $currencyCode);
        }

        // Lookup or create expense category
        $expenseCategory = null;
        if (!empty($data['category'])) {
            // Try to find existing category
            $expenseCategory = \App\Models\ExpenseCategory::where('company_id', $companyId)
                ->where('name', $data['category'])
                ->first();

            // Create category if it doesn't exist
            if (!$expenseCategory) {
                $expenseCategory = \App\Models\ExpenseCategory::create([
                    'name' => $data['category'],
                    'company_id' => $companyId,
                    'description' => 'Auto-created from import',
                ]);

                \Log::info('[ImportController] Created new expense category', [
                    'category_name' => $data['category'],
                    'category_id' => $expenseCategory->id,
                    'company_id' => $companyId,
                ]);
            }
        } else {
            // If no category provided, try to find or create a default "Uncategorized" category
            $expenseCategory = \App\Models\ExpenseCategory::where('company_id', $companyId)
                ->where('name', 'Uncategorized')
                ->first();

            if (!$expenseCategory) {
                $expenseCategory = \App\Models\ExpenseCategory::create([
                    'name' => 'Uncategorized',
                    'company_id' => $companyId,
                    'description' => 'Default category for imported expenses',
                ]);
            }
        }

        // Get or create payment method (optional)
        $paymentMethod = null;
        if (!empty($data['payment_method'])) {
            $paymentMethod = \App\Models\PaymentMethod::where('company_id', $companyId)
                ->where('name', $data['payment_method'])
                ->first();

            // Create payment method if it doesn't exist
            if (!$paymentMethod) {
                $paymentMethod = \App\Models\PaymentMethod::create([
                    'name' => $data['payment_method'],
                    'company_id' => $companyId,
                    'type' => \App\Models\PaymentMethod::TYPE_GENERAL,
                ]);
            }
        }

        // Parse expense date
        $expenseDate = !empty($data['expense_date'])
            ? \Carbon\Carbon::parse($data['expense_date'])
            : now();

        // Convert amount to integer (cents)
        $amount = !empty($data['amount']) ? (int)round((float)$data['amount'] * 100) : 0;

        // Create expense record
        $expense = \App\Models\Expense::create([
            'expense_date' => $expenseDate,
            'amount' => $amount,
            'base_amount' => $amount, // Assuming 1:1 exchange rate for simplicity
            'notes' => $data['notes'] ?? null,
            'expense_category_id' => $expenseCategory->id,
            'company_id' => $companyId,
            'creator_id' => $creatorId,
            'customer_id' => $customer ? $customer->id : null,
            'currency_id' => $currency->id,
            'payment_method_id' => $paymentMethod ? $paymentMethod->id : null,
            'exchange_rate' => 1.0,
        ]);

        \Log::info('[ImportController] Expense imported', [
            'expense_id' => $expense->id,
            'amount' => $amount,
            'category' => $expenseCategory->name,
            'customer_name' => $customer ? $customer->name : null,
            'expense_date' => $expenseDate->format('Y-m-d'),
        ]);

        return $expense;
    }
    // CLAUDE-CHECKPOINT

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
            'payments' => "payment_date,amount,payment_method,invoice_number,customer_name,reference,currency,notes\n2025-01-20,11800.00,Bank Transfer,INV-2025-001,Example Company,BT-20250120-001,MKD,Payment for invoice INV-2025-001",
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
     * Generate mapping suggestions based on field names and import type
     * Supports type-aware suggestions for customers, invoices, items, payments, and expenses
     *
     * @param array $detectedFields Array of detected CSV field objects
     * @param string $importType The import type (customers, invoices, items, payments, expenses, complete)
     * @return array Mapping suggestions where CSV field name => target field name
     */
    private function generateMappingSuggestions($detectedFields, $importType = 'customers')
    {
        $suggestions = [];

        // Define common customer mapping rules
        $customerMappingRules = [
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
            'street_1' => 'billing_address_street_1',
            'address_line_1' => 'billing_address_street_1',
            'street_2' => 'billing_address_street_2',
            'address_line_2' => 'billing_address_street_2',
            'city' => 'billing_address_city',
            'state' => 'billing_address_state',
            'province' => 'billing_address_state',
            'zip' => 'billing_address_zip',
            'postal_code' => 'billing_address_zip',
            'zipcode' => 'billing_address_zip',
            'country' => 'billing_address_country',
            'vat_number' => 'vat_number',
            'tax_id' => 'vat_number',
            'vat_id' => 'vat_number',
            'website' => 'website',
            'url' => 'website',
            'currency' => 'currency',
        ];

        // Define invoice-specific mapping rules
        $invoiceMappingRules = [
            // Invoice header fields
            'invoice_number' => 'invoice_number',
            'invoice_no' => 'invoice_number',
            'inv_number' => 'invoice_number',
            'invoice_id' => 'invoice_number',
            'number' => 'invoice_number',
            'invoice_date' => 'invoice_date',
            'date' => 'invoice_date',
            'due_date' => 'due_date',
            'payment_due' => 'due_date',
            'duedate' => 'due_date',
            'total' => 'total',
            'amount' => 'total',
            'invoice_total' => 'total',
            'subtotal' => 'subtotal',
            'sub_total' => 'subtotal',
            'sub_amount' => 'subtotal',
            'tax' => 'tax',
            'tax_amount' => 'tax',
            'total_tax' => 'tax',
            'status' => 'status',
            'invoice_status' => 'status',
            'currency' => 'currency',
            'currency_code' => 'currency',
            'notes' => 'notes',
            'description' => 'notes',
            'invoice_notes' => 'notes',
            'memo' => 'notes',
            'comment' => 'notes',
            'discount' => 'discount',
            'discount_amount' => 'discount',
            'shipping' => 'shipping',
            'shipping_amount' => 'shipping',
            'shipping_cost' => 'shipping',
            // Customer reference fields
            'customer_name' => 'customer_name',
            'customer_email' => 'customer_email',
            'customer_phone' => 'customer_phone',
            'customer_id' => 'customer_id',
        ];

        // Define item-specific mapping rules
        $itemMappingRules = [
            // Item fields
            'name' => 'name',
            'item_name' => 'name',
            'product_name' => 'name',
            'product' => 'name',
            'item' => 'name',
            'description' => 'description',
            'item_description' => 'description',
            'product_description' => 'description',
            'detail' => 'description',
            'details' => 'description',
            'price' => 'price',
            'unit_price' => 'price',
            'price_per_unit' => 'price',
            'unit_cost' => 'price',
            'cost' => 'price',
            'rate' => 'price',
            'unit' => 'unit',
            'unit_of_measure' => 'unit',
            'uom' => 'unit',
            'quantity' => 'quantity',
            'qty' => 'quantity',
            'amount' => 'quantity',
            'category' => 'category',
            'item_category' => 'category',
            'product_category' => 'category',
            'type' => 'category',
            'sku' => 'sku',
            'product_code' => 'sku',
            'item_code' => 'sku',
            'code' => 'sku',
            'tax_type' => 'tax_type',
            'tax_category' => 'tax_type',
            'tax_name' => 'tax_type',
            'tax_rate' => 'tax_rate',
            'tax_percent' => 'tax_rate',
            'tax' => 'tax_rate',
            'percentage' => 'tax_rate',
        ];

        // Define payment-specific mapping rules
        $paymentMappingRules = [
            'payment_date' => 'payment_date',
            'date' => 'payment_date',
            'transaction_date' => 'payment_date',
            'paid_date' => 'payment_date',
            'amount' => 'amount',
            'payment_amount' => 'amount',
            'paid_amount' => 'amount',
            'total' => 'amount',
            'transaction_amount' => 'amount',
            'payment_method' => 'payment_method',
            'method' => 'payment_method',
            'type' => 'payment_method',
            'payment_type' => 'payment_method',
            'mode' => 'payment_method',
            'invoice_number' => 'invoice_number',
            'invoice_no' => 'invoice_number',
            'inv_number' => 'invoice_number',
            'invoice_id' => 'invoice_number',
            'related_invoice' => 'invoice_number',
            'customer_name' => 'customer_name',
            'customer' => 'customer_name',
            'payer' => 'customer_name',
            'payer_name' => 'customer_name',
            'reference' => 'reference',
            'transaction_reference' => 'reference',
            'reference_number' => 'reference',
            'ref_number' => 'reference',
            'transaction_id' => 'reference',
            'transaction_number' => 'reference',
            'currency' => 'currency',
            'currency_code' => 'currency',
            'notes' => 'notes',
            'description' => 'notes',
            'memo' => 'notes',
            'comment' => 'notes',
        ];

        // Select mapping rules based on import type
        $mappingRules = match ($importType) {
            'invoices' => array_merge($customerMappingRules, $invoiceMappingRules),
            'items' => $itemMappingRules,
            'payments' => array_merge($customerMappingRules, $paymentMappingRules),
            'expenses' => array_merge($customerMappingRules, $itemMappingRules),
            'complete' => array_merge(
                $customerMappingRules,
                $invoiceMappingRules,
                $itemMappingRules,
                $paymentMappingRules
            ),
            default => $customerMappingRules, // Default to customer rules
        };
        // CLAUDE-CHECKPOINT

        // Apply mapping suggestions to detected fields
        foreach ($detectedFields as $field) {
            $fieldName = $field['name'];
            $normalizedName = strtolower(trim(str_replace([' ', '_', '-'], '_', $fieldName)));

            // Direct match - exact normalized field name
            if (isset($mappingRules[$normalizedName])) {
                $suggestions[$fieldName] = $mappingRules[$normalizedName];
                continue;
            }

            // Fuzzy match - check if any rule key is contained in the field name
            // or if the rule key contains the field name (partial matching)
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
