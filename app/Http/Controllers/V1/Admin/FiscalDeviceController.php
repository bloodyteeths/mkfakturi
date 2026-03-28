<?php

namespace App\Http\Controllers\V1\Admin;

use App\Exceptions\FiscalDeviceException;
use App\Http\Controllers\Controller;
use App\Models\FiscalDevice;
use App\Models\FiscalReceipt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\FiscalDevices\ErpNetFpClient;
use Modules\Mk\Services\FiscalDevices\FiscalDeviceManager;

/**
 * Fiscal Device Controller
 *
 * Manages fiscal device registration, invoice sending, status checks,
 * and daily reconciliation for Macedonian fiscal compliance.
 *
 * Supports all Macedonian fiscal devices: Daisy FX, Давид, Развигорец,
 * Северец, Expert SX, Пелистерец, Alpha — via TCP/IP, serial, Bluetooth,
 * or the ErpNet.FP universal sidecar (auto-discovery).
 */
class FiscalDeviceController extends Controller
{
    public function __construct(
        private FiscalDeviceManager $deviceManager
    ) {
    }

    /**
     * List registered fiscal devices for the company.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        try {
            $devices = FiscalDevice::forCompany($companyId)
                ->withCount('receipts')
                ->orderBy('name')
                ->get();
        } catch (\Illuminate\Database\QueryException $e) {
            // Table may not exist yet if migration hasn't run
            Log::warning('Fiscal devices table query failed', ['error' => $e->getMessage()]);
            $devices = collect();
        }

        return response()->json([
            'data' => $devices,
            'supported_types' => $this->deviceManager->supportedTypesWithLabels(),
        ]);
    }

    /**
     * Register a new fiscal device.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_type' => 'required|string|max:50',
            'name' => 'nullable|string|max:100',
            'serial_number' => 'required|string|max:100',
            'connection_type' => 'nullable|string|in:tcp,serial,bluetooth,erpnet-fp,webserial',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'serial_port' => 'nullable|string|max:100',
        ]);

        $companyId = $request->header('company');

        if (! $this->deviceManager->isSupported($validated['device_type'])) {
            return response()->json([
                'error' => 'Unsupported device type',
                'supported_types' => $this->deviceManager->supportedTypesWithLabels(),
            ], 422);
        }

        // Check for duplicate serial number within company
        $exists = FiscalDevice::where('company_id', $companyId)
            ->where('serial_number', $validated['serial_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'A device with this serial number is already registered',
            ], 422);
        }

        $deviceType = strtolower($validated['device_type']);
        $connectionType = $validated['connection_type']
            ?? $this->deviceManager->getDefaultConnectionType($deviceType);

        $device = FiscalDevice::create([
            'company_id' => $companyId,
            'device_type' => $deviceType,
            'name' => $validated['name'] ?? null,
            'serial_number' => $validated['serial_number'],
            'connection_type' => $connectionType,
            'ip_address' => $validated['ip_address'] ?? null,
            'port' => $validated['port'] ?? null,
            'serial_port' => $validated['serial_port'] ?? null,
            'is_active' => true,
        ]);

        Log::info('Fiscal device registered', [
            'device_id' => $device->id,
            'company_id' => $companyId,
            'type' => $device->device_type,
            'connection' => $device->connection_type,
            'serial' => $device->serial_number,
        ]);

        return response()->json(['data' => $device], 201);
    }

    /**
     * Get a specific fiscal device.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->findOrFail($id);

        return response()->json(['data' => $device->load('receipts')]);
    }

    /**
     * Update a fiscal device.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'connection_type' => 'nullable|string|in:tcp,serial,bluetooth,erpnet-fp,webserial',
            'ip_address' => 'nullable|ip',
            'port' => 'nullable|integer|min:1|max:65535',
            'serial_port' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $device->update($validated);

        return response()->json(['data' => $device]);
    }

    /**
     * Delete a fiscal device.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->findOrFail($id);

        if ($device->receipts()->exists()) {
            return response()->json([
                'error' => 'Cannot delete device with existing receipts. Deactivate it instead.',
            ], 422);
        }

        $device->delete();

        return response()->json(null, 204);
    }

    /**
     * Check the status of a fiscal device.
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->active()->findOrFail($id);

        try {
            $driver = $this->deviceManager->driver($device->device_type);
            $driver->connect($device->getConnectionConfig());

            $status = $driver->getStatus();
            $driver->disconnect();

            return response()->json(['data' => $status]);
        } catch (FiscalDeviceException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'device_type' => $e->getDeviceType(),
            ], 503);
        }
    }

    /**
     * Send an invoice to a fiscal device for fiscalization.
     */
    public function sendInvoice(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->active()->findOrFail($id);

        $validated = $request->validate([
            'invoice_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|integer',
            'items.*.vat_rate' => 'required|numeric',
            'total' => 'required|integer',
            'vat_total' => 'required|integer',
        ]);

        try {
            $driver = $this->deviceManager->driver($device->device_type);
            $driver->connect($device->getConnectionConfig());

            $result = $driver->sendInvoice($validated);
            $driver->disconnect();

            // Store fiscal receipt
            $receipt = FiscalReceipt::create([
                'company_id' => $companyId,
                'fiscal_device_id' => $device->id,
                'invoice_id' => $validated['invoice_id'],
                'receipt_number' => $result['receipt_number'],
                'amount' => $validated['total'],
                'vat_amount' => $validated['vat_total'],
                'fiscal_id' => $result['fiscal_id'],
                'raw_response' => $result['raw_response'] ?? null,
            ]);

            Log::info('Invoice fiscalized', [
                'receipt_id' => $receipt->id,
                'device_id' => $device->id,
                'fiscal_id' => $result['fiscal_id'],
            ]);

            return response()->json(['data' => $receipt], 201);
        } catch (FiscalDeviceException $e) {
            Log::error('Fiscal device error', [
                'device_id' => $device->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'device_type' => $e->getDeviceType(),
            ], 503);
        }
    }

    /**
     * Get daily reconciliation report from the fiscal device.
     */
    public function dailyReport(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->active()->findOrFail($id);

        try {
            $driver = $this->deviceManager->driver($device->device_type);
            $driver->connect($device->getConnectionConfig());

            $report = $driver->getDailyReport();
            $driver->disconnect();

            // Compare with our receipt records
            $ourTotal = FiscalReceipt::forDevice($device->id)
                ->whereDate('created_at', today())
                ->sum('amount');

            $ourVatTotal = FiscalReceipt::forDevice($device->id)
                ->whereDate('created_at', today())
                ->sum('vat_amount');

            $ourCount = FiscalReceipt::forDevice($device->id)
                ->whereDate('created_at', today())
                ->count();

            return response()->json([
                'data' => [
                    'device_report' => $report,
                    'system_totals' => [
                        'total_amount' => $ourTotal,
                        'total_vat' => $ourVatTotal,
                        'receipt_count' => $ourCount,
                    ],
                    'reconciled' => $report['total_amount'] === $ourTotal
                        && $report['receipt_count'] === $ourCount,
                ],
            ]);
        } catch (FiscalDeviceException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'device_type' => $e->getDeviceType(),
            ], 503);
        }
    }

    /**
     * Check ErpNet.FP sidecar health and list discovered printers.
     *
     * Returns sidecar connectivity status and any auto-discovered fiscal
     * printers, so the UI can show a plug-and-play experience.
     */
    public function erpnetStatus(): JsonResponse
    {
        try {
            $client = app(ErpNetFpClient::class);
            $healthy = $client->isHealthy();

            if (! $healthy) {
                return response()->json([
                    'data' => [
                        'connected' => false,
                        'printers' => [],
                        'message' => 'ErpNet.FP sidecar is not reachable',
                    ],
                ]);
            }

            $printers = $client->listPrinters();

            return response()->json([
                'data' => [
                    'connected' => true,
                    'printers' => $printers,
                    'message' => count($printers) > 0
                        ? count($printers).' printer(s) discovered'
                        : 'Sidecar running, no printers connected',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('ErpNet.FP status check failed', ['error' => $e->getMessage()]);

            return response()->json([
                'data' => [
                    'connected' => false,
                    'printers' => [],
                    'message' => 'Could not reach ErpNet.FP sidecar',
                ],
            ]);
        }
    }

    /**
     * List receipts for a device.
     */
    public function receipts(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->findOrFail($id);

        $receipts = $device->receipts()
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($receipts);
    }

    /**
     * Record a fiscal receipt printed via browser WebSerial.
     *
     * The browser communicates directly with the fiscal device via WebSerial API,
     * then POSTs the result here for server-side record-keeping.
     */
    public function recordReceipt(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->findOrFail($id);

        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'receipt_number' => 'required|string|max:50',
            'fiscal_id' => 'required|string|max:100',
            'amount' => 'required|integer',
            'vat_amount' => 'required|integer',
            'raw_response' => 'nullable|string|max:10000',
            'source' => 'required|string|in:webserial,erpnet-fp,manual',
        ]);

        // Prevent duplicate fiscalization of same invoice on same device
        $existingReceipt = FiscalReceipt::where('fiscal_device_id', $device->id)
            ->where('invoice_id', $validated['invoice_id'])
            ->first();

        if ($existingReceipt) {
            return response()->json([
                'error' => 'This invoice has already been fiscalized on this device',
                'existing_receipt' => $existingReceipt,
            ], 422);
        }

        $receipt = FiscalReceipt::create([
            'company_id' => $companyId,
            'fiscal_device_id' => $device->id,
            'invoice_id' => $validated['invoice_id'],
            'receipt_number' => $validated['receipt_number'],
            'amount' => $validated['amount'],
            'vat_amount' => $validated['vat_amount'],
            'fiscal_id' => $validated['fiscal_id'],
            'raw_response' => $validated['raw_response'],
            'source' => $validated['source'],
            'metadata' => json_encode(['source' => $validated['source']]),
        ]);

        Log::info('Fiscal receipt recorded', [
            'receipt_id' => $receipt->id,
            'device_id' => $device->id,
            'invoice_id' => $validated['invoice_id'],
            'fiscal_id' => $validated['fiscal_id'],
            'source' => $validated['source'],
        ]);

        return response()->json(['data' => $receipt], 201);
    }

    /**
     * Record a Z-report printed via browser WebSerial.
     */
    public function recordZReport(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $device = FiscalDevice::forCompany($companyId)->findOrFail($id);

        $validated = $request->validate([
            'report_number' => 'required|string|max:50',
            'total_amount' => 'required|string',
            'total_vat' => 'required|string',
            'receipt_count' => 'required|string',
            'raw_response' => 'nullable|string|max:10000',
            'source' => 'required|string|in:webserial,erpnet-fp,manual',
        ]);

        // Compare with system receipt records for reconciliation
        $systemTotal = FiscalReceipt::forDevice($device->id)
            ->whereDate('created_at', today())
            ->sum('amount');

        $systemCount = FiscalReceipt::forDevice($device->id)
            ->whereDate('created_at', today())
            ->count();

        Log::info('Z-report recorded', [
            'device_id' => $device->id,
            'report_number' => $validated['report_number'],
            'device_total' => $validated['total_amount'],
            'system_total' => $systemTotal,
            'source' => $validated['source'],
        ]);

        return response()->json([
            'data' => [
                'report_number' => $validated['report_number'],
                'device_totals' => [
                    'total_amount' => $validated['total_amount'],
                    'total_vat' => $validated['total_vat'],
                    'receipt_count' => $validated['receipt_count'],
                ],
                'system_totals' => [
                    'total_amount' => $systemTotal,
                    'receipt_count' => $systemCount,
                ],
                'reconciled' => (string) $systemTotal === $validated['total_amount']
                    && (string) $systemCount === $validated['receipt_count'],
            ],
        ]);
    }
    /**
     * List all fiscal receipts for the company (paginated).
     */
    public function allReceipts(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $query = FiscalReceipt::forCompany($companyId)
            ->with(['fiscalDevice:id,name,device_type', 'invoice:id,invoice_number,total,status'])
            ->orderBy($request->get('orderByField', 'created_at'), $request->get('orderBy', 'desc'));

        if ($request->filled('fiscal_device_id')) {
            $query->where('fiscal_device_id', $request->input('fiscal_device_id'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $receipts = $query->paginate($request->get('limit', 25));

        return response()->json($receipts);
    }
}

// CLAUDE-CHECKPOINT
