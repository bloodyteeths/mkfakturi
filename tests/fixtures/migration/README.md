# Migration Test Fixtures - Quick Reference

This directory contains 35+ comprehensive test CSV files for validating data migration functionality.

## Quick File Index

### Happy Path (Valid Data) ✓
| File | Type | Records | Description |
|------|------|---------|-------------|
| 01_happy_path_customers.csv | Customers | 3 | Clean, well-formed customer data |
| 07_happy_path_invoices.csv | Invoices | 5 | Standard invoices with common statuses |
| 14_happy_path_items.csv | Items | 7 | Product/service catalog |
| 17_happy_path_payments.csv | Payments | 5 | Payment records with various methods |

### Macedonian Characters 🇲🇰
| File | Type | Records | Description |
|------|------|---------|-------------|
| 02_macedonian_chars_customers.csv | Customers | 6 | Cyrillic, Latin extended, Serbian Cyrillic |
| 15_macedonian_chars_items.csv | Items | 7 | Macedonian chars in item names |
| 23_encoding_issues_utf8.csv | Customers | 6 | UTF-8 edge cases, currency symbols |

### Performance & Scale 📈
| File | Type | Records | Description |
|------|------|---------|-------------|
| 03_large_dataset_customers.csv | Customers | 1,200+ | Stress test with high volume data |

### Optional Fields & NULL Handling ∅
| File | Type | Records | Description |
|------|------|---------|-------------|
| 04_missing_optional_fields_customers.csv | Customers | 5 | Missing optional fields |
| 05_empty_vs_null_customers.csv | Customers | 6 | Empty strings vs NULL values |

### Duplicates & Data Quality 🔄
| File | Type | Records | Description |
|------|------|---------|-------------|
| 06_duplicate_customers.csv | Customers | 5 | Duplicate names and emails |
| 31_mixed_valid_invalid.csv | Customers | 7 | Mix of valid and invalid records |

### Date Formats 📅
| File | Type | Records | Description |
|------|------|---------|-------------|
| 08_date_format_variations_invoices.csv | Invoices | 5 | DD.MM.YYYY, YYYY-MM-DD, DD/MM/YYYY |
| 18_invalid_dates_invoices.csv | Invoices | 10 | Invalid dates, edge cases |
| 34_payment_date_variations.csv | Payments | 5 | Payment date format variations |

### Number Formats 🔢
| File | Type | Records | Description |
|------|------|---------|-------------|
| 09_number_format_variations_invoices.csv | Invoices | 5 | 1.234,56 vs 1,234.56 formats |
| 16_special_chars_amounts_items.csv | Items | 5 | Currency symbols in amounts |
| 19_negative_amounts_invoices.csv | Invoices | 6 | Negative and zero amounts |
| 35_payment_negative_amounts.csv | Payments | 5 | Payment amount edge cases |

### Currency & Tax 💰
| File | Type | Records | Description |
|------|------|---------|-------------|
| 10_currency_variations_invoices.csv | Invoices | 5 | MKD, EUR, USD, GBP, CHF |
| 11_tax_scenarios_invoices.csv | Invoices | 7 | 18% DDV, 5% DDV, 0%, mixed |

### Invoice Statuses 📋
| File | Type | Records | Description |
|------|------|---------|-------------|
| 12_all_invoice_statuses.csv | Invoices | 10 | All invoice lifecycle statuses |

### Text Fields 📝
| File | Type | Records | Description |
|------|------|---------|-------------|
| 13_long_text_fields_invoices.csv | Invoices | 3 | 500+ char notes, paragraphs |
| 32_whitespace_issues.csv | Customers | 5 | Leading, trailing, multiple spaces |

### Invalid Data - Required Fields ⚠️
| File | Type | Records | Description |
|------|------|---------|-------------|
| 20_missing_required_fields_customers.csv | Customers | 5 | Missing name, email, currency |
| 21_invalid_email_formats_customers.csv | Customers | 7 | Invalid email formats |
| 22_invalid_tax_id_formats_customers.csv | Customers | 7 | Invalid VAT/tax ID formats |

### Encoding Tests 🔤
| File | Type | Records | Description |
|------|------|---------|-------------|
| 27_windows1250_encoding.csv | Customers | 2 | Windows-1250 encoded (UTF-8 version) |
| 27_windows1250_encoding_converted.csv | Customers | 2 | Windows-1250 encoded (converted) |
| 33_bom_file.csv | Customers | 2 | Without BOM |
| 33_bom_file_with_bom.csv | Customers | 2 | UTF-8 with BOM (EF BB BF) |

### CSV Format Edge Cases 📄
| File | Type | Records | Description |
|------|------|---------|-------------|
| 24_missing_invoice_headers.csv | Invoices | 2 | No header row |
| 25_wrong_column_count.csv | Customers | 3 | Inconsistent column counts |
| 26_special_csv_chars.csv | Customers | 5 | Quotes, commas, newlines in fields |

### Security Tests 🔒
| File | Type | Records | Description |
|------|------|---------|-------------|
| 29_sql_injection_attempts.csv | Customers | 6 | SQL injection, XSS, path traversal |

### Boundary & Edge Cases 🎯
| File | Type | Records | Description |
|------|------|---------|-------------|
| 28_circular_references.csv | Invoices | 4 | Circular and self-references |
| 30_extreme_values.csv | Invoices | 5 | Max/min amounts, old/future dates |

---

## File Naming Convention

Files are numbered sequentially and grouped by category:
- **01-06:** Customer tests
- **07-13:** Invoice tests
- **14-16:** Item tests
- **17, 34-35:** Payment tests
- **18-32:** Cross-entity validation and edge cases
- **33:** Encoding tests

---

## Common Test Scenarios

### 1. Import Happy Path Data
```bash
php artisan import:csv customers tests/fixtures/migration/01_happy_path_customers.csv
php artisan import:csv invoices tests/fixtures/migration/07_happy_path_invoices.csv
php artisan import:csv items tests/fixtures/migration/14_happy_path_items.csv
php artisan import:csv payments tests/fixtures/migration/17_happy_path_payments.csv
```

### 2. Test Macedonian Character Support
```bash
php artisan import:csv customers tests/fixtures/migration/02_macedonian_chars_customers.csv
php artisan import:csv items tests/fixtures/migration/15_macedonian_chars_items.csv
```

### 3. Test Performance with Large Dataset
```bash
time php artisan import:csv customers tests/fixtures/migration/03_large_dataset_customers.csv
```

### 4. Test Validation (Should Fail)
```bash
# These should produce validation errors
php artisan import:csv customers tests/fixtures/migration/20_missing_required_fields_customers.csv
php artisan import:csv customers tests/fixtures/migration/21_invalid_email_formats_customers.csv
php artisan import:csv invoices tests/fixtures/migration/18_invalid_dates_invoices.csv
```

### 5. Test Security
```bash
# Should sanitize/reject malicious input
php artisan import:csv customers tests/fixtures/migration/29_sql_injection_attempts.csv
```

---

## Expected Outcomes Summary

### Should SUCCEED (Import All Records)
- 01, 02, 03, 04, 05, 07, 14, 15, 17
- Files 08, 09, 10, 11, 12 (if parser handles multiple formats)
- File 13 (long text fields)
- File 23 (UTF-8 encoding)
- Files 26, 32, 33 (CSV format edge cases, if parser is robust)

### Should FAIL (Validation Errors)
- 18: Invalid dates
- 19: Negative amounts (unless business rules allow)
- 20: Missing required fields
- 21: Invalid email formats
- 22: Invalid VAT formats
- 24: Missing headers
- 25: Wrong column count
- 29: SQL injection attempts (should sanitize or reject)
- 30: Extreme values (may need business rule validation)
- 35: Invalid payment amounts

### Should WARN (Partial Import or Data Quality Issues)
- 06: Duplicates (depends on conflict resolution strategy)
- 28: Circular references (should detect and prevent)
- 31: Mixed valid/invalid (depends on import strategy: all-or-nothing vs. partial)

### Should HANDLE GRACEFULLY
- 27: Windows-1250 encoding (convert to UTF-8)
- 33: BOM file (strip BOM)
- 16: Special chars in amounts (parse and clean)
- 34: Date format variations (auto-detect and convert)

---

## Testing Checklist

### Functional Testing
- [ ] All happy path files import successfully
- [ ] Macedonian characters render correctly
- [ ] Large dataset imports without timeout/memory errors
- [ ] Date format variations are parsed correctly
- [ ] Number format variations are parsed correctly
- [ ] All currencies are supported
- [ ] Tax calculations are validated
- [ ] All invoice statuses are recognized
- [ ] Long text fields are stored without truncation
- [ ] Optional fields handle NULL correctly
- [ ] Empty strings vs NULL are handled consistently

### Validation Testing
- [ ] Missing required fields are rejected with clear errors
- [ ] Invalid emails are rejected
- [ ] Invalid VAT numbers are rejected
- [ ] Invalid dates are rejected
- [ ] Negative amounts are handled per business rules
- [ ] Duplicate detection works as expected
- [ ] Column count mismatches are detected
- [ ] Missing headers are detected

### Security Testing
- [ ] SQL injection attempts are blocked
- [ ] XSS attempts are blocked
- [ ] Path traversal attempts are blocked
- [ ] All user input is sanitized
- [ ] Parameterized queries are used

### Performance Testing
- [ ] 1,200+ records import in < 30 seconds
- [ ] Memory usage stays under 256MB
- [ ] No database connection timeouts
- [ ] Batch processing works efficiently

### Encoding Testing
- [ ] UTF-8 characters display correctly
- [ ] Cyrillic text is preserved
- [ ] Special Macedonian chars (Ѓ, Ќ, Ѕ, Џ) work
- [ ] Windows-1250 files are converted
- [ ] BOM is stripped from files
- [ ] Currency symbols are preserved

### Edge Case Testing
- [ ] CSV special characters (quotes, commas, newlines) are handled
- [ ] Whitespace is trimmed appropriately
- [ ] Circular references are detected
- [ ] Extreme values are validated
- [ ] Mixed valid/invalid data is reported clearly

---

## Error Message Format

Expected error response structure:

```json
{
  "success": false,
  "message": "Import completed with errors",
  "summary": {
    "total": 10,
    "imported": 6,
    "failed": 4
  },
  "errors": [
    {
      "line": 3,
      "field": "email",
      "value": "invalid-email",
      "message": "Invalid email format",
      "code": "VALIDATION_ERROR"
    },
    {
      "line": 5,
      "field": "invoice_date",
      "value": "2025-13-45",
      "message": "Invalid date: month must be between 1 and 12",
      "code": "INVALID_DATE"
    }
  ]
}
```

---

## Performance Benchmarks

Target performance metrics for large dataset import (file 03):

| Metric | Target | Critical |
|--------|--------|----------|
| Import Time | < 30 sec | < 60 sec |
| Memory Usage | < 256 MB | < 512 MB |
| Records/sec | > 40 | > 20 |
| Database Load | < 80% CPU | < 100% CPU |

---

## Maintenance

### Adding New Test Files
1. Follow numbering convention (next available number)
2. Use descriptive filename: `##_test_purpose_entity.csv`
3. Document in both README.md and TEST_DOCUMENTATION.md
4. Include expected outcome
5. Add to appropriate test category

### Modifying Existing Test Files
1. Document changes in TEST_DOCUMENTATION.md
2. Update expected outcomes if behavior changes
3. Version control all changes
4. Run full test suite after modifications

### Reporting Issues
If a test file produces unexpected results:
1. Note the file name and line number
2. Document expected vs. actual behavior
3. Check database logs for errors
4. Verify encoding and format
5. Create bug report with test file attached

---

## Related Documentation

- **TEST_DOCUMENTATION.md**: Comprehensive documentation of all test cases
- **DATABASE_SCHEMA.sql**: Expected database schema for tests
- **IMPORT_SPECIFICATION.md**: Import business rules and requirements

---

## Quick Start

1. **Run happy path tests first:**
   ```bash
   php artisan test --filter MigrationHappyPathTest
   ```

2. **Test Macedonian character support:**
   ```bash
   php artisan test --filter MacedonianCharactersTest
   ```

3. **Test validation:**
   ```bash
   php artisan test --filter MigrationValidationTest
   ```

4. **Test security:**
   ```bash
   php artisan test --filter MigrationSecurityTest
   ```

5. **Full test suite:**
   ```bash
   php artisan test --testsuite=Migration
   ```

---

## Statistics

- **Total Test Files:** 37
- **Total Test Records:** 1,300+
- **Customers:** 1,250+ records across 18 files
- **Invoices:** 70+ records across 12 files
- **Items:** 19+ records across 3 files
- **Payments:** 15+ records across 3 files

---

**Created:** 2025-11-12
**Version:** 1.0
**Status:** Complete
