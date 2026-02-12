<?php

namespace Modules\Mk\Services\FiscalDevices;

use App\Contracts\FiscalDeviceDriver;
use App\Exceptions\FiscalDeviceException;

/**
 * Fiscal Device Manager
 *
 * Factory that resolves the appropriate fiscal device driver
 * based on device type. Covers all fiscal devices approved and
 * sold in North Macedonia (distributed by Fiditek, Скопје).
 *
 * Supported devices:
 * - Daisy FX 1200/1300 (fiscal printers — most popular for POS integration)
 * - Давид ФП/М/С (David — fiscal printer + cash registers)
 * - Развигорец (Razvigorec — mobile cash register)
 * - Северец (Severec — entry-level cash register)
 * - Expert SX (dual stationary/mobile cash register)
 * - Пелистерец (Pelisterec — Bluetooth mobile cash register)
 * - Alpha (standard cash register)
 */
class FiscalDeviceManager
{
    /** @var array<string, class-string<FiscalDeviceDriver>> */
    private array $drivers = [
        'daisy' => DaisyDriver::class,
        'david' => DavidDriver::class,
        'razvigorec' => RazvigorecDriver::class,
        'severec' => SeverecDriver::class,
        'expert-sx' => ExpertSxDriver::class,
        'pelisterec' => PelisterecDriver::class,
        'alpha' => AlphaDriver::class,
        'erpnet-fp' => ErpNetFpDriver::class,
    ];

    /** @var array<string, string> Human-readable device names (Macedonian) */
    private array $deviceLabels = [
        'daisy' => 'Daisy FX 1200/1300',
        'david' => 'Давид ФП/М/С',
        'razvigorec' => 'Развигорец',
        'severec' => 'Северец',
        'expert-sx' => 'Expert SX',
        'pelisterec' => 'Пелистерец',
        'alpha' => 'Alpha',
        'erpnet-fp' => 'ErpNet.FP (Universal)',
    ];

    /** @var array<string, string> Default connection type per device */
    private array $defaultConnectionTypes = [
        'daisy' => 'tcp',
        'david' => 'tcp',
        'razvigorec' => 'serial',
        'severec' => 'serial',
        'expert-sx' => 'serial',
        'pelisterec' => 'bluetooth',
        'alpha' => 'serial',
        'erpnet-fp' => 'tcp',
    ];

    /**
     * Resolve a fiscal device driver by type.
     *
     * @param  string  $deviceType  e.g., 'daisy', 'david', 'razvigorec'
     *
     * @throws FiscalDeviceException If driver type is unsupported
     */
    public function driver(string $deviceType): FiscalDeviceDriver
    {
        $type = strtolower($deviceType);

        if (! isset($this->drivers[$type])) {
            throw new FiscalDeviceException(
                "Unsupported fiscal device type: {$deviceType}. Supported: ".implode(', ', array_keys($this->drivers)),
                $deviceType
            );
        }

        $driverClass = $this->drivers[$type];

        return new $driverClass;
    }

    /**
     * Register a custom driver.
     *
     * @param  string  $type  Driver identifier
     * @param  class-string<FiscalDeviceDriver>  $driverClass
     */
    public function extend(string $type, string $driverClass): void
    {
        $this->drivers[strtolower($type)] = $driverClass;
    }

    /**
     * Get list of supported device types.
     *
     * @return array<string>
     */
    public function supportedTypes(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * Check if a device type is supported.
     */
    public function isSupported(string $deviceType): bool
    {
        return isset($this->drivers[strtolower($deviceType)]);
    }

    /**
     * Get human-readable label for a device type.
     */
    public function getLabel(string $deviceType): string
    {
        return $this->deviceLabels[strtolower($deviceType)] ?? $deviceType;
    }

    /**
     * Get all supported types with labels (for UI dropdowns).
     *
     * @return array<array{type: string, label: string, default_connection: string}>
     */
    public function supportedTypesWithLabels(): array
    {
        return array_map(fn ($type) => [
            'type' => $type,
            'label' => $this->deviceLabels[$type] ?? $type,
            'default_connection' => $this->defaultConnectionTypes[$type] ?? 'serial',
        ], array_keys($this->drivers));
    }

    /**
     * Get default connection type for a device type.
     */
    public function getDefaultConnectionType(string $deviceType): string
    {
        return $this->defaultConnectionTypes[strtolower($deviceType)] ?? 'serial';
    }
}
// CLAUDE-CHECKPOINT
