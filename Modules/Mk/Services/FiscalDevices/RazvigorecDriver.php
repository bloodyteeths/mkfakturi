<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Log;

/**
 * Развигорец (Razvigorec) Fiscal Cash Register Driver
 *
 * Popular mobile fiscal cash register sold by Fiditek in North Macedonia.
 * Designed for small companies with sales points, field billing, distributors,
 * ambulatory sales, ticketing offices, and mobile payment points.
 *
 * Features:
 * - Built-in GPRS terminal (auto-sends to UJP)
 * - Crypto module + SIM card + SD card
 * - Compact and portable design
 *
 * Connection types:
 * - RS232 serial (primary — most common for mobile registers)
 * - TCP/IP via optional LAN adapter
 *
 * NOTE: Actual protocol implementation pending — requires physical test device.
 */
class RazvigorecDriver implements FiscalDeviceDriver
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
                    'Serial port path is required for Развигорец (e.g. /dev/ttyUSB0 or COM3)',
                    'razvigorec',
                    $this->serialNumber
                );
            }

            Log::info('Развигорец: Connecting via serial', [
                'port' => $this->serialPort,
                'serial' => $this->serialNumber,
            ]);
        } else {
            $this->ipAddress = $config['ip_address'] ?? null;
            $this->port = $config['port'] ?? 4999;

            if (! $this->ipAddress) {
                throw new FiscalDeviceException(
                    'IP address is required for Развигорец TCP/IP connection',
                    'razvigorec',
                    $this->serialNumber
                );
            }

            Log::info('Развигорец: Connecting via TCP/IP', [
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
            'Развигорец protocol not yet implemented — awaiting UJP device API specs',
            'razvigorec',
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
            'driver' => 'razvigorec',
            'device' => 'Развигорец',
            'serial' => $this->serialNumber,
            'connection_type' => $this->connectionType,
            'note' => 'Status check stub — full implementation pending UJP specs',
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Развигорец getLastReceipt not yet implemented',
            'razvigorec',
            $this->serialNumber
        );
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Развигорец getDailyReport not yet implemented',
            'razvigorec',
            $this->serialNumber
        );
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info('Развигорец: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'razvigorec';
    }

    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new FiscalDeviceException(
                'Not connected to Развигорец. Call connect() first.',
                'razvigorec',
                $this->serialNumber
            );
        }
    }
}
