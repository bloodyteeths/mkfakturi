<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;
use Illuminate\Support\Facades\Log;

/**
 * Давид (David) Fiscal Device Driver
 *
 * Supports the David product family sold in North Macedonia by Fiditek:
 * - Давид ФП (David FP) — fiscal printer for high-volume POS systems
 * - Давид М (David M) — cash register for shops, pharmacies, offices
 * - Давид С (David S) — mobile cash register for vehicles, market stands, courier
 *
 * Connection types:
 * - TCP/IP on configurable port (default 4999)
 * - RS232 serial (9600 baud, 8N1)
 *
 * Protocol: Similar binary framing to Daisy FX series.
 * Device auto-sends receipt data to UJP via built-in GPRS/crypto module.
 *
 * NOTE: Actual protocol implementation pending — requires physical test device.
 */
class DavidDriver implements FiscalDeviceDriver
{
    private ?string $ipAddress = null;

    private int $port = 4999;

    private ?string $serialNumber = null;

    private ?string $serialPort = null;

    private string $connectionType = 'tcp';

    private bool $connected = false;

    public function connect(array $config): bool
    {
        $this->connectionType = $config['connection_type'] ?? 'tcp';
        $this->serialNumber = $config['serial_number'] ?? null;

        if ($this->connectionType === 'serial') {
            $this->serialPort = $config['serial_port'] ?? null;

            if (! $this->serialPort) {
                throw new FiscalDeviceException(
                    'Serial port path is required for David device (e.g. /dev/ttyUSB0 or COM3)',
                    'david',
                    $this->serialNumber
                );
            }

            Log::info('David: Connecting via serial', [
                'port' => $this->serialPort,
                'serial' => $this->serialNumber,
            ]);
        } else {
            $this->ipAddress = $config['ip_address'] ?? null;
            $this->port = $config['port'] ?? 4999;

            if (! $this->ipAddress) {
                throw new FiscalDeviceException(
                    'IP address is required for David TCP/IP connection',
                    'david',
                    $this->serialNumber
                );
            }

            Log::info('David: Connecting via TCP/IP', [
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
            'David fiscal device protocol not yet implemented — awaiting UJP device API specs',
            'david',
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
            'driver' => 'david',
            'device' => 'Давид ФП/М/С',
            'serial' => $this->serialNumber,
            'connection_type' => $this->connectionType,
            'note' => 'Status check stub — full implementation pending UJP specs',
        ];
    }

    public function getLastReceipt(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'David getLastReceipt not yet implemented',
            'david',
            $this->serialNumber
        );
    }

    public function getDailyReport(): array
    {
        $this->ensureConnected();

        throw new FiscalDeviceException(
            'David getDailyReport not yet implemented',
            'david',
            $this->serialNumber
        );
    }

    public function disconnect(): void
    {
        $this->connected = false;
        Log::info('David: Disconnected', ['serial' => $this->serialNumber]);
    }

    public function getDriverName(): string
    {
        return 'david';
    }

    private function ensureConnected(): void
    {
        if (! $this->connected) {
            throw new FiscalDeviceException(
                'Not connected to David device. Call connect() first.',
                'david',
                $this->serialNumber
            );
        }
    }
}
// CLAUDE-CHECKPOINT
