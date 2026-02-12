<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Log;

/**
 * Северец (Severec) Fiscal Cash Register Driver
 *
 * Popular entry-level fiscal cash register sold in North Macedonia.
 * Suitable for small shops, services, and low-volume retail.
 *
 * Features:
 * - Built-in GPRS terminal (auto-sends to UJP)
 * - Crypto module + SIM card + SD card
 * - Compact design with basic keypad
 *
 * Connection types:
 * - RS232 serial (primary)
 * - TCP/IP via optional LAN adapter
 *
 * NOTE: Actual protocol implementation pending — requires physical test device.
 */
class SeverecDriver implements FiscalDeviceDriver
{
    private ?string $ipAddress = null;

    private int $port = 4999;

    private ?string $serialNumber = null;

    private ?string $serialPort = null;

    private string $connectionType = 'serial';

    private bool $connected = false;

    public function connect(array $config): bool
    {
        $this->connectionType = $config['connection_type'] ?? 'serial';
        $this->serialNumber = $config['serial_number'] ?? null;

        if ($this->connectionType === 'serial') {
            $this->serialPort = $config['serial_port'] ?? null;

            if (! $this->serialPort) {
                throw new FiscalDeviceException(
                    'Serial port path is required for Северец (e.g. /dev/ttyUSB0 or COM3)',
                    'severec',
                    $this->serialNumber
                );
            }

            Log::info('Северец: Connecting via serial', [
                'port' => $this->serialPort,
                'serial' => $this->serialNumber,
            ]);
        } else {
            $this->ipAddress = $config['ip_address'] ?? null;
            $this->port = $config['port'] ?? 4999;

            if (! $this->ipAddress) {
                throw new FiscalDeviceException(
                    'IP address is required for Северец TCP/IP connection',
                    'severec',
                    $this->serialNumber
                );
            }

            Log::info('Северец: Connecting via TCP/IP', [
                'ip' => $this->ipAddress,
                'port' => $this->port,
                'serial' => $this->serialNumber,
            ]);
        }

        $this->connected = true;

        return true;
    }

    public function sendInvoice(array $invoiceData): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Северец protocol not yet implemented — awaiting UJP device API specs',
            'severec',
            $this->serialNumber
        );
    }

    public function getStatus(): array
    {
        $this->ensureConnected();

        return [
            'connected' => $this->connected,
            'paper' => true,
            'fiscal_memory' => 'unknown',
            'last_receipt' => null,
            'errors' => [],
            'driver' => 'severec',
            'device' => 'Северец',
            'serial' => $this->serialNumber,
            'connection_type' => $this->connectionType,
            'note' => 'Status check stub — full implementation pending UJP specs',
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Северец getLastReceipt not yet implemented',
            'severec',
            $this->serialNumber
        );
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Северец getDailyReport not yet implemented',
            'severec',
            $this->serialNumber
        );
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info('Северец: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'severec';
    }

    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new FiscalDeviceException(
                'Not connected to Северец. Call connect() first.',
                'severec',
                $this->serialNumber
            );
        }
    }
}
// CLAUDE-CHECKPOINT
