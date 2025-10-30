<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class ValidationException extends Exception
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
     * Field-specific validation errors
     */
    protected array $fieldErrors = [];

    /**
     * Additional context information
     */
    protected array $context = [];

    /**
     * User-friendly message for display
     */
    protected string $userMessage;

    /**
     * The field that caused the validation error
     */
    protected ?string $field = null;

    /**
     * Create a new validation exception instance.
     */
    public function __construct(
        string $message,
        string $errorCode = 'VALIDATION_ERROR',
        int $statusCode = 422,
        array $fieldErrors = [],
        array $context = [],
        ?string $userMessage = null,
        ?string $field = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
        
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->fieldErrors = $fieldErrors;
        $this->context = $context;
        $this->userMessage = $userMessage ?? 'The provided data contains validation errors.';
        $this->field = $field;
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
     * Get field-specific errors
     */
    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
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
     * Get the field that caused the error
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * Create a required field validation error
     */
    public static function requiredFieldError(string $field, array $context = []): self
    {
        return new self(
            message: "Required field '{$field}' is missing or empty",
            errorCode: 'REQUIRED_FIELD_ERROR',
            fieldErrors: [$field => 'This field is required'],
            context: $context,
            userMessage: "The field '{$field}' is required and cannot be empty.",
            field: $field
        );
    }

    /**
     * Create an invalid format validation error
     */
    public static function invalidFormatError(string $field, string $expectedFormat, string $actualValue = null, array $context = []): self
    {
        $message = "Field '{$field}' has invalid format. Expected: {$expectedFormat}";
        if ($actualValue) {
            $message .= ", Got: {$actualValue}";
        }

        return new self(
            message: $message,
            errorCode: 'INVALID_FORMAT_ERROR',
            fieldErrors: [$field => "This field must be in {$expectedFormat} format"],
            context: array_merge($context, [
                'expected_format' => $expectedFormat,
                'actual_value' => $actualValue
            ]),
            userMessage: "The field '{$field}' must be in {$expectedFormat} format.",
            field: $field
        );
    }

    /**
     * Create a range validation error
     */
    public static function outOfRangeError(string $field, $min, $max, $actualValue, array $context = []): self
    {
        return new self(
            message: "Field '{$field}' value {$actualValue} is out of range [{$min}, {$max}]",
            errorCode: 'OUT_OF_RANGE_ERROR',
            fieldErrors: [$field => "This field must be between {$min} and {$max}"],
            context: array_merge($context, [
                'min' => $min,
                'max' => $max,
                'actual_value' => $actualValue
            ]),
            userMessage: "The field '{$field}' must be between {$min} and {$max}.",
            field: $field
        );
    }

    /**
     * Create a duplicate value validation error
     */
    public static function duplicateValueError(string $field, $value, array $context = []): self
    {
        return new self(
            message: "Field '{$field}' has duplicate value: {$value}",
            errorCode: 'DUPLICATE_VALUE_ERROR',
            fieldErrors: [$field => 'This value already exists and must be unique'],
            context: array_merge($context, ['duplicate_value' => $value]),
            userMessage: "The value for '{$field}' already exists. Please choose a different value.",
            field: $field
        );
    }

    /**
     * Create an invalid choice validation error
     */
    public static function invalidChoiceError(string $field, array $allowedChoices, $actualValue, array $context = []): self
    {
        $choicesList = implode(', ', $allowedChoices);
        
        return new self(
            message: "Field '{$field}' has invalid choice '{$actualValue}'. Allowed: {$choicesList}",
            errorCode: 'INVALID_CHOICE_ERROR',
            fieldErrors: [$field => "Please choose from: {$choicesList}"],
            context: array_merge($context, [
                'allowed_choices' => $allowedChoices,
                'actual_value' => $actualValue
            ]),
            userMessage: "The field '{$field}' must be one of: {$choicesList}.",
            field: $field
        );
    }

    /**
     * Create a length validation error
     */
    public static function invalidLengthError(string $field, int $minLength, int $maxLength, int $actualLength, array $context = []): self
    {
        $lengthRule = '';
        if ($minLength > 0 && $maxLength > 0) {
            $lengthRule = "between {$minLength} and {$maxLength} characters";
        } elseif ($minLength > 0) {
            $lengthRule = "at least {$minLength} characters";
        } elseif ($maxLength > 0) {
            $lengthRule = "no more than {$maxLength} characters";
        }

        return new self(
            message: "Field '{$field}' length {$actualLength} is invalid. Must be {$lengthRule}",
            errorCode: 'INVALID_LENGTH_ERROR',
            fieldErrors: [$field => "This field must be {$lengthRule}"],
            context: array_merge($context, [
                'min_length' => $minLength,
                'max_length' => $maxLength,
                'actual_length' => $actualLength
            ]),
            userMessage: "The field '{$field}' must be {$lengthRule}.",
            field: $field
        );
    }

    /**
     * Create a dependency validation error
     */
    public static function dependencyError(string $field, string $dependentField, array $context = []): self
    {
        return new self(
            message: "Field '{$field}' depends on '{$dependentField}' which is missing or invalid",
            errorCode: 'DEPENDENCY_ERROR',
            fieldErrors: [
                $field => "This field requires '{$dependentField}' to be provided",
                $dependentField => "This field is required when '{$field}' is provided"
            ],
            context: array_merge($context, ['dependent_field' => $dependentField]),
            userMessage: "The field '{$field}' requires '{$dependentField}' to be provided.",
            field: $field
        );
    }

    /**
     * Create a custom rule validation error
     */
    public static function customRuleError(string $field, string $ruleName, string $ruleMessage, array $context = []): self
    {
        return new self(
            message: "Field '{$field}' failed custom rule '{$ruleName}': {$ruleMessage}",
            errorCode: 'CUSTOM_RULE_ERROR',
            fieldErrors: [$field => $ruleMessage],
            context: array_merge($context, ['rule_name' => $ruleName]),
            userMessage: $ruleMessage,
            field: $field
        );
    }

    /**
     * Create a file validation error
     */
    public static function fileValidationError(string $field, string $reason, array $context = []): self
    {
        return new self(
            message: "File validation failed for '{$field}': {$reason}",
            errorCode: 'FILE_VALIDATION_ERROR',
            fieldErrors: [$field => $reason],
            context: $context,
            userMessage: "The uploaded file for '{$field}' is invalid: {$reason}",
            field: $field
        );
    }

    /**
     * Create a date validation error
     */
    public static function dateValidationError(string $field, string $reason, array $context = []): self
    {
        return new self(
            message: "Date validation failed for '{$field}': {$reason}",
            errorCode: 'DATE_VALIDATION_ERROR',
            fieldErrors: [$field => $reason],
            context: $context,
            userMessage: "The date in '{$field}' is invalid: {$reason}",
            field: $field
        );
    }

    /**
     * Create a numeric validation error
     */
    public static function numericValidationError(string $field, string $expectedType, $actualValue, array $context = []): self
    {
        return new self(
            message: "Field '{$field}' expected {$expectedType}, got: {$actualValue}",
            errorCode: 'NUMERIC_VALIDATION_ERROR',
            fieldErrors: [$field => "This field must be a valid {$expectedType}"],
            context: array_merge($context, [
                'expected_type' => $expectedType,
                'actual_value' => $actualValue
            ]),
            userMessage: "The field '{$field}' must be a valid {$expectedType}.",
            field: $field
        );
    }

    /**
     * Create a batch validation error for multiple fields
     */
    public static function batchValidationError(array $fieldErrors, array $context = []): self
    {
        $errorCount = count($fieldErrors);
        $message = "Validation failed for {$errorCount} field(s): " . implode(', ', array_keys($fieldErrors));

        return new self(
            message: $message,
            errorCode: 'BATCH_VALIDATION_ERROR',
            fieldErrors: $fieldErrors,
            context: $context,
            userMessage: "Please correct the errors in the highlighted fields."
        );
    }

    /**
     * Create a conditional validation error
     */
    public static function conditionalValidationError(string $field, string $condition, array $context = []): self
    {
        return new self(
            message: "Field '{$field}' failed conditional validation: {$condition}",
            errorCode: 'CONDITIONAL_VALIDATION_ERROR',
            fieldErrors: [$field => "This field is required based on other field values"],
            context: array_merge($context, ['condition' => $condition]),
            userMessage: "The field '{$field}' is required based on your other selections.",
            field: $field
        );
    }

    /**
     * Create a business rule validation error
     */
    public static function businessRuleError(string $field, string $rule, string $explanation, array $context = []): self
    {
        return new self(
            message: "Field '{$field}' violates business rule '{$rule}': {$explanation}",
            errorCode: 'BUSINESS_RULE_VALIDATION_ERROR',
            fieldErrors: [$field => $explanation],
            context: array_merge($context, ['business_rule' => $rule]),
            userMessage: $explanation,
            field: $field
        );
    }
}