# FieldMapperService Documentation

## Overview

The `FieldMapperService` is a critical component of the Universal Migration Wizard designed specifically for Macedonia accounting software migration. It provides intelligent field mapping capabilities using a comprehensive Macedonian language corpus combined with advanced matching algorithms.

## Features

### 🇲🇰 Macedonian Language Corpus
- **Comprehensive Coverage**: Over 200+ field variations covering major Macedonia accounting software (Onivo, Megasoft, Pantheon, Syntegra)
- **Multi-language Support**: Macedonian, Serbian, and English field name variations
- **Business Domain Specific**: Accounting, invoicing, taxation, and financial terminology
- **Regional Variations**: Handles different spelling conventions and dialectical differences

### 🧠 Intelligent Matching Algorithms
1. **Exact Match**: Perfect matches from the corpus (confidence: 1.0)
2. **Fuzzy Matching**: Uses Levenshtein, Jaro similarity, and substring matching
3. **Heuristic Patterns**: RegEx-based patterns for common field structures
4. **AI Semantic Scoring**: Placeholder for future ML integration

### 📊 Confidence Scoring
- **Range**: 0.0 to 1.0 (0% to 100% confidence)
- **Threshold Support**: Auto-mapping with configurable confidence thresholds
- **Alternative Suggestions**: Top 3 alternative mappings for manual review
- **Reasoning**: Human-readable explanations for mapping decisions

### 🎯 Core Functionality
- **Auto-mapping**: Automatic field mapping for high-confidence matches
- **Manual Review**: Suggestions for uncertain mappings
- **Validation**: Schema validation against target fields
- **Learning**: Capability to learn from successful mappings
- **Export**: JSON/CSV export of mapping results
- **Caching**: Redis-backed caching for performance

## Supported Field Categories

### Customer/Client Fields
- `customer_name`: naziv, ime_klient, klient, kupuvach, купувач
- `customer_id`: id_klient, klient_id, customer_id
- `tax_id`: embs, edb, danocen_broj, данок_број
- `company_id`: firma_id, kompanija_id, фирма_ид

### Invoice Fields
- `invoice_number`: broj_faktura, faktura_broj, број_фактура
- `invoice_date`: datum_faktura, faktura_datum, дата_фактура
- `due_date`: datum_dospeanos, dospeanos, доспевање
- `invoice_status`: status_faktura, статус_фактура

### Item/Product Fields
- `item_name`: naziv_stavka, ime_proizvod, производ, ставка
- `item_code`: kod_stavka, sifra_proizvod, код_ставка
- `quantity`: kolicina, kolichestvo, количина
- `unit_price`: edinichna_cena, цена_по_единица
- `description`: opis, opis_stavka, опис

### Financial Fields
- `amount`: iznos, suma, износ, сума
- `subtotal`: podvkupen_iznos, основица
- `total`: vkupen_iznos, vkupno, вкупно
- `currency`: valuta, валута

### VAT/Tax Fields
- `vat_rate`: pdv_stapka, ddv_stapka, пдв_стапка
- `vat_amount`: pdv_iznos, ddv_iznos, пдв_износ
- `tax_inclusive`: so_ddv, со_ддв
- `tax_exclusive`: bez_ddv, без_ддв

### Payment Fields
- `payment_date`: datum_plakanje, датум_плаќање
- `payment_method`: nachin_plakanje, начин_плаќање
- `payment_amount`: iznos_plakanje, износ_плаќање
- `payment_reference`: referenca_plakanje

### Additional Categories
- **Bank/Account**: bankovska_smetka, ime_banka
- **Address**: adresa, grad, postanski_broj
- **Warehouse**: skladiste, магацин
- **Expense**: kategorija_trosok, datum_trosok
- **Contact**: email, telefon, kontakt_lice

## Usage Examples

### Basic Field Mapping

```php
use App\Services\Migration\FieldMapperService;

$mapper = new FieldMapperService();

// Input fields from CSV headers, Excel columns, or XML tags
$inputFields = [
    'broj_faktura',
    'naziv_klient', 
    'embs',
    'iznos_osnovica',
    'ddv_stapka'
];

// Map fields with confidence scoring
$mappings = $mapper->mapFields($inputFields, 'csv', ['software' => 'onivo']);

foreach ($mappings as $mapping) {
    echo "Input: {$mapping['input_field']}\n";
    echo "Mapped: {$mapping['mapped_field']}\n";  
    echo "Confidence: " . round($mapping['confidence'] * 100) . "%\n";
    echo "Algorithm: {$mapping['algorithm']}\n\n";
}
```

### Auto-mapping with High Confidence

```php
// Auto-map only fields with 80%+ confidence
$autoMapped = $mapper->autoMapFields($inputFields, 0.8);

// Result: ['broj_faktura' => 'invoice_number', 'embs' => 'tax_id', ...]
```

### Manual Review Workflow

```php
// Get suggestions for manual review
$suggestions = $mapper->getSuggestions($inputFields, 10);

foreach ($suggestions as $suggestion) {
    echo "Input: {$suggestion['input_field']}\n";
    echo "Suggested: {$suggestion['suggested_field']}\n";
    echo "Confidence: " . round($suggestion['confidence'] * 100) . "%\n";
    echo "Reason: {$suggestion['reason']}\n";
    
    // Show alternatives
    foreach ($suggestion['alternatives'] as $alt) {
        echo "Alt: {$alt['field']} (" . round($alt['confidence'] * 100) . "%)\n";
    }
}
```

### Validation and Export

```php
// Validate mappings against required fields
$mappings = [
    'broj_faktura' => 'invoice_number',
    'naziv_klient' => 'customer_name',
    'iznos' => 'amount'
];

$requiredFields = ['invoice_number', 'customer_name', 'amount', 'invoice_date'];
$validation = $mapper->validateMappings($mappings, $requiredFields);

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo "Error: $error\n";
    }
}

// Export mappings
$jsonExport = $mapper->exportMappings($mappings, 'json');
$csvExport = $mapper->exportMappings($mappings, 'csv');
```

### Learning from Successful Mappings

```php
// Learn from user corrections
$mapper->learnFromMapping(
    'novi_klient',           // Input field
    'customer_name',         // Correct mapping
    0.95,                   // Confidence
    ['software' => 'custom'] // Context
);
```

## Integration with Universal Migration Wizard

### 1. File Upload Phase
```php
// Extract field names from uploaded file
$csvHeaders = ['broj_faktura', 'naziv_klient', 'iznos'];
$mappings = $mapper->mapFields($csvHeaders, 'csv');
```

### 2. Mapping Review Phase
```php
// Auto-map high confidence fields
$autoMapped = $mapper->autoMapFields($csvHeaders, 0.8);

// Present uncertain mappings for review
$needsReview = array_filter($mappings, fn($m) => $m['confidence'] < 0.8);
```

### 3. Validation Phase
```php
// Validate before data import
$validation = $mapper->validateMappings($finalMappings, $requiredFields);

if (!$validation['valid']) {
    // Show errors to user
    return response()->json(['errors' => $validation['errors']], 422);
}
```

### 4. Learning Phase
```php
// After successful import, learn from mappings
foreach ($finalMappings as $input => $mapped) {
    $mapper->learnFromMapping($input, $mapped, 1.0, $importContext);
}
```

## Configuration

### Cache Settings
- **Cache Duration**: 60 minutes (configurable)
- **Cache Prefix**: `field_mapper_`
- **Learning Cache**: 24 hours for learned mappings

### Confidence Thresholds
- **Auto-mapping**: 0.8 (80% confidence)
- **Manual Review**: 0.3-0.8 (30-80% confidence)  
- **Rejection**: <0.3 (<30% confidence)

### Fuzzy Matching
- **Similarity Threshold**: 0.7 (70% similarity)
- **Algorithm Weights**: Levenshtein (40%) + Jaro (40%) + Substring (20%)

## Performance Considerations

### Caching Strategy
- **Field Mappings**: Cached for 60 minutes
- **Learned Mappings**: Cached for 24 hours
- **Corpus Lookups**: In-memory array access

### Optimization Tips
1. **Batch Processing**: Map multiple fields at once
2. **Cache Warming**: Pre-populate cache for common software
3. **Context Usage**: Provide software context for better accuracy
4. **Learning**: Train on real data to improve accuracy over time

## Testing

Run the comprehensive test script:

```bash
php test_field_mapper.php
```

The test covers:
- ✅ Basic field mapping with various algorithms
- ✅ Auto-mapping with confidence thresholds  
- ✅ Manual review suggestions
- ✅ Mapping validation
- ✅ Export functionality
- ✅ Learning capabilities
- ✅ Corpus information

## Extending the Corpus

### Adding New Field Variations

```php
// Add to macedonianCorpus array
'new_standard_field' => [
    'variation1', 'variation2', 'macedonian_term', 
    'serbian_term', 'english_term'
]
```

### Adding New Software Support

```php
// Context-specific patterns in heuristicScore()
if (isset($context['software']) && $context['software'] === 'new_software') {
    // Add software-specific patterns
}
```

## Future Enhancements

### 1. Machine Learning Integration
- **Word Embeddings**: Use pre-trained Macedonian word vectors
- **Neural Networks**: Train on historical mapping data
- **Active Learning**: Improve from user feedback

### 2. Enhanced Corpus
- **Crowd-sourcing**: Community contributions for field variations
- **Software-specific**: Dedicated modules for each accounting software
- **Industry-specific**: Specialized terms for different business sectors

### 3. Advanced Features
- **Field Relationships**: Understand dependencies between fields
- **Data Type Detection**: Infer data types from field names
- **Schema Evolution**: Track changes in software field formats

## Support

For questions or contributions related to the FieldMapperService:

1. **Documentation**: This README and inline code comments
2. **Testing**: Run `test_field_mapper.php` for functionality verification
3. **Logging**: Check application logs for mapping decisions and errors
4. **Cache**: Monitor Redis cache for performance optimization

## License

This service is part of the Facturino Universal Migration Wizard and follows the project's licensing terms.