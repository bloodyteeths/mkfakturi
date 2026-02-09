<?php

namespace App\Services\InvoiceParsing;

use RuntimeException;

/**
 * Thrown when the invoice2data-service is unreachable or times out.
 *
 * Callers should catch this to provide a graceful degradation
 * (e.g. log a warning, return a user-friendly error) instead of
 * allowing a raw ConnectionException to bubble up.
 */
class Invoice2DataServiceException extends RuntimeException
{
}
// CLAUDE-CHECKPOINT
