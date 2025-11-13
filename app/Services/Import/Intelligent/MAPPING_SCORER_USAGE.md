# MappingScorer Service Usage Guide

## Overview
The MappingScorer service calculates mapping quality scores (0-100) for intelligent CSV import. It evaluates both the coverage and confidence of field mappings against database-configured requirements.

## Installation
The service is located at: `App\Services\Import\Intelligent\MappingScorer`

## Basic Usage

### 1. Calculate Mapping Quality

```php
use App\Services\Import\Intelligent\MappingScorer;
use App\Models\MappingRule;

$scorer = new MappingScorer();

// Your detected mappings from CSV
$mappings = [
    'Customer Name' => [
        'target_field' => 'name',
        'confidence' => 0.95,
        'source' => 'rule',
    ],
    'Email Address' => [
        'target_field' => 'email',
        'confidence' => 0.92,
        'source' => 'rule',
    ],
    'Phone Number' => [
        'target_field' => 'phone',
        'confidence' => 0.88,
        'source' => 'pattern',
    ],
];

// Calculate quality
$quality = $scorer->calculateQuality(
    mappings: $mappings,
    entityType: MappingRule::ENTITY_CUSTOMER,
    totalFields: 10,
    companyId: $companyId  // Optional
);
```

### 2. Quality Result Structure

```php
[
    'overall_score' => 87.5,          // 0-100
    'grade' => 'EXCELLENT',           // EXCELLENT|GOOD|FAIR|POOR|FAILED
    'critical_coverage' => 100.0,     // % of required fields mapped
    'critical_confidence' => 92.5,    // Average confidence for required fields
    'field_coverage' => 90.0,         // % of CSV fields mapped
    'avg_confidence' => 85.0,         // Average confidence for all mappings
    'critical_fields_total' => 4,
    'critical_fields_mapped' => 4,
    'total_fields' => 10,
    'mapped_fields' => 9,
    'unmapped_fields' => ['notes'],
    'unmapped_critical_fields' => [],
    'recommendation' => 'EXCELLENT: Mapping is ready to proceed with high confidence.',
    'breakdown' => [
        'high_confidence_count' => 7,   // >= 0.8
        'medium_confidence_count' => 2, // 0.6 - 0.79
        'low_confidence_count' => 0,    // < 0.6
    ]
]
```

## Score Grades

| Grade | Score Range | Meaning |
|-------|-------------|---------|
| EXCELLENT | 90-100 | All critical fields mapped with high confidence |
| GOOD | 75-89 | Most critical fields mapped, review recommended |
| FAIR | 60-74 | Some critical fields missing or low confidence |
| POOR | 40-59 | Significant manual mapping needed |
| FAILED | 0-39 | Automatic detection unsuccessful |

## Scoring Algorithm

### Weights
- **Critical Coverage (50%)**: Percentage of required fields mapped
- **Critical Confidence (30%)**: Average confidence for required fields
- **Field Coverage (10%)**: Percentage of all CSV fields mapped
- **Overall Confidence (10%)**: Average confidence for all mappings

### Formula
```
score = (
    (critical_coverage * 0.50) +
    (critical_confidence * 0.30) +
    (field_coverage * 0.10) +
    (avg_confidence * 0.10)
) * 100
```

## Data Quality Assessment

### Calculate Data Quality

```php
$dataQuality = $scorer->calculateDataQuality(
    records: $csvRecords,
    mappings: $mappings,
    entityType: MappingRule::ENTITY_CUSTOMER
);
```

### Data Quality Result

```php
[
    'completeness' => 95.5,      // % of non-empty values
    'uniqueness' => 98.0,        // % of unique values in key fields
    'consistency' => 92.0,       // Data type consistency
    'overall_quality' => 95.17,  // Average of above metrics
    'issues' => [
        '2 invalid email format(s) detected.',
        'Duplicate values detected in unique fields.'
    ]
]
```

## Field-Level Metrics

### Get Detailed Field Metrics

```php
$fieldMetrics = $scorer->getFieldQualityMetrics($csvRecords, $mappings);
```

### Field Metrics Result

```php
[
    'Customer Name' => [
        'target_field' => 'name',
        'confidence' => 0.95,
        'total_values' => 100,
        'non_empty_values' => 98,
        'completeness' => 0.98,
        'unique_values' => 87,
        'sample_values' => ['John Doe', 'Jane Smith', 'Bob Johnson']
    ],
    // ... more fields
]
```

## Integration Example

### In Import Controller

```php
use App\Services\Import\Intelligent\MappingScorer;
use App\Services\Import\Intelligent\MappingDetector;

public function analyzeCsvImport(Request $request)
{
    // 1. Parse CSV
    $csvData = $this->parseCsv($request->file('csv'));

    // 2. Detect mappings
    $detector = new MappingDetector();
    $mappings = $detector->detectMappings(
        csvHeaders: $csvData['headers'],
        sampleRows: $csvData['sample_rows'],
        entityType: $request->input('entity_type'),
        companyId: auth()->user()->company_id
    );

    // 3. Score quality
    $scorer = new MappingScorer();
    $quality = $scorer->calculateQuality(
        mappings: $mappings,
        entityType: $request->input('entity_type'),
        totalFields: count($csvData['headers']),
        companyId: auth()->user()->company_id
    );

    // 4. Assess data quality
    $dataQuality = $scorer->calculateDataQuality(
        records: $csvData['sample_rows'],
        mappings: $mappings,
        entityType: $request->input('entity_type')
    );

    // 5. Return results
    return response()->json([
        'mappings' => $mappings,
        'quality' => $quality,
        'data_quality' => $dataQuality,
        'should_proceed' => $quality['overall_score'] >= 60,
        'requires_review' => $quality['overall_score'] < 90,
    ]);
}
```

## Decision Logic

### Should Import Proceed?

```php
function shouldProceedWithImport($quality) {
    $score = $quality['overall_score'];
    $grade = $quality['grade'];

    if ($score >= 90) {
        return [
            'proceed' => true,
            'auto_import' => true,
            'message' => 'Excellent quality. Safe to proceed automatically.'
        ];
    }

    if ($score >= 75) {
        return [
            'proceed' => true,
            'auto_import' => false,
            'message' => 'Good quality. Review recommended before import.'
        ];
    }

    if ($score >= 60) {
        return [
            'proceed' => true,
            'auto_import' => false,
            'message' => 'Fair quality. Manual review required.'
        ];
    }

    if ($score >= 40) {
        return [
            'proceed' => false,
            'auto_import' => false,
            'message' => 'Poor quality. Manual mapping needed.'
        ];
    }

    return [
        'proceed' => false,
        'auto_import' => false,
        'message' => 'Failed. Please configure mappings manually.'
    ];
}
```

## Database Requirements

### Critical Fields Configuration

The scorer dynamically loads critical (required) fields from the database:

```php
// In your database seeder or migration
MappingRule::create([
    'entity_type' => MappingRule::ENTITY_CUSTOMER,
    'target_field' => 'name',
    'validation_rules' => [
        'required' => true,  // This marks it as critical
    ],
    'priority' => 1,
    'is_active' => true,
]);
```

### Key Points
- **No hardcoded field lists**: All requirements come from `mapping_rules` table
- **Company-specific rules**: Pass `companyId` to include company-specific requirements
- **Dynamic scoring**: Adding new required fields automatically updates scoring
- **Future-proof**: Supports new entity types without code changes

## Testing

### Run Tests

```bash
php artisan test --filter=MappingScorerTest
```

### Test Coverage
- ✅ Excellent quality scores (90-100)
- ✅ Good quality scores (75-89)
- ✅ Fair quality scores (60-74)
- ✅ Poor quality scores (40-59)
- ✅ Failed quality scores (0-39)
- ✅ Confidence level breakdown
- ✅ Data quality assessment
- ✅ Incomplete data handling
- ✅ Invalid data detection
- ✅ Field-level metrics
- ✅ Empty mappings handling
- ✅ No critical fields scenario

## Performance Considerations

- **Database Queries**: Cached critical field queries for same entity type
- **Sample Size**: Use representative sample (100-1000 rows) for data quality
- **Memory Usage**: Process large CSVs in chunks if needed
- **Concurrency**: Service is stateless and thread-safe

## Error Handling

```php
try {
    $quality = $scorer->calculateQuality($mappings, $entityType, $totalFields);

    if ($quality['overall_score'] < 40) {
        throw new MappingQualityException(
            "Mapping quality too low: {$quality['grade']}",
            $quality
        );
    }

} catch (\Exception $e) {
    Log::error('Mapping scoring failed', [
        'exception' => $e->getMessage(),
        'mappings' => $mappings,
        'entity_type' => $entityType,
    ]);

    // Fallback to manual mapping
}
```

## Best Practices

1. **Always score before import**: Never skip quality check
2. **Use data quality assessment**: Check actual data, not just mappings
3. **Set minimum thresholds**: Define minimum acceptable scores per entity type
4. **Log poor scores**: Track and analyze failed mappings for improvement
5. **User feedback**: Show quality metrics to users for transparency
6. **Continuous improvement**: Use failed mappings to create new rules

## Related Services

- `MappingDetector`: Detects field mappings from CSV headers
- `MappingTransformer`: Transforms CSV data using mappings
- `MappingValidator`: Validates transformed data
- `ImportPresetService`: Manages predefined mapping templates

---

Created: 2025-11-13
Last Updated: 2025-11-13
Version: 1.0.0
