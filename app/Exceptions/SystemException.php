<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class SystemException extends Exception
{
    /**
     * Error code for API responses
     */
    protected string $errorCode;

    /**
     * HTTP status code
     */
    protected int $statusCode = 500;

    /**
     * Additional context information
     */
    protected array $context = [];

    /**
     * User-friendly message for display
     */
    protected string $userMessage;

    /**
     * Whether this error should be reported to monitoring services
     */
    protected bool $shouldReport = true;

    /**
     * Create a new system exception instance.
     */
    public function __construct(
        string $message,
        string $errorCode = 'SYSTEM_ERROR',
        int $statusCode = 500,
        array $context = [],
        ?string $userMessage = null,
        bool $shouldReport = true,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->context = $context;
        $this->userMessage = $userMessage ?? 'A system error occurred. Please try again or contact support.';
        $this->shouldReport = $shouldReport;
    }

    /**
     * Get the error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * Get the HTTP status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get additional context
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get user-friendly message
     */
    public function getUserMessage(): string
    {
        return $this->userMessage;
    }

    /**
     * Check if this error should be reported
     */
    public function shouldReport(): bool
    {
        return $this->shouldReport;
    }

    /**
     * Create a database connection error
     */
    public static function databaseConnectionError(string $reason, array $context = []): self
    {
        return new self(
            message: "Database connection failed: {$reason}",
            errorCode: 'DATABASE_CONNECTION_ERROR',
            statusCode: 503,
            context: $context,
            userMessage: 'We are experiencing database connectivity issues. Please try again in a few moments.'
        );
    }

    /**
     * Create a file system error
     */
    public static function fileSystemError(string $operation, string $path, string $reason, array $context = []): self
    {
        return new self(
            message: "File system {$operation} failed for {$path}: {$reason}",
            errorCode: 'FILE_SYSTEM_ERROR',
            context: array_merge($context, [
                'operation' => $operation,
                'path' => $path
            ]),
            userMessage: 'A file system error occurred. Please try again or contact support if the problem persists.'
        );
    }

    /**
     * Create a memory exhaustion error
     */
    public static function memoryExhaustionError(int $memoryLimit, int $memoryUsage, array $context = []): self
    {
        return new self(
            message: "Memory exhaustion: {$memoryUsage}MB used, {$memoryLimit}MB limit",
            errorCode: 'MEMORY_EXHAUSTION_ERROR',
            statusCode: 507,
            context: array_merge($context, [
                'memory_limit' => $memoryLimit,
                'memory_usage' => $memoryUsage
            ]),
            userMessage: 'The system is low on memory. Please try again with smaller data sets or contact support.'
        );
    }

    /**
     * Create a timeout error
     */
    public static function timeoutError(string $operation, int $timeoutSeconds, array $context = []): self
    {
        return new self(
            message: "Operation '{$operation}' timed out after {$timeoutSeconds} seconds",
            errorCode: 'OPERATION_TIMEOUT_ERROR',
            statusCode: 408,
            context: array_merge($context, [
                'operation' => $operation,
                'timeout_seconds' => $timeoutSeconds
            ]),
            userMessage: 'The operation took too long to complete. Please try again with smaller data or contact support.'
        );
    }

    /**
     * Create a cache system error
     */
    public static function cacheSystemError(string $operation, string $reason, array $context = []): self
    {
        return new self(
            message: "Cache {$operation} failed: {$reason}",
            errorCode: 'CACHE_SYSTEM_ERROR',
            statusCode: 503,
            context: array_merge($context, ['operation' => $operation]),
            userMessage: 'We are experiencing caching issues. The system may be slower than usual.',
            shouldReport: false // Cache errors are often temporary
        );
    }

    /**
     * Create a queue system error
     */
    public static function queueSystemError(string $queue, string $reason, array $context = []): self
    {
        return new self(
            message: "Queue '{$queue}' error: {$reason}",
            errorCode: 'QUEUE_SYSTEM_ERROR',
            statusCode: 503,
            context: array_merge($context, ['queue' => $queue]),
            userMessage: 'Background processing is currently unavailable. Your request may be delayed.'
        );
    }

    /**
     * Create a third-party service error
     */
    public static function thirdPartyServiceError(string $service, string $endpoint, string $reason, array $context = []): self
    {
        return new self(
            message: "Third-party service '{$service}' at '{$endpoint}' failed: {$reason}",
            errorCode: 'THIRD_PARTY_SERVICE_ERROR',
            statusCode: 502,
            context: array_merge($context, [
                'service' => $service,
                'endpoint' => $endpoint
            ]),
            userMessage: 'An external service we depend on is currently unavailable. Please try again later.'
        );
    }

    /**
     * Create a license validation error
     */
    public static function licenseValidationError(string $feature, string $reason, array $context = []): self
    {
        return new self(
            message: "License validation failed for '{$feature}': {$reason}",
            errorCode: 'LICENSE_VALIDATION_ERROR',
            statusCode: 402,
            context: array_merge($context, ['feature' => $feature]),
            userMessage: 'Your license does not allow access to this feature. Please contact support or upgrade your plan.'
        );
    }

    /**
     * Create a configuration error
     */
    public static function configurationError(string $configKey, string $reason, array $context = []): self
    {
        return new self(
            message: "Configuration error for '{$configKey}': {$reason}",
            errorCode: 'CONFIGURATION_ERROR',
            context: array_merge($context, ['config_key' => $configKey]),
            userMessage: 'A system configuration issue is preventing this operation. Please contact support.'
        );
    }

    /**
     * Create a security violation error
     */
    public static function securityViolationError(string $violation, array $context = []): self
    {
        return new self(
            message: "Security violation detected: {$violation}",
            errorCode: 'SECURITY_VIOLATION_ERROR',
            statusCode: 403,
            context: $context,
            userMessage: 'This action has been blocked for security reasons. Please contact support if you believe this is an error.'
        );
    }

    /**
     * Create a maintenance mode error
     */
    public static function maintenanceModeError(string $reason = null, \DateTime $estimatedEnd = null): self
    {
        $message = 'System is currently in maintenance mode';
        if ($reason) {
            $message .= ": {$reason}";
        }

        $context = [];
        if ($estimatedEnd) {
            $context['estimated_end'] = $estimatedEnd->format('Y-m-d H:i:s T');
        }

        return new self(
            message: $message,
            errorCode: 'MAINTENANCE_MODE_ERROR',
            statusCode: 503,
            context: $context,
            userMessage: 'The system is currently undergoing maintenance. Please try again later.',
            shouldReport: false
        );
    }

    /**
     * Create a resource exhaustion error
     */
    public static function resourceExhaustionError(string $resource, int $limit, int $current, array $context = []): self
    {
        return new self(
            message: "Resource '{$resource}' exhausted: {$current}/{$limit}",
            errorCode: 'RESOURCE_EXHAUSTION_ERROR',
            statusCode: 507,
            context: array_merge($context, [
                'resource' => $resource,
                'limit' => $limit,
                'current' => $current
            ]),
            userMessage: "System resources for {$resource} are currently exhausted. Please try again later or contact support."
        );
    }

    /**
     * Create a version compatibility error
     */
    public static function versionCompatibilityError(string $component, string $currentVersion, string $requiredVersion, array $context = []): self
    {
        return new self(
            message: "Version compatibility error for '{$component}': current {$currentVersion}, required {$requiredVersion}",
            errorCode: 'VERSION_COMPATIBILITY_ERROR',
            context: array_merge($context, [
                'component' => $component,
                'current_version' => $currentVersion,
                'required_version' => $requiredVersion
            ]),
            userMessage: 'A system component version incompatibility was detected. Please contact support for assistance.'
        );
    }

    /**
     * Create a data corruption error
     */
    public static function dataCorruptionError(string $dataType, string $identifier, array $context = []): self
    {
        return new self(
            message: "Data corruption detected in {$dataType} with identifier: {$identifier}",
            errorCode: 'DATA_CORRUPTION_ERROR',
            context: array_merge($context, [
                'data_type' => $dataType,
                'identifier' => $identifier
            ]),
            userMessage: 'Data corruption has been detected. Please contact support immediately for assistance.'
        );
    }

    /**
     * Create a dependency unavailable error
     */
    public static function dependencyUnavailableError(string $dependency, string $reason, array $context = []): self
    {
        return new self(
            message: "Required dependency '{$dependency}' is unavailable: {$reason}",
            errorCode: 'DEPENDENCY_UNAVAILABLE_ERROR',
            statusCode: 503,
            context: array_merge($context, ['dependency' => $dependency]),
            userMessage: 'A required system component is currently unavailable. Please try again later.'
        );
    }
}