<?php

namespace Tests\Unit;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Modules\Mk\Services\FiscalDevices\AlphaDriver;
use Modules\Mk\Services\FiscalDevices\DaisyDriver;
use Modules\Mk\Services\FiscalDevices\DavidDriver;
use Modules\Mk\Services\FiscalDevices\ExpertSxDriver;
use Modules\Mk\Services\FiscalDevices\FiscalDeviceManager;
use Modules\Mk\Services\FiscalDevices\PelisterecDriver;
use Modules\Mk\Services\FiscalDevices\RazvigorecDriver;
use Modules\Mk\Services\FiscalDevices\SeverecDriver;
use Tests\TestCase;

/**
 * Unit tests for FiscalDeviceManager and all Macedonian fiscal device drivers.
 *
 * Covers: driver resolution, supported types, labels, connection types,
 * TCP/IP and serial connect behaviour, stub sendInvoice throws.
 */
class FiscalDeviceManagerTest extends TestCase
{
    // ── Manager: Driver resolution ──────────────────────────────────

    public function test_resolves_all_macedonian_device_drivers(): void
    {
        $manager = new FiscalDeviceManager;

        $this->assertInstanceOf(DaisyDriver::class, $manager->driver('daisy'));
        $this->assertInstanceOf(DavidDriver::class, $manager->driver('david'));
        $this->assertInstanceOf(RazvigorecDriver::class, $manager->driver('razvigorec'));
        $this->assertInstanceOf(SeverecDriver::class, $manager->driver('severec'));
        $this->assertInstanceOf(ExpertSxDriver::class, $manager->driver('expert-sx'));
        $this->assertInstanceOf(PelisterecDriver::class, $manager->driver('pelisterec'));
        $this->assertInstanceOf(AlphaDriver::class, $manager->driver('alpha'));
    }

    public function test_all_drivers_implement_interface(): void
    {
        $manager = new FiscalDeviceManager;

        foreach ($manager->supportedTypes() as $type) {
            $driver = $manager->driver($type);
            $this->assertInstanceOf(FiscalDeviceDriver::class, $driver, "Driver '{$type}' must implement FiscalDeviceDriver");
        }
    }

    public function test_driver_resolution_is_case_insensitive(): void
    {
        $manager = new FiscalDeviceManager;

        $this->assertInstanceOf(DaisyDriver::class, $manager->driver('DAISY'));
        $this->assertInstanceOf(DavidDriver::class, $manager->driver('David'));
        $this->assertInstanceOf(RazvigorecDriver::class, $manager->driver('RAZVIGOREC'));
    }

    public function test_unsupported_driver_throws_exception(): void
    {
        $manager = new FiscalDeviceManager;

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('Unsupported fiscal device type: synergy');

        $manager->driver('synergy');
    }

    // ── Manager: Supported types ────────────────────────────────────

    public function test_supported_types_returns_all_seven_devices(): void
    {
        $manager = new FiscalDeviceManager;
        $types = $manager->supportedTypes();

        $this->assertCount(7, $types);
        $this->assertContains('daisy', $types);
        $this->assertContains('david', $types);
        $this->assertContains('razvigorec', $types);
        $this->assertContains('severec', $types);
        $this->assertContains('expert-sx', $types);
        $this->assertContains('pelisterec', $types);
        $this->assertContains('alpha', $types);
    }

    public function test_is_supported_returns_true_for_all_macedonian_devices(): void
    {
        $manager = new FiscalDeviceManager;

        foreach (['daisy', 'david', 'razvigorec', 'severec', 'expert-sx', 'pelisterec', 'alpha'] as $type) {
            $this->assertTrue($manager->isSupported($type), "Device type '{$type}' should be supported");
        }
    }

    public function test_is_supported_returns_false_for_non_macedonian_devices(): void
    {
        $manager = new FiscalDeviceManager;

        $this->assertFalse($manager->isSupported('synergy'));
        $this->assertFalse($manager->isSupported('tremol'));
        $this->assertFalse($manager->isSupported(''));
    }

    // ── Manager: Labels and metadata ────────────────────────────────

    public function test_get_label_returns_macedonian_names(): void
    {
        $manager = new FiscalDeviceManager;

        $this->assertEquals('Daisy FX 1200/1300', $manager->getLabel('daisy'));
        $this->assertEquals('Давид ФП/М/С', $manager->getLabel('david'));
        $this->assertEquals('Развигорец', $manager->getLabel('razvigorec'));
        $this->assertEquals('Северец', $manager->getLabel('severec'));
        $this->assertEquals('Expert SX', $manager->getLabel('expert-sx'));
        $this->assertEquals('Пелистерец', $manager->getLabel('pelisterec'));
        $this->assertEquals('Alpha', $manager->getLabel('alpha'));
    }

    public function test_supported_types_with_labels_returns_full_metadata(): void
    {
        $manager = new FiscalDeviceManager;
        $types = $manager->supportedTypesWithLabels();

        $this->assertCount(7, $types);

        // Check first entry structure
        $daisy = $types[0];
        $this->assertEquals('daisy', $daisy['type']);
        $this->assertEquals('Daisy FX 1200/1300', $daisy['label']);
        $this->assertEquals('tcp', $daisy['default_connection']);
    }

    public function test_default_connection_types_are_correct(): void
    {
        $manager = new FiscalDeviceManager;

        // Fiscal printers default to TCP/IP
        $this->assertEquals('tcp', $manager->getDefaultConnectionType('daisy'));
        $this->assertEquals('tcp', $manager->getDefaultConnectionType('david'));

        // Cash registers default to serial
        $this->assertEquals('serial', $manager->getDefaultConnectionType('razvigorec'));
        $this->assertEquals('serial', $manager->getDefaultConnectionType('severec'));
        $this->assertEquals('serial', $manager->getDefaultConnectionType('expert-sx'));
        $this->assertEquals('serial', $manager->getDefaultConnectionType('alpha'));

        // Pelisterec defaults to bluetooth
        $this->assertEquals('bluetooth', $manager->getDefaultConnectionType('pelisterec'));
    }

    // ── Manager: Extensibility ──────────────────────────────────────

    public function test_extend_registers_custom_driver(): void
    {
        $manager = new FiscalDeviceManager;

        $manager->extend('custom', DaisyDriver::class);

        $this->assertTrue($manager->isSupported('custom'));
        $this->assertContains('custom', $manager->supportedTypes());
    }

    // ── DaisyDriver: TCP/IP and serial ──────────────────────────────

    public function test_daisy_connect_via_tcp(): void
    {
        $driver = new DaisyDriver;

        $result = $driver->connect([
            'connection_type' => 'tcp',
            'ip_address' => '192.168.1.100',
            'port' => 4999,
            'serial_number' => 'DSY-001',
        ]);

        $this->assertTrue($result);

        $status = $driver->getStatus();
        $this->assertEquals('tcp', $status['connection_type']);
        $this->assertEquals('daisy', $status['driver']);
    }

    public function test_daisy_connect_via_serial(): void
    {
        $driver = new DaisyDriver;

        $result = $driver->connect([
            'connection_type' => 'serial',
            'serial_port' => '/dev/ttyUSB0',
            'serial_number' => 'DSY-002',
        ]);

        $this->assertTrue($result);

        $status = $driver->getStatus();
        $this->assertEquals('serial', $status['connection_type']);
    }

    public function test_daisy_tcp_throws_without_ip(): void
    {
        $driver = new DaisyDriver;

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('IP address is required');

        $driver->connect(['connection_type' => 'tcp', 'serial_number' => 'DSY-001']);
    }

    public function test_daisy_serial_throws_without_port_path(): void
    {
        $driver = new DaisyDriver;

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('Serial port path is required');

        $driver->connect(['connection_type' => 'serial', 'serial_number' => 'DSY-001']);
    }

    public function test_daisy_send_invoice_throws_not_implemented(): void
    {
        $driver = new DaisyDriver;
        $driver->connect(['ip_address' => '192.168.1.100', 'serial_number' => 'DSY-001']);

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('awaiting UJP');

        $driver->sendInvoice(['items' => [['name' => 'Test', 'quantity' => 1, 'price' => 100, 'vat_rate' => 18]], 'total' => 100, 'vat_total' => 18]);
    }

    public function test_daisy_operations_throw_when_not_connected(): void
    {
        $driver = new DaisyDriver;

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('Not connected');

        $driver->getStatus();
    }

    public function test_daisy_disconnect_resets_state(): void
    {
        $driver = new DaisyDriver;
        $driver->connect(['ip_address' => '192.168.1.100', 'serial_number' => 'DSY-001']);
        $driver->disconnect();

        $this->expectException(FiscalDeviceException::class);
        $driver->getStatus();
    }

    public function test_daisy_driver_name(): void
    {
        $this->assertEquals('daisy', (new DaisyDriver)->getDriverName());
    }

    // ── DavidDriver ─────────────────────────────────────────────────

    public function test_david_connect_via_tcp(): void
    {
        $driver = new DavidDriver;
        $this->assertTrue($driver->connect(['ip_address' => '192.168.1.200', 'serial_number' => 'DVD-001']));
        $this->assertEquals('david', $driver->getStatus()['driver']);
        $this->assertEquals('Давид ФП/М/С', $driver->getStatus()['device']);
    }

    public function test_david_connect_via_serial(): void
    {
        $driver = new DavidDriver;
        $this->assertTrue($driver->connect(['connection_type' => 'serial', 'serial_port' => 'COM3', 'serial_number' => 'DVS-001']));
        $this->assertEquals('serial', $driver->getStatus()['connection_type']);
    }

    public function test_david_send_invoice_throws(): void
    {
        $driver = new DavidDriver;
        $driver->connect(['ip_address' => '192.168.1.200', 'serial_number' => 'DVD-001']);

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('awaiting UJP');

        $driver->sendInvoice(['items' => [], 'total' => 100]);
    }

    public function test_david_driver_name(): void
    {
        $this->assertEquals('david', (new DavidDriver)->getDriverName());
    }

    // ── RazvigorecDriver ────────────────────────────────────────────

    public function test_razvigorec_connect_via_serial(): void
    {
        $driver = new RazvigorecDriver;
        $this->assertTrue($driver->connect(['serial_port' => '/dev/ttyUSB0', 'serial_number' => 'RZV-001']));
        $this->assertEquals('razvigorec', $driver->getStatus()['driver']);
        $this->assertEquals('Развигорец', $driver->getStatus()['device']);
        $this->assertEquals('serial', $driver->getStatus()['connection_type']);
    }

    public function test_razvigorec_serial_throws_without_port(): void
    {
        $driver = new RazvigorecDriver;

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('Serial port path is required');

        $driver->connect(['serial_number' => 'RZV-001']);
    }

    public function test_razvigorec_driver_name(): void
    {
        $this->assertEquals('razvigorec', (new RazvigorecDriver)->getDriverName());
    }

    // ── SeverecDriver ───────────────────────────────────────────────

    public function test_severec_connect_via_serial(): void
    {
        $driver = new SeverecDriver;
        $this->assertTrue($driver->connect(['serial_port' => 'COM1', 'serial_number' => 'SVR-001']));
        $this->assertEquals('severec', $driver->getStatus()['driver']);
        $this->assertEquals('Северец', $driver->getStatus()['device']);
    }

    public function test_severec_driver_name(): void
    {
        $this->assertEquals('severec', (new SeverecDriver)->getDriverName());
    }

    // ── ExpertSxDriver ──────────────────────────────────────────────

    public function test_expert_sx_connect_via_serial(): void
    {
        $driver = new ExpertSxDriver;
        $this->assertTrue($driver->connect(['serial_port' => '/dev/ttyS0', 'serial_number' => 'EXP-001']));
        $this->assertEquals('expert-sx', $driver->getStatus()['driver']);
        $this->assertEquals('Expert SX', $driver->getStatus()['device']);
    }

    public function test_expert_sx_connect_via_tcp(): void
    {
        $driver = new ExpertSxDriver;
        $this->assertTrue($driver->connect(['connection_type' => 'tcp', 'ip_address' => '10.0.0.50', 'serial_number' => 'EXP-002']));
        $this->assertEquals('tcp', $driver->getStatus()['connection_type']);
    }

    public function test_expert_sx_driver_name(): void
    {
        $this->assertEquals('expert-sx', (new ExpertSxDriver)->getDriverName());
    }

    // ── PelisterecDriver ────────────────────────────────────────────

    public function test_pelisterec_connect_via_bluetooth(): void
    {
        $driver = new PelisterecDriver;
        $this->assertTrue($driver->connect(['serial_port' => '/dev/rfcomm0', 'serial_number' => 'PEL-001']));
        $this->assertEquals('pelisterec', $driver->getStatus()['driver']);
        $this->assertEquals('Пелистерец', $driver->getStatus()['device']);
        $this->assertEquals('bluetooth', $driver->getStatus()['connection_type']);
    }

    public function test_pelisterec_throws_without_port(): void
    {
        $driver = new PelisterecDriver;

        $this->expectException(FiscalDeviceException::class);
        $this->expectExceptionMessage('Serial/Bluetooth port path is required');

        $driver->connect(['serial_number' => 'PEL-001']);
    }

    public function test_pelisterec_driver_name(): void
    {
        $this->assertEquals('pelisterec', (new PelisterecDriver)->getDriverName());
    }

    // ── AlphaDriver ─────────────────────────────────────────────────

    public function test_alpha_connect_via_serial(): void
    {
        $driver = new AlphaDriver;
        $this->assertTrue($driver->connect(['serial_port' => 'COM2', 'serial_number' => 'ALF-001']));
        $this->assertEquals('alpha', $driver->getStatus()['driver']);
        $this->assertEquals('Alpha', $driver->getStatus()['device']);
    }

    public function test_alpha_connect_via_tcp(): void
    {
        $driver = new AlphaDriver;
        $this->assertTrue($driver->connect(['connection_type' => 'tcp', 'ip_address' => '192.168.0.10', 'serial_number' => 'ALF-002']));
        $this->assertEquals('tcp', $driver->getStatus()['connection_type']);
    }

    public function test_alpha_driver_name(): void
    {
        $this->assertEquals('alpha', (new AlphaDriver)->getDriverName());
    }

    // ── FiscalDeviceException ───────────────────────────────────────

    public function test_exception_carries_device_type(): void
    {
        $exception = new FiscalDeviceException('Test error', 'daisy', 'DSY-001');

        $this->assertEquals('daisy', $exception->getDeviceType());
        $this->assertEquals('DSY-001', $exception->getDeviceSerial());
        $this->assertEquals('Test error', $exception->getMessage());
    }

    public function test_exception_defaults(): void
    {
        $exception = new FiscalDeviceException('Error');

        $this->assertEquals('unknown', $exception->getDeviceType());
        $this->assertNull($exception->getDeviceSerial());
    }
}
// CLAUDE-CHECKPOINT
