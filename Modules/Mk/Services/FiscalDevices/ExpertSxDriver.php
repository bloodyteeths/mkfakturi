<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Log;

/**
 * Expert SX Fiscal Cash Register Driver
 *
 * Dual-mode (stationary + mobile) fiscal cash register sold in North Macedonia.
 * Popular with businesses that need both counter and field operation.
 *
 * Features:
 * - Stationary mode with full keypad and display
 * - Mobile mode with rechargeable battery
 * - Built-in GPRS terminal (auto-sends to UJP)
 * - Crypto module + SIM card + SD card
 *
 * Connection types:
 * - RS232 serial
 * - TCP/IP via LAN adapter
 *
 * NOTE: Actual protocol implementation pending — requires physical test device.
 */
class ExpertSxDriver implements FiscalDeviceDriver
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
                    'Serial port path is required for Expert SX (e.g. /dev/ttyUSB0 or COM3)',
                    'expert-sx',
                    $this->serialNumber
                );
            }

            Log::info('Expert SX: Connecting via serial', [
                'port' => $this->serialPort,
                'serial' => $this->serialNumber,
            ]);
        } else {
            $this->ipAddress = $config['ip_address'] ?? null;
            $this->port = $config['port'] ?? 4999;

            if (! $this->ipAddress) {
                throw new FiscalDeviceException(
                    'IP address is required for Expert SX TCP/IP connection',
                    'expert-sx',
                    $this->serialNumber
                );
            }

            Log::info('Expert SX: Connecting via TCP/IP', [
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
            'Expert SX protocol not yet implemented — awaiting UJP device API specs',
            'expert-sx',
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
            'driver' => 'expert-sx',
            'device' => 'Expert SX',
            'serial' => $this->serialNumber,
            'connection_type' => $this->connectionType,
            'note' => 'Status check stub — full implementation pending UJP specs',
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Expert SX getLastReceipt not yet implemented',
            'expert-sx',
            $this->serialNumber
        );
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Expert SX getDailyReport not yet implemented',
            'expert-sx',
            $this->serialNumber
        );
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info('Expert SX: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'expert-sx';
    }

    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new FiscalDeviceException(
                'Not connected to Expert SX. Call connect() first.',
                'expert-sx',
                $this->serialNumber
            );
        }
    }
}
