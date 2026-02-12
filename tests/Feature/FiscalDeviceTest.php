<?php

use App\Models\FiscalDevice;
use App\Models\FiscalReceipt;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use Modules\Mk\Services\FiscalDevices\FiscalDeviceManager;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->company = $user->companies()->first();
    $this->user = $user;
    $this->withHeaders([
        'company' => $this->company->id,
    ]);
    Sanctum::actingAs($user, ['*']);
});

// Helper: create a fiscal device
function createFiscalDevice($companyId, $overrides = []): FiscalDevice
{
    return FiscalDevice::create(array_merge([
        'company_id' => $companyId,
        'device_type' => 'daisy',
        'name' => 'Test Fiscal Device',
        'serial_number' => 'DSY-' . uniqid(),
        'connection_type' => 'tcp',
        'ip_address' => '192.168.1.100',
        'port' => 4999,
        'is_active' => true,
    ], $overrides));
}

// Helper: create a fiscal receipt
function createFiscalReceipt($companyId, $deviceId, $overrides = []): FiscalReceipt
{
    return FiscalReceipt::create(array_merge([
        'company_id' => $companyId,
        'fiscal_device_id' => $deviceId,
        'invoice_id' => 1,
        'receipt_number' => 'RCP-' . uniqid(),
        'amount' => 10000,
        'vat_amount' => 1800,
        'fiscal_id' => 'FSC-' . uniqid(),
    ], $overrides));
}

describe('Fiscal Device CRUD', function () {
    test('can list fiscal devices for company', function () {
        createFiscalDevice($this->company->id, ['serial_number' => 'DSY-001', 'name' => 'Device A']);
        createFiscalDevice($this->company->id, ['serial_number' => 'DSY-002', 'name' => 'Device B']);

        $response = getJson('api/v1/fiscal-devices');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'supported_types',
        ]);

        // supported_types now returns array of objects with type/label/default_connection
        $types = $response->json('supported_types');
        expect(count($types))->toBe(7);
        expect($types[0])->toHaveKeys(['type', 'label', 'default_connection']);
        expect(count($response->json('data')))->toBe(2);
    });

    test('can register a new daisy fiscal printer via TCP/IP', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'daisy',
            'name' => 'Office Printer',
            'serial_number' => 'DSY-NEW-001',
            'connection_type' => 'tcp',
            'ip_address' => '192.168.1.50',
            'port' => 4999,
        ]);

        $response->assertStatus(201);
        expect($response->json('data.device_type'))->toBe('daisy');
        expect($response->json('data.connection_type'))->toBe('tcp');
        expect($response->json('data.serial_number'))->toBe('DSY-NEW-001');
        expect($response->json('data.is_active'))->toBeTrue();

        $this->assertDatabaseHas('fiscal_devices', [
            'company_id' => $this->company->id,
            'serial_number' => 'DSY-NEW-001',
            'device_type' => 'daisy',
            'connection_type' => 'tcp',
        ]);
    });

    test('can register a razvigorec cash register via serial', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'razvigorec',
            'name' => 'Каса Развигорец',
            'serial_number' => 'RZV-NEW-001',
            'connection_type' => 'serial',
            'serial_port' => '/dev/ttyUSB0',
        ]);

        $response->assertStatus(201);
        expect($response->json('data.device_type'))->toBe('razvigorec');
        expect($response->json('data.connection_type'))->toBe('serial');
        expect($response->json('data.serial_port'))->toBe('/dev/ttyUSB0');
    });

    test('can register a pelisterec via bluetooth', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'pelisterec',
            'name' => 'Мобилен Пелистерец',
            'serial_number' => 'PEL-NEW-001',
            'connection_type' => 'bluetooth',
            'serial_port' => '/dev/rfcomm0',
        ]);

        $response->assertStatus(201);
        expect($response->json('data.device_type'))->toBe('pelisterec');
        expect($response->json('data.connection_type'))->toBe('bluetooth');
    });

    test('defaults to correct connection_type when not specified', function () {
        // Daisy defaults to tcp
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'daisy',
            'serial_number' => 'DSY-DEFAULT-001',
            'ip_address' => '192.168.1.50',
        ]);
        $response->assertStatus(201);
        expect($response->json('data.connection_type'))->toBe('tcp');

        // Severec defaults to serial
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'severec',
            'serial_number' => 'SVR-DEFAULT-001',
            'serial_port' => 'COM1',
        ]);
        $response->assertStatus(201);
        expect($response->json('data.connection_type'))->toBe('serial');
    });

    test('rejects unsupported device type', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'synergy',
            'serial_number' => 'SYN-001',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['error', 'supported_types']);
    });

    test('rejects duplicate serial number within company', function () {
        createFiscalDevice($this->company->id, ['serial_number' => 'DSY-DUP-001']);

        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'daisy',
            'serial_number' => 'DSY-DUP-001',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'A device with this serial number is already registered',
        ]);
    });

    test('can show a specific fiscal device', function () {
        $device = createFiscalDevice($this->company->id, ['serial_number' => 'DSY-SHOW-001']);

        $response = getJson("api/v1/fiscal-devices/{$device->id}");

        $response->assertOk();
        expect($response->json('data.id'))->toBe($device->id);
        expect($response->json('data.serial_number'))->toBe('DSY-SHOW-001');
    });

    test('can update a fiscal device including connection fields', function () {
        $device = createFiscalDevice($this->company->id, ['serial_number' => 'DSY-UPD-001']);

        $response = patchJson("api/v1/fiscal-devices/{$device->id}", [
            'name' => 'Updated Name',
            'connection_type' => 'serial',
            'serial_port' => '/dev/ttyS0',
            'is_active' => false,
        ]);

        $response->assertOk();
        expect($response->json('data.name'))->toBe('Updated Name');
        expect($response->json('data.connection_type'))->toBe('serial');
        expect($response->json('data.serial_port'))->toBe('/dev/ttyS0');
        expect($response->json('data.is_active'))->toBeFalse();
    });

    test('can delete a fiscal device without receipts', function () {
        $device = createFiscalDevice($this->company->id, ['serial_number' => 'DSY-DEL-001']);

        $response = deleteJson("api/v1/fiscal-devices/{$device->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('fiscal_devices', ['id' => $device->id]);
    });

    test('cannot delete device with existing receipts', function () {
        $device = createFiscalDevice($this->company->id, ['serial_number' => 'DSY-NODELETE-001']);
        createFiscalReceipt($this->company->id, $device->id);

        $response = deleteJson("api/v1/fiscal-devices/{$device->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'error' => 'Cannot delete device with existing receipts. Deactivate it instead.',
        ]);
    });
});

describe('Fiscal Device Operations', function () {
    test('can check device status', function () {
        $device = createFiscalDevice($this->company->id, [
            'serial_number' => 'DSY-STATUS-001',
            'ip_address' => '192.168.1.100',
        ]);

        $response = getJson("api/v1/fiscal-devices/{$device->id}/status");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['connected', 'paper', 'driver', 'serial', 'connection_type'],
        ]);

        expect($response->json('data.connected'))->toBeTrue();
        expect($response->json('data.driver'))->toBe('daisy');
        expect($response->json('data.connection_type'))->toBe('tcp');
    });

    test('status check fails for inactive device', function () {
        $device = createFiscalDevice($this->company->id, [
            'serial_number' => 'DSY-INACTIVE-001',
            'is_active' => false,
        ]);

        $response = getJson("api/v1/fiscal-devices/{$device->id}/status");

        $response->assertStatus(404);
    });

    test('send invoice returns 503 for stub drivers', function () {
        $device = createFiscalDevice($this->company->id, [
            'serial_number' => 'DSY-SEND-001',
            'ip_address' => '192.168.1.100',
        ]);

        $response = postJson("api/v1/fiscal-devices/{$device->id}/send-invoice", [
            'invoice_id' => 1,
            'items' => [
                [
                    'name' => 'Product A',
                    'quantity' => 2,
                    'price' => 5000,
                    'vat_rate' => 18,
                ],
            ],
            'total' => 10000,
            'vat_total' => 1800,
        ]);

        // Stub driver throws FiscalDeviceException -> 503
        $response->assertStatus(503);
        $response->assertJsonStructure(['error', 'device_type']);
        expect($response->json('device_type'))->toBe('daisy');
    });

    test('daily report returns 503 for stub drivers', function () {
        $device = createFiscalDevice($this->company->id, [
            'serial_number' => 'DSY-REPORT-001',
            'ip_address' => '192.168.1.100',
        ]);

        $response = getJson("api/v1/fiscal-devices/{$device->id}/daily-report");

        $response->assertStatus(503);
        $response->assertJsonStructure(['error', 'device_type']);
    });
});

describe('Fiscal Device Receipts', function () {
    test('can list receipts for a device', function () {
        $device = createFiscalDevice($this->company->id, ['serial_number' => 'DSY-RCP-001']);
        createFiscalReceipt($this->company->id, $device->id);
        createFiscalReceipt($this->company->id, $device->id);
        createFiscalReceipt($this->company->id, $device->id);

        $response = getJson("api/v1/fiscal-devices/{$device->id}/receipts");

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
        ]);

        expect($response->json('total'))->toBe(3);
    });
});

describe('Fiscal Device Validation', function () {
    test('store requires serial_number', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'daisy',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['serial_number']);
    });

    test('store requires device_type', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'serial_number' => 'DSY-VAL-001',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['device_type']);
    });

    test('store validates ip_address format', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'daisy',
            'serial_number' => 'DSY-IPVAL-001',
            'ip_address' => 'not-an-ip',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['ip_address']);
    });

    test('store validates port range', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'daisy',
            'serial_number' => 'DSY-PORTVAL-001',
            'port' => 99999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['port']);
    });

    test('store validates connection_type enum', function () {
        $response = postJson('api/v1/fiscal-devices', [
            'device_type' => 'daisy',
            'serial_number' => 'DSY-CONN-001',
            'connection_type' => 'usb',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['connection_type']);
    });

    test('send-invoice validates required fields', function () {
        $device = createFiscalDevice($this->company->id, [
            'serial_number' => 'DSY-SNDVAL-001',
            'ip_address' => '192.168.1.100',
        ]);

        $response = postJson("api/v1/fiscal-devices/{$device->id}/send-invoice", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['invoice_id', 'items', 'total', 'vat_total']);
    });
});

describe('All Macedonian Device Types', function () {
    test('can register each macedonian device type', function () {
        $devices = [
            ['type' => 'daisy', 'serial' => 'DSY-ALL-001', 'ip_address' => '192.168.1.1'],
            ['type' => 'david', 'serial' => 'DVD-ALL-001', 'ip_address' => '192.168.1.2'],
            ['type' => 'razvigorec', 'serial' => 'RZV-ALL-001', 'serial_port' => '/dev/ttyUSB0'],
            ['type' => 'severec', 'serial' => 'SVR-ALL-001', 'serial_port' => 'COM1'],
            ['type' => 'expert-sx', 'serial' => 'EXP-ALL-001', 'serial_port' => '/dev/ttyS0'],
            ['type' => 'pelisterec', 'serial' => 'PEL-ALL-001', 'serial_port' => '/dev/rfcomm0'],
            ['type' => 'alpha', 'serial' => 'ALF-ALL-001', 'serial_port' => 'COM2'],
        ];

        foreach ($devices as $device) {
            $payload = [
                'device_type' => $device['type'],
                'serial_number' => $device['serial'],
            ];
            if (isset($device['ip_address'])) {
                $payload['ip_address'] = $device['ip_address'];
            }
            if (isset($device['serial_port'])) {
                $payload['serial_port'] = $device['serial_port'];
            }

            $response = postJson('api/v1/fiscal-devices', $payload);

            $response->assertStatus(201, "Failed to register device type: {$device['type']}");
            expect($response->json('data.device_type'))->toBe($device['type']);
        }

        // Verify all 7 are in DB
        $this->assertDatabaseCount('fiscal_devices', 7);
    });
});

describe('Fiscal Device Manager Singleton', function () {
    test('manager is registered as singleton in service container', function () {
        $manager1 = app(FiscalDeviceManager::class);
        $manager2 = app(FiscalDeviceManager::class);

        expect($manager1)->toBe($manager2);
        expect($manager1)->toBeInstanceOf(FiscalDeviceManager::class);
    });
});

// CLAUDE-CHECKPOINT
