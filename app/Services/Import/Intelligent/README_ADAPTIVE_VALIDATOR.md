# AdaptiveValidator Service

Dynamic data validation service for intelligent CSV imports.

## Overview

The AdaptiveValidator validates imported records based on:
- Data type (not field name)
- Business rules from database (MappingRule model)
- Field requirements dynamically loaded from database
- NO hardcoded validation rules!

## Features

- **Dynamic Validation**: All validation rules are loaded from the database
- **Multi-type Support**: Email, phone, date, number, URL, boolean, string
- **Business Rules**: Min/max value, regex, enum, length constraints
- **Cross-field Validation**: Validates relationships between fields
- **Macedonian Support**: Handles Cyrillic characters, localized formats
- **IDN Support**: Internationalized domain names for emails
- **Smart Caching**: Caches mapping rules to avoid repeated DB queries

## Basic Usage

```php
use App\Services\Import\Intelligent\AdaptiveValidator;

// Initialize with company ID
$validator = new AdaptiveValidator($companyId);

// Prepare data
$record = [
    'csv_email' => 'test@example.com',
    'csv_phone' => '+38970123456',
    'csv_total' => '1500.00'
];

$fieldMappings = [
    'csv_email' => 'email',
    'csv_phone' => 'phone',
    'csv_total' => 'total'
];

// Validate
$result = $validator->validate($record, $fieldMappings, $rowNumber = 5);

// Check results
if (!empty($result['errors'])) {
    // Blocking errors - import should fail
    foreach ($result['errors'] as $error) {
        echo "Error: {$error}\n";
    }
}

if (!empty($result['warnings'])) {
    // Non-blocking warnings - import can continue
    foreach ($result['warnings'] as $warning) {
        echo "Warning: {$warning}\n";
    }
}
```

## Setting Up Validation Rules

Validation rules are stored in the `mapping_rules` table:

```php
use App\Models\MappingRule;

// Example: Email validation
MappingRule::create([
    'name' => 'Email Validation',
    'company_id' => $companyId,
    'entity_type' => MappingRule::ENTITY_CUSTOMER,
    'source_field' => 'csv_email',
    'target_field' => 'email',
    'transformation_type' => MappingRule::TRANSFORM_DIRECT,
    'validation_rules' => [
        'required' => true,
        'type' => 'email'
    ],
    'is_active' => true
]);

// Example: Invoice number with regex
MappingRule::create([
    'name' => 'Invoice Number Validation',
    'company_id' => $companyId,
    'entity_type' => MappingRule::ENTITY_INVOICE,
    'source_field' => 'csv_invoice_number',
    'target_field' => 'invoice_number',
    'transformation_type' => MappingRule::TRANSFORM_DIRECT,
    'validation_rules' => [
        'required' => true,
        'type' => 'string'
    ],
    'business_rules' => [
        'regex' => '^INV-[0-9]{3,}$',
        'min_length' => 7,
        'max_length' => 20
    ],
    'is_active' => true
]);

// Example: Amount with min/max
MappingRule::create([
    'name' => 'Total Amount Validation',
    'company_id' => $companyId,
    'entity_type' => MappingRule::ENTITY_INVOICE,
    'source_field' => 'csv_total',
    'target_field' => 'total',
    'transformation_type' => MappingRule::TRANSFORM_DIRECT,
    'validation_rules' => [
        'required' => true,
        'type' => 'decimal'
    ],
    'business_rules' => [
        'min_value' => 0,
        'max_value' => 999999
    ],
    'is_active' => true
]);
```

## Supported Data Types

### Email
- Validates email format
- Supports internationalized domain names (IDN)
- Example: `contact@компанија.мк`

### Phone
- Validates phone number format
- Supports international formats
- Macedonian numbers: `+389 70 123 456`, `070/123-456`

### Date
- Supports multiple formats: ISO (Y-m-d), European (d.m.Y), US (m/d/Y)
- Auto-detects common date formats

### Number/Decimal
- Validates numeric values
- Supports European decimal separator (comma): `1.500,50`
- Handles thousand separators

### URL
- Validates URL format
- Auto-adds http:// if missing

### Boolean
- Accepts: true/false, 1/0, yes/no, да/не, y/n, on/off

### String
- Default type
- Use business rules for length constraints

## Business Rules

### min_value / max_value
For numeric fields:
```php
'business_rules' => [
    'min_value' => 0,
    'max_value' => 999999
]
```

### regex
Pattern matching:
```php
'business_rules' => [
    'regex' => '^[A-Z]{2}-[0-9]{4}$'  // e.g., AB-1234
]
```

### enum
Allowed values list:
```php
'business_rules' => [
    'enum' => ['draft', 'sent', 'paid', 'cancelled']
]
```

### min_length / max_length
String length constraints:
```php
'business_rules' => [
    'min_length' => 3,
    'max_length' => 100
]
```

## Cross-Field Validation

The validator automatically checks relationships between fields:

### Financial Totals
Validates: `subtotal + tax = total`
```php
$record = [
    'csv_subtotal' => '100',
    'csv_tax' => '20',
    'csv_total' => '120'  // Must match subtotal + tax
];
```

### Date Relationships
Validates: `due_date >= invoice_date`
```php
$record = [
    'csv_invoice_date' => '2025-01-15',
    'csv_due_date' => '2025-01-30'  // Must be after invoice date
];
```

### Line Item Totals
Validates: `quantity × unit_price = line_total`
```php
$record = [
    'csv_quantity' => '5',
    'csv_unit_price' => '100',
    'csv_line_total' => '500'  // Must match calculation
];
```

### Negative Values
Warns about negative values in: total, subtotal, amount, price, unit_price

## Macedonian Cyrillic Support

The validator properly handles Macedonian Cyrillic characters:

```php
// Names with Cyrillic
$record = ['csv_name' => 'Компанија ДООЕЛ'];

// Boolean values in Macedonian
$record = ['csv_active' => 'да'];  // Yes
$record = ['csv_active' => 'не'];  // No

// Email with Cyrillic domain (IDN)
$record = ['csv_email' => 'контакт@компанија.мк'];
```

## Performance: Caching

The validator caches mapping rules to minimize database queries:

```php
// Clear cache if rules are updated during validation
$validator->clearCache();

// Change company scope
$validator->setCompanyId($newCompanyId);  // Automatically clears cache
```

## Error Messages

All error messages include the row number for easy debugging:

**Errors** (blocking):
- `Row 5: Required field 'email' is empty`
- `Row 12: Invalid email format in 'email': not-an-email`
- `Row 8: Value in 'total' must be at least 0, got: -500`

**Warnings** (non-blocking):
- `Row 10: Phone number may be invalid in 'phone': 123`
- `Row 15: Total (150) does not match subtotal + tax (120)`
- `Row 20: Due date is before invoice date`

## Integration Example

```php
use App\Services\Import\Intelligent\AdaptiveValidator;
use App\Models\ImportLog;

class ImportService
{
    public function processImport($file, $companyId)
    {
        $validator = new AdaptiveValidator($companyId);
        $rows = $this->readCsvFile($file);
        $fieldMappings = $this->detectFieldMappings($rows[0]);

        $errors = [];
        $warnings = [];

        foreach ($rows as $rowNumber => $row) {
            // Skip header
            if ($rowNumber === 0) continue;

            // Validate row
            $result = $validator->validate($row, $fieldMappings, $rowNumber);

            // Collect errors and warnings
            $errors = array_merge($errors, $result['errors']);
            $warnings = array_merge($warnings, $result['warnings']);

            // Stop on errors (or continue if you prefer)
            if (!empty($result['errors'])) {
                break;
            }
        }

        // Log results
        ImportLog::create([
            'company_id' => $companyId,
            'status' => empty($errors) ? 'success' : 'failed',
            'errors' => $errors,
            'warnings' => $warnings,
            'rows_processed' => count($rows) - 1
        ]);

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
```

## Testing

Comprehensive tests are available in:
`tests/Unit/Services/AdaptiveValidatorTest.php`

Run tests:
```bash
php artisan test --filter=AdaptiveValidatorTest
```

## Notes

- Validation rules must be active (`is_active = true`) to be applied
- Company-scoped rules take precedence over global rules
- Empty values are skipped unless field is marked as required
- Rounding differences up to 0.01 are allowed in financial calculations
- Invalid regex patterns in business rules generate warnings instead of failing

## Future Enhancements

- Unique field validation (requires database check)
- Custom validation callbacks
- Multi-field conditional validation
- AI-powered validation suggestions
