<?php

namespace App\Exceptions;

use Exception;

/**
 * Period Locked Exception
 *
 * Thrown when attempting to modify a document in a locked period.
 */
class PeriodLockedException extends Exception
{
    protected string $lockType;

    protected $lock;

    public function __construct(string $message, string $lockType, $lock = null)
    {
        parent::__construct($message, 423); // 423 Locked HTTP status code
        $this->lockType = $lockType;
        $this->lock = $lock;
    }

    public function getLockType(): string
    {
        return $this->lockType;
    }

    public function getLock()
    {
        return $this->lock;
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'error' => 'period_locked',
            'message' => $this->getMessage(),
            'lock_type' => $this->lockType,
        ], 423);
    }
}
// CLAUDE-CHECKPOINT
