<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Log;

/**
 * Пелистерец (Pelisterec) Fiscal Cash Register Driver
 *
 * Mobile fiscal cash register with Bluetooth, sold in North Macedonia by Fiditek.
 * Designed for mobile businesses: delivery, field sales, market vendors.
 *
 * Features:
 * - Compact and ergonomic mobile design
 * - Bluetooth communication with POS software
 * - Built-in GPRS terminal (auto-sends to UJP)
 * - Crypto module + SIM card + SD card
 * - Rechargeable battery
 *
 * Connection types:
 * - Bluetooth (primary — via virtual serial port)
 * - RS232 serial (when docked)
 *
 * NOTE: Actual protocol implementation pending — requires physical test device.
 */
class PelisterecDriver implements FiscalDeviceDriver
{
    private ?string $serialNumber = null;

    private ?string $serialPort = null;

    private string $connectionType = 'bluetooth';

    private bool $connected = false;

    public function connect(array $config): bool
    {
        $this->connectionType = $config['connection_type'] ?? 'bluetooth';
        $this->serialNumber = $config['serial_number'] ?? null;
        $this->serialPort = $config['serial_port'] ?? null;

        if (! $this->serialPort) {
            throw new FiscalDeviceException(
                'Serial/Bluetooth port path is required for Пелистерец (e.g. /dev/rfcomm0 or COM5)',
                'pelisterec',
                $this->serialNumber
            );
        }

        $connectionLabel = $this->connectionType === 'bluetooth' ? 'Bluetooth' : 'serial';

        Log::info("Пелистерец: Connecting via {$connectionLabel}", [
            'port' => $this->serialPort,
            'serial' => $this->serialNumber,
        ]);

        $this->connected = true;

        return true;
    }

    public function sendInvoice(array $invoiceData): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Пелистерец protocol not yet implemented — awaiting UJP device API specs',
            'pelisterec',
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
            'driver' => 'pelisterec',
            'device' => 'Пелистерец',
            'serial' => $this->serialNumber,
            'connection_type' => $this->connectionType,
            'note' => 'Status check stub — full implementation pending UJP specs',
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Пелистерец getLastReceipt not yet implemented',
            'pelisterec',
            $this->serialNumber
        );
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'Пелистерец getDailyReport not yet implemented',
            'pelisterec',
            $this->serialNumber
        );
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info('Пелистерец: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'pelisterec';
    }

    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new FiscalDeviceException(
                'Not connected to Пелистерец. Call connect() first.',
                'pelisterec',
                $this->serialNumber
            );
        }
    }
}
