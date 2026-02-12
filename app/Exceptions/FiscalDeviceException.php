<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception thrown by fiscal device operations.
 */
class FiscalDeviceException extends RuntimeException
{
    private string $deviceType;

    private ?string $deviceSerial;

    public function __construct(
        string $message,
        string $deviceType = 'unknown',
        ?string $deviceSerial = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        $this->deviceType = $deviceType;
        $this->deviceSerial = $deviceSerial;
        parent::__construct($message, $code, $previous);
    }

    public function getDeviceType(): string
    {
        return $this->deviceType;
    }

    public function getDeviceSerial(): ?string
    {
        return $this->deviceSerial;
    }
}
// CLAUDE-CHECKPOINT
