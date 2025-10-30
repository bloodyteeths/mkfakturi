<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class BusinessLogicException extends Exception
{
    /**
     * Error code for API responses
     */
    protected string $errorCode;

    /**
     * HTTP status code
     */
    protected int $statusCode = 422;

    /**
     * Additional context information
     */
    protected array $context = [];

    /**
     * User-friendly message for display
     */
    protected string $userMessage;

    /**
     * Create a new business logic exception instance.
     */
    public function __construct(
        string $message,
        string $errorCode = 'BUSINESS_LOGIC_ERROR',
        int $statusCode = 422,
        array $context = [],
        ?string $userMessage = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->context = $context;
        $this->userMessage = $userMessage ?? $message;
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
     * Create an invoice generation error
     */
    public static function invoiceGenerationError(string $reason, array $context = []): self
    {
        return new self(
            message: "Invoice generation failed: {$reason}",
            errorCode: 'INVOICE_GENERATION_ERROR',
            context: $context,
            userMessage: 'Unable to generate invoice. Please check your data and try again.'
        );
    }

    /**
     * Create a payment processing error
     */
    public static function paymentProcessingError(string $reason, array $context = []): self
    {
        return new self(
            message: "Payment processing failed: {$reason}",
            errorCode: 'PAYMENT_PROCESSING_ERROR',
            statusCode: 402,
            context: $context,
            userMessage: 'Payment could not be processed. Please try again or use a different payment method.'
        );
    }

    /**
     * Create a tax calculation error
     */
    public static function taxCalculationError(string $reason, array $context = []): self
    {
        return new self(
            message: "Tax calculation failed: {$reason}",
            errorCode: 'TAX_CALCULATION_ERROR',
            context: $context,
            userMessage: 'Unable to calculate taxes. Please verify your tax settings and rates.'
        );
    }

    /**
     * Create a currency conversion error
     */
    public static function currencyConversionError(string $fromCurrency, string $toCurrency, array $context = []): self
    {
        return new self(
            message: "Currency conversion failed from {$fromCurrency} to {$toCurrency}",
            errorCode: 'CURRENCY_CONVERSION_ERROR',
            context: array_merge($context, [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]),
            userMessage: 'Unable to convert currency. Please check exchange rates and try again.'
        );
    }

    /**
     * Create an insufficient permissions error
     */
    public static function insufficientPermissions(string $action, array $context = []): self
    {
        return new self(
            message: "Insufficient permissions to perform action: {$action}",
            errorCode: 'INSUFFICIENT_PERMISSIONS',
            statusCode: 403,
            context: array_merge($context, ['action' => $action]),
            userMessage: 'You do not have permission to perform this action.'
        );
    }

    /**
     * Create a resource limit exceeded error
     */
    public static function resourceLimitExceeded(string $resourceType, int $limit, int $current, array $context = []): self
    {
        return new self(
            message: "Resource limit exceeded for {$resourceType}: {$current}/{$limit}",
            errorCode: 'RESOURCE_LIMIT_EXCEEDED',
            statusCode: 429,
            context: array_merge($context, [
                'resource_type' => $resourceType,
                'limit' => $limit,
                'current' => $current
            ]),
            userMessage: "You have reached the limit for {$resourceType}. Please upgrade your plan or remove some items."
        );
    }

    /**
     * Create a duplicate resource error
     */
    public static function duplicateResource(string $resourceType, string $identifier, array $context = []): self
    {
        return new self(
            message: "Duplicate {$resourceType} with identifier: {$identifier}",
            errorCode: 'DUPLICATE_RESOURCE',
            statusCode: 409,
            context: array_merge($context, [
                'resource_type' => $resourceType,
                'identifier' => $identifier
            ]),
            userMessage: "A {$resourceType} with this information already exists."
        );
    }

    /**
     * Create an invalid state transition error
     */
    public static function invalidStateTransition(string $resourceType, string $currentState, string $targetState, array $context = []): self
    {
        return new self(
            message: "Invalid state transition for {$resourceType}: {$currentState} -> {$targetState}",
            errorCode: 'INVALID_STATE_TRANSITION',
            context: array_merge($context, [
                'resource_type' => $resourceType,
                'current_state' => $currentState,
                'target_state' => $targetState
            ]),
            userMessage: "Cannot change {$resourceType} from {$currentState} to {$targetState}."
        );
    }

    /**
     * Create a data integrity error
     */
    public static function dataIntegrityError(string $reason, array $context = []): self
    {
        return new self(
            message: "Data integrity violation: {$reason}",
            errorCode: 'DATA_INTEGRITY_ERROR',
            statusCode: 409,
            context: $context,
            userMessage: 'This action would violate data integrity rules. Please check your data and try again.'
        );
    }

    /**
     * Create an external service error
     */
    public static function externalServiceError(string $service, string $reason, array $context = []): self
    {
        return new self(
            message: "External service error ({$service}): {$reason}",
            errorCode: 'EXTERNAL_SERVICE_ERROR',
            statusCode: 502,
            context: array_merge($context, ['service' => $service]),
            userMessage: 'An external service is currently unavailable. Please try again later.'
        );
    }

    /**
     * Create a bank integration error
     */
    public static function bankIntegrationError(string $bankName, string $reason, array $context = []): self
    {
        return new self(
            message: "Bank integration error ({$bankName}): {$reason}",
            errorCode: 'BANK_INTEGRATION_ERROR',
            statusCode: 502,
            context: array_merge($context, ['bank' => $bankName]),
            userMessage: 'Unable to connect to your bank. Please check your credentials and try again.'
        );
    }

    /**
     * Create a document generation error
     */
    public static function documentGenerationError(string $documentType, string $reason, array $context = []): self
    {
        return new self(
            message: "Document generation failed ({$documentType}): {$reason}",
            errorCode: 'DOCUMENT_GENERATION_ERROR',
            context: array_merge($context, ['document_type' => $documentType]),
            userMessage: "Unable to generate {$documentType}. Please try again or contact support."
        );
    }

    /**
     * Create an email sending error
     */
    public static function emailSendingError(string $recipient, string $reason, array $context = []): self
    {
        return new self(
            message: "Email sending failed to {$recipient}: {$reason}",
            errorCode: 'EMAIL_SENDING_ERROR',
            context: array_merge($context, ['recipient' => $recipient]),
            userMessage: 'Unable to send email. Please check the recipient address and try again.'
        );
    }

    /**
     * Create a file operation error
     */
    public static function fileOperationError(string $operation, string $filename, string $reason, array $context = []): self
    {
        return new self(
            message: "File {$operation} failed for {$filename}: {$reason}",
            errorCode: 'FILE_OPERATION_ERROR',
            context: array_merge($context, [
                'operation' => $operation,
                'filename' => $filename
            ]),
            userMessage: 'File operation failed. Please try again or contact support if the problem persists.'
        );
    }

    /**
     * Create a configuration error
     */
    public static function configurationError(string $configKey, string $reason, array $context = []): self
    {
        return new self(
            message: "Configuration error for {$configKey}: {$reason}",
            errorCode: 'CONFIGURATION_ERROR',
            statusCode: 500,
            context: array_merge($context, ['config_key' => $configKey]),
            userMessage: 'A configuration issue is preventing this operation. Please contact support.'
        );
    }

    /**
     * Create a company setup error
     */
    public static function companySetupError(string $reason, array $context = []): self
    {
        return new self(
            message: "Company setup incomplete: {$reason}",
            errorCode: 'COMPANY_SETUP_ERROR',
            context: $context,
            userMessage: 'Please complete your company setup before performing this action.'
        );
    }

    /**
     * Create a license validation error
     */
    public static function licenseValidationError(string $reason, array $context = []): self
    {
        return new self(
            message: "License validation failed: {$reason}",
            errorCode: 'LICENSE_VALIDATION_ERROR',
            statusCode: 402,
            context: $context,
            userMessage: 'Your license is invalid or expired. Please contact support or update your subscription.'
        );
    }
}