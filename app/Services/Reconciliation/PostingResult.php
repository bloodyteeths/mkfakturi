<?php

namespace App\Services\Reconciliation;

use App\Models\Payment;

/**
 * PostingResult Value Object
 *
 * Immutable result of a reconciliation posting operation.
 * Uses static factory methods for clarity:
 *   - PostingResult::success($payment)      -> payment was created
 *   - PostingResult::alreadyPosted($payment) -> idempotent replay, payment exists
 *   - PostingResult::error($message)         -> validation or processing error
 *
 * P0-12: Reconciliation Posting Service
 *
 * @property-read bool $ok Whether the operation was successful (success or alreadyPosted)
 * @property-read string $status One of: 'success', 'already_posted', 'error'
 * @property-read Payment|null $payment The payment record (null on error)
 * @property-read string|null $errorMessage Error description (null on success)
 */
class PostingResult
{
    /**
     * Status constants
     */
    public const STATUS_SUCCESS = 'success';

    public const STATUS_ALREADY_POSTED = 'already_posted';

    public const STATUS_ERROR = 'error';

    /**
     * Whether the operation succeeded (success or already_posted both count)
     */
    public readonly bool $ok;

    /**
     * The result status
     */
    public readonly string $status;

    /**
     * The payment record (null on error)
     */
    public readonly ?Payment $payment;

    /**
     * Error message (null on success)
     */
    public readonly ?string $errorMessage;

    /**
     * Private constructor - use static factory methods.
     *
     * @param  bool  $ok  Whether the operation succeeded
     * @param  string  $status  Result status code
     * @param  Payment|null  $payment  The payment record
     * @param  string|null  $errorMessage  Error description
     */
    private function __construct(
        bool $ok,
        string $status,
        ?Payment $payment = null,
        ?string $errorMessage = null
    ) {
        $this->ok = $ok;
        $this->status = $status;
        $this->payment = $payment;
        $this->errorMessage = $errorMessage;
    }

    /**
     * Create a success result - payment was newly created.
     *
     * @param  Payment  $payment  The newly created payment
     * @return static
     */
    public static function success(Payment $payment): static
    {
        return new static(
            ok: true,
            status: self::STATUS_SUCCESS,
            payment: $payment
        );
    }

    /**
     * Create an already-posted result - idempotent replay detected.
     *
     * The payment already existed for this source, so no new payment was created.
     * This is NOT an error - it means the system correctly prevented a duplicate.
     *
     * @param  Payment  $payment  The existing payment
     * @return static
     */
    public static function alreadyPosted(Payment $payment): static
    {
        return new static(
            ok: true,
            status: self::STATUS_ALREADY_POSTED,
            payment: $payment
        );
    }

    /**
     * Create an error result - posting could not be completed.
     *
     * @param  string  $message  Human-readable error description
     * @return static
     */
    public static function error(string $message): static
    {
        return new static(
            ok: false,
            status: self::STATUS_ERROR,
            errorMessage: $message
        );
    }

    /**
     * Check if a new payment was created (not a replay).
     *
     * @return bool
     */
    public function wasCreated(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if this was an idempotent replay (payment already existed).
     *
     * @return bool
     */
    public function wasAlreadyPosted(): bool
    {
        return $this->status === self::STATUS_ALREADY_POSTED;
    }

    /**
     * Check if the posting failed.
     *
     * @return bool
     */
    public function isError(): bool
    {
        return $this->status === self::STATUS_ERROR;
    }

    /**
     * Convert to array for API responses or logging.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'status' => $this->status,
            'payment_id' => $this->payment?->id,
            'error_message' => $this->errorMessage,
        ];
    }
}

// CLAUDE-CHECKPOINT
