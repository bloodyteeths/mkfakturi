<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            $this->logStructuredException($e);
        });

        $this->renderable(function (Throwable $e, Request $request) {
            return $this->renderException($e, $request);
        });
    }

    /**
     * Log exceptions with structured context and proper severity levels
     */
    protected function logStructuredException(Throwable $exception): void
    {
        $context = [
            'exception_id' => Str::uuid()->toString(),
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            'company_id' => session('company_id'),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toISOString(),
        ];

        // Add request data (sanitized)
        if (request()->hasAny(['query', 'input'])) {
            $context['request_data'] = $this->sanitizeRequestData(request()->all());
        }

        // Determine log level based on exception type
        $logLevel = $this->getLogLevel($exception);

        Log::log($logLevel, "Exception occurred: {$exception->getMessage()}", $context);

        // Log critical errors to separate channel for monitoring
        if (in_array($logLevel, ['critical', 'emergency', 'alert'])) {
            Log::channel('critical')->log($logLevel, "CRITICAL: {$exception->getMessage()}", $context);
            
            // Send notifications for critical errors in production
            if (app()->environment('production')) {
                $this->notifyCriticalError($exception, $context);
            }
        }

        // Log security-related errors
        if ($exception instanceof SystemException && $exception->getErrorCode() === 'SECURITY_VIOLATION_ERROR') {
            Log::channel('security')->warning("Security violation: {$exception->getMessage()}", $context);
        }
    }

    /**
     * Render custom exception responses
     */
    protected function renderException(Throwable $exception, Request $request): ?Response
    {
        // Handle API requests with JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->renderApiException($exception, $request);
        }

        // Handle specific exception types with custom pages
        if ($exception instanceof NotFoundHttpException) {
            return response()->view('errors.404', [
                'message' => 'The page you are looking for could not be found.',
                'support_url' => route('help.support', ['topic' => 'navigation']),
            ], 404);
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->view('errors.405', [
                'message' => 'The method used for this request is not allowed.',
                'allowed_methods' => $exception->getHeaders()['Allow'] ?? 'Unknown'
            ], 405);
        }

        if ($exception instanceof AuthenticationException) {
            return response()->view('errors.401', [
                'message' => 'You need to log in to access this resource.',
                'login_url' => route('login')
            ], 401);
        }

        // Handle migration-specific errors
        if ($exception instanceof MigrationException) {
            return $this->renderMigrationException($exception, $request);
        }

        // Handle system errors
        if ($exception instanceof SystemException) {
            return $this->renderSystemException($exception, $request);
        }

        // Handle business logic errors
        if ($exception instanceof BusinessLogicException) {
            return $this->renderBusinessException($exception, $request);
        }

        // Handle rate limiting errors
        if ($exception instanceof TooManyRequestsHttpException) {
            return response()->view('errors.429', [
                'message' => 'Too many requests. Please slow down and try again.',
                'retry_after' => $exception->getHeaders()['Retry-After'] ?? null,
            ], 429);
        }

        // Handle CSRF token errors
        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            return response()->view('errors.419', [
                'message' => 'Your session has expired. Please refresh the page and try again.',
            ], 419);
        }

        return null; // Let Laravel handle other exceptions normally
    }

    /**
     * Render API exception responses with standardized format
     */
    protected function renderApiException(Throwable $exception, Request $request): Response
    {
        $statusCode = $this->getStatusCode($exception);
        $errorCode = $this->getErrorCode($exception);
        
        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $this->getApiErrorMessage($exception),
                'type' => class_basename($exception),
            ],
            'meta' => [
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID', Str::uuid()->toString()),
            ]
        ];

        // Add validation errors for ValidationException
        if ($exception instanceof ValidationException) {
            $response['error']['validation_errors'] = $exception->errors();
        }

        // Add debug information in development
        if (config('app.debug') && !app()->environment('production')) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => collect($exception->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Render migration-specific exception responses
     */
    protected function renderMigrationException(MigrationException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'MIGRATION_ERROR',
                    'message' => $exception->getMessage(),
                    'step' => $exception->getStep(),
                    'recovery_suggestions' => $exception->getRecoverySuggestions(),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                    'import_job_id' => $exception->getImportJobId(),
                ]
            ], $exception->getStatusCode());
        }

        return response()->view('errors.migration', [
            'exception' => $exception,
            'step' => $exception->getStep(),
            'suggestions' => $exception->getRecoverySuggestions(),
            'import_job_id' => $exception->getImportJobId(),
            'retry_url' => route('migration.retry', ['job' => $exception->getImportJobId()]),
        ], $exception->getStatusCode());
    }

    /**
     * Render system exception responses
     */
    protected function renderSystemException(SystemException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $exception->getErrorCode(),
                    'message' => $exception->getUserMessage(),
                    'context' => $exception->getContext(),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                ]
            ], $exception->getStatusCode());
        }

        return response()->view('errors.500', [
            'exception' => $exception,
            'error_code' => $exception->getErrorCode(),
            'context' => $exception->getContext(),
            'user_message' => $exception->getUserMessage(),
        ], $exception->getStatusCode());
    }

    /**
     * Render business logic exception responses
     */
    protected function renderBusinessException(BusinessLogicException $exception, Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => $exception->getErrorCode(),
                    'message' => $exception->getMessage(),
                    'context' => $exception->getContext(),
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                ]
            ], $exception->getStatusCode());
        }

        return response()->view('errors.business', [
            'exception' => $exception,
            'error_code' => $exception->getErrorCode(),
            'context' => $exception->getContext(),
            'user_message' => $exception->getUserMessage(),
        ], $exception->getStatusCode());
    }


    /**
     * Get user-friendly API error message
     */
    protected function getApiErrorMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return 'The provided data failed validation.';
        }

        if ($exception instanceof ModelNotFoundException) {
            return 'The requested resource was not found.';
        }

        if ($exception instanceof AuthenticationException) {
            return 'Authentication is required to access this resource.';
        }

        if ($exception instanceof NotFoundHttpException) {
            return 'The requested endpoint was not found.';
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return 'The HTTP method used is not allowed for this endpoint.';
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            return 'Too many requests. Please slow down and try again.';
        }

        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            return 'Your session has expired. Please refresh the page and try again.';
        }

        if ($exception instanceof MigrationException || $exception instanceof BusinessLogicException) {
            return $exception->getMessage();
        }

        if ($exception instanceof SystemException) {
            return $exception->getUserMessage();
        }

        // Don't expose internal error messages in production
        if (app()->environment('production')) {
            return 'An unexpected error occurred. Please try again later.';
        }

        return $exception->getMessage();
    }

    /**
     * Sanitize request data for logging (remove sensitive information)
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'token', 'api_key', 'secret',
            'credit_card', 'ssn', 'bank_account', 'routing_number', 'cvv',
            'current_password', 'new_password', 'api_token', 'access_token'
        ];

        return collect($data)->map(function ($value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                return '[REDACTED]';
            }

            if (is_array($value)) {
                return $this->sanitizeRequestData($value);
            }

            return $value;
        })->all();
    }

    /**
     * Get HTTP status code for exception with enhanced SystemException support
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof ModelNotFoundException) {
            return 404;
        }

        if ($exception instanceof AuthenticationException) {
            return 401;
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            return 429;
        }

        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            return 419;
        }

        if ($exception instanceof MigrationException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof BusinessLogicException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof SystemException) {
            return $exception->getStatusCode();
        }

        return 500;
    }

    /**
     * Report or log an exception.
     */
    public function report(Throwable $exception): void
    {
        // Skip reporting for certain exceptions in production
        if (app()->environment('production')) {
            if ($exception instanceof NotFoundHttpException || 
                $exception instanceof ValidationException ||
                $exception instanceof AuthenticationException) {
                return;
            }
        }

        parent::report($exception);
    }

    /**
     * Convert an authentication exception into a response.
     */
    protected function unauthenticated($request, AuthenticationException $exception): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTHENTICATION_REQUIRED',
                    'message' => 'Authentication is required to access this resource.',
                ],
                'meta' => [
                    'timestamp' => now()->toISOString(),
                ]
            ], 401);
        }

        return redirect()->guest(route('login'))->with('error', 'Please log in to continue.');
    }

    /**
     * Send notifications for critical errors
     */
    protected function notifyCriticalError(Throwable $exception, array $context): void
    {
        try {
            // Send Slack notification if configured
            if (config('logging.channels.slack_critical.url')) {
                Log::channel('slack_critical')->critical("Critical Error Alert", [
                    'exception' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'url' => $context['url'] ?? 'Unknown',
                    'user_id' => $context['user_id'] ?? 'Guest',
                    'company_id' => $context['company_id'] ?? 'Unknown',
                    'timestamp' => $context['timestamp'] ?? now()->toISOString(),
                ]);
            }

            // Send email notification if configured
            if (config('logging.channels.email_critical') && env('LOG_EMAIL_RECIPIENTS')) {
                Log::channel('email_critical')->critical("Critical System Error", [
                    'exception_class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'stack_trace' => $exception->getTraceAsString(),
                    'context' => $context,
                ]);
            }

            // Log to Sentry if configured
            if (config('logging.channels.sentry') && function_exists('\\Sentry\\captureException')) {
                \Sentry\captureException($exception);
            }
        } catch (\Exception $notificationException) {
            // If notification fails, log the failure but don't throw
            Log::error("Failed to send critical error notification: {$notificationException->getMessage()}");
        }
    }

    /**
     * Get log level based on exception type with enhanced SystemException support
     */
    protected function getLogLevel(Throwable $exception): string
    {
        if ($exception instanceof \Error || $exception instanceof \ParseError) {
            return 'critical';
        }

        if ($exception instanceof \PDOException || $exception instanceof \Illuminate\Database\QueryException) {
            return 'error';
        }

        if ($exception instanceof ValidationException) {
            return 'info';
        }

        if ($exception instanceof NotFoundHttpException) {
            return 'warning';
        }

        if ($exception instanceof AuthenticationException) {
            return 'notice';
        }

        if ($exception instanceof MigrationException) {
            return $exception->isCritical() ? 'error' : 'warning';
        }

        if ($exception instanceof BusinessLogicException) {
            return 'warning';
        }

        // Enhanced SystemException handling
        if ($exception instanceof SystemException) {
            $errorCode = $exception->getErrorCode();
            
            switch ($errorCode) {
                case 'DATABASE_CONNECTION_ERROR':
                case 'MEMORY_EXHAUSTION_ERROR':
                case 'DATA_CORRUPTION_ERROR':
                case 'DEPENDENCY_UNAVAILABLE_ERROR':
                    return 'critical';
                    
                case 'FILE_SYSTEM_ERROR':
                case 'CONFIGURATION_ERROR':
                case 'THIRD_PARTY_SERVICE_ERROR':
                    return 'error';
                    
                case 'CACHE_SYSTEM_ERROR':
                case 'QUEUE_SYSTEM_ERROR':
                    return 'warning';
                    
                case 'SECURITY_VIOLATION_ERROR':
                    return 'alert';
                    
                default:
                    return 'error';
            }
        }

        return 'error';
    }

    /**
     * Enhanced error code mapping with SystemException support
     */
    protected function getErrorCode(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return 'VALIDATION_FAILED';
        }

        if ($exception instanceof ModelNotFoundException) {
            return 'RESOURCE_NOT_FOUND';
        }

        if ($exception instanceof AuthenticationException) {
            return 'AUTHENTICATION_REQUIRED';
        }

        if ($exception instanceof NotFoundHttpException) {
            return 'ENDPOINT_NOT_FOUND';
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return 'METHOD_NOT_ALLOWED';
        }

        if ($exception instanceof TooManyRequestsHttpException) {
            return 'RATE_LIMIT_EXCEEDED';
        }

        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            return 'CSRF_TOKEN_MISMATCH';
        }

        if ($exception instanceof MigrationException) {
            return $exception->getErrorCode();
        }

        if ($exception instanceof BusinessLogicException) {
            return $exception->getErrorCode();
        }

        if ($exception instanceof SystemException) {
            return $exception->getErrorCode();
        }

        return 'INTERNAL_SERVER_ERROR';
    }
}