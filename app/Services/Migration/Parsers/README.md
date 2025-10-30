# File Parser Services

This directory contains the file parser services for the Universal Migration Wizard, specifically designed to handle Macedonia accounting software exports from Onivo, Megasoft, Pantheon, and other regional platforms.

## Available Parsers

### 1. CsvParserService.php
**Purpose**: Parse CSV files with Macedonia-specific formatting and encoding support.

**Features**:
- Stream processing for large files (no memory limits)
- Auto-detection of CSV structure (delimiter, encoding, headers)
- Macedonia-specific encoding support (UTF-8, Windows-1251 for Cyrillic)
- Field header extraction and data type detection
- Integration with FieldMapperService for intelligent field mapping
- Progress callbacks for real-time UI updates
- Support for Macedonia date formats (dd.mm.yyyy, dd/mm/yyyy)
- Decimal separator handling (comma to dot conversion)

**Usage**:
```php
$parser = app(CsvParserService::class);
$result = $parser->parse($importJob, $filePath, $options, $progressCallback);

// Preview functionality
$preview = $parser->preview($filePath, $options);
```

**Options**:
- `delimiter`: CSV delimiter (auto-detected if not specified)
- `encoding`: File encoding (auto-detected if not specified)
- `has_headers`: Whether file has headers (default: true)
- `skip_rows`: Number of rows to skip at beginning
- `chunk_size`: Batch processing size (default: 1000)

### 2. ExcelParserService.php
**Purpose**: Parse Excel files (.xlsx, .xls, .ods) with support for complex workbooks.

**Features**:
- Support for multiple Excel formats (XLSX, XLS, ODS, CSV)
- Memory-efficient chunked reading for large files (>100MB)
- Multiple worksheet processing with intelligent sheet selection
- Macedonia-specific data type detection and conversion
- Cyrillic text support with proper encoding handling
- Excel date/time parsing with Macedonia locale support
- Formula evaluation and calculated cell handling
- Merged cell detection and data extraction

**Usage**:
```php
$parser = app(ExcelParserService::class);
$result = $parser->parse($importJob, $filePath, $options, $progressCallback);

// Preview all worksheets
$preview = $parser->preview($filePath, $options);
```

**Options**:
- `worksheet_name`: Specific worksheet to process
- `worksheet_index`: Worksheet index to process
- `has_headers`: Whether worksheet has headers (default: true)
- `header_row`: Row containing headers (default: 1)
- `data_start_row`: First data row (auto-detected)
- `chunk_size`: Batch processing size (default: 1000)
- `memory_limit`: PHP memory limit for processing

### 3. XmlParserService.php
**Purpose**: Parse XML files from accounting software exports with format-specific handling.

**Features**:
- Support for multiple XML formats (UBL, custom exports, eSlog)
- Memory-efficient streaming for large XML files (>100MB)
- XSD schema validation for compliance verification
- Namespace-aware parsing with dynamic namespace detection
- Macedonia-specific XML structure recognition
- Nested data extraction (invoice lines, payment details)
- XML to relational data flattening

**Supported Formats**:
- **UBL 2.1 Invoices**: Standard European invoice format
- **Onivo XML Export**: Custom format from market leader
- **Megasoft XML**: Structured accounting data export
- **Pantheon eSlog**: Tax authority compliance format
- **Generic Accounting XML**: Common export patterns

**Usage**:
```php
$parser = app(XmlParserService::class);
$result = $parser->parse($importJob, $filePath, $options, $progressCallback);

// Preview XML structure
$preview = $parser->preview($filePath, $options);
```

## Common Return Format

All parsers return data in a consistent format:

```php
[
    'headers' => ['field1', 'field2', 'field3'],
    'field_mappings' => [
        'field1' => [
            'standard_field' => 'customer_name',
            'confidence' => 0.95,
            'data_type' => 'string',
            'transformation' => 'none'
        ],
        // ... more mappings
    ],
    'rows' => [
        ['field1' => 'value1', 'field2' => 'value2'],
        // ... more rows
    ],
    'metadata' => [
        'total_rows' => 1000,
        'processed_rows' => 1000,
        'processing_time' => 2.5,
        'statistics' => [
            'error_rows' => 0,
            'warnings' => []
        ]
    ]
]
```

## Macedonia-Specific Features

### Encoding Support
- **UTF-8**: Standard Unicode encoding
- **Windows-1251**: Common for Cyrillic exports from legacy systems
- **ISO-8859-1**: Western European encoding fallback

### Date Format Recognition
- `dd.mm.yyyy` (e.g., 25.12.2024)
- `dd/mm/yyyy` (e.g., 25/12/2024)  
- `yyyy-mm-dd` (ISO format)
- Excel date serial numbers

### Number Format Handling
- Comma decimal separators (e.g., `1.250,50`)
- Space thousand separators (e.g., `1 250,50`)
- Dot thousand separators (e.g., `1.250.500,50`)

### Field Name Recognition
The parsers integrate with `FieldMapperService` to recognize Macedonia field names:
- **Customer fields**: `naziv`, `klient`, `купувач`, `embs`, `данок_број`
- **Invoice fields**: `faktura`, `број_фактура`, `datum_faktura`, `dospeanos`
- **Item fields**: `stavka`, `proizvod`, `količina`, `cena`, `pdv_stapka`
- **Payment fields**: `plakanje`, `uplata`, `iznos`, `valuta`

### Progress Tracking
All parsers support progress callbacks for real-time UI updates:

```php
$progressCallback = function($progress) {
    echo "Processing: {$progress['processed']}/{$progress['total']} ({$progress['percentage']}%)\n";
};

$result = $parser->parse($importJob, $filePath, [], $progressCallback);
```

## Error Handling

The parsers implement comprehensive error handling:

1. **File Validation**: Size limits, format validation, encoding detection
2. **Parsing Errors**: Malformed data handling with row-level error reporting
3. **Memory Management**: Chunked processing to prevent memory exhaustion
4. **Data Transformation**: Graceful handling of transformation failures
5. **Logging**: Complete audit trail via `ImportLog` model

## Performance Characteristics

### Memory Usage
- **CSV**: ~50MB for files up to 1GB
- **Excel**: ~100MB for files up to 500MB
- **XML**: ~75MB for files up to 1GB

### Processing Speed
- **CSV**: ~10,000 rows/second
- **Excel**: ~5,000 rows/second
- **XML**: ~3,000 records/second

### File Size Limits
- Maximum file size: 1GB
- Recommended chunk sizes:
  - CSV: 1,000 rows
  - Excel: 500 rows
  - XML: 500 records

## Integration with Migration Wizard

These parsers are integral components of the Universal Migration Wizard:

1. **Upload Phase**: File validation and preview generation
2. **Mapping Phase**: Automatic field mapping with confidence scoring
3. **Validation Phase**: Data transformation and validation
4. **Import Phase**: Bulk processing with progress tracking

## Testing

Run the parser tests:

```bash
php artisan test tests/Unit/Migration/ParsersTest.php
```

The test suite covers:
- Macedonia-specific data formats
- Encoding handling (UTF-8, Windows-1251)
- Cyrillic text processing
- Large file handling
- Progress callback functionality
- Error recovery scenarios

## Future Enhancements

1. **Additional Formats**: Support for PDF data extraction
2. **Schema Validation**: XSD/JSON schema validation for XML/JSON files  
3. **OCR Integration**: Text extraction from scanned documents
4. **AI Enhancement**: Machine learning for better field mapping
5. **Parallel Processing**: Multi-threaded parsing for very large files