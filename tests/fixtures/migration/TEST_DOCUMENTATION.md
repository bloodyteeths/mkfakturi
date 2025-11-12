# Migration Test Data Documentation

This directory contains comprehensive test CSV files designed to validate data migration functionality across various scenarios, edge cases, and error conditions.

## Test File Categories

### 1. Happy Path Tests (Valid Data)

#### 01_happy_path_customers.csv
**Purpose:** Clean, well-formed customer data with standard Macedonian business information
**Records:** 3 customers
**Expected Outcome:** All records should import successfully
**Coverage:**
- Valid Macedonian company names with legal forms (ДООЕЛ, ООД)
- Proper email format
- Valid Macedonian phone numbers (+389 prefix)
- Complete addresses with cities
- Valid MK VAT numbers (format: MK4080012562XXX)
- Working website URLs
- Multiple currencies (MKD, EUR)

#### 07_happy_path_invoices.csv
**Purpose:** Standard invoice data with common statuses
**Records:** 5 invoices
**Expected Outcome:** All records should import successfully
**Coverage:**
- Valid invoice numbering (INV-2025-XXX)
- All common invoice statuses (SENT, PAID, DRAFT, OVERDUE, VIEWED)
- Proper date formats (YYYY-MM-DD)
- Correct tax calculations (18% standard rate for Macedonia)
- Multiple currencies
- Cyrillic text in notes field

#### 14_happy_path_items.csv
**Purpose:** Product/service catalog with standard offerings
**Records:** 7 items
**Expected Outcome:** All records should import successfully
**Coverage:**
- Various item types (hourly services, projects, subscriptions)
- Different units (hour, project, month, year)
- Multiple categories
- SKU codes
- Different tax types (Standard 18%, Reduced 5%, Zero 0%)

#### 17_happy_path_payments.csv
**Purpose:** Payment records with various methods
**Records:** 5 payments
**Expected Outcome:** All records should import successfully
**Coverage:**
- Multiple payment methods (Bank Transfer, Cash, Credit Card, PayPal, Check)
- Valid payment references
- Proper date formatting
- Multiple currencies
- Link to invoice numbers

---

### 2. Macedonian Character Tests

#### 02_macedonian_chars_customers.csv
**Purpose:** Test Cyrillic and special Macedonian characters
**Records:** 6 customers
**Expected Outcome:** All records should import with proper character encoding
**Coverage:**
- Full Cyrillic text (КИРИЛИЦА)
- Latin extended characters (Č, Ž, Š, Ć)
- Serbian Cyrillic (Ђ, Љ, Њ)
- Mixed Cyrillic and Latin scripts
- Cyrillic in email domain (@пример.мк)

**Important Notes:**
- System must handle UTF-8 encoding properly
- Email addresses with Cyrillic domains are technically valid (IDN)
- Characters should not be corrupted or replaced with ?

#### 15_macedonian_chars_items.csv
**Purpose:** Test Macedonian characters in item names and descriptions
**Records:** 7 items
**Expected Outcome:** All text should render correctly
**Coverage:**
- Full Cyrillic product names
- Mixed script descriptions
- Special characters in SKU codes
- Category names in Cyrillic

---

### 3. Large Dataset Tests

#### 03_large_dataset_customers.csv
**Purpose:** Stress test with high volume data
**Records:** 1,200+ customers (1000 MKD, 100 EUR, 100 USD)
**Expected Outcome:** All records import without memory/performance issues
**Coverage:**
- Sequential numbering patterns
- Memory management validation
- Import performance benchmarking
- Batch processing validation
- Multiple currency distribution

**Performance Targets:**
- Import time: < 30 seconds
- Memory usage: < 256MB
- No timeouts
- Proper transaction handling

---

### 4. Optional Fields Tests

#### 04_missing_optional_fields_customers.csv
**Purpose:** Validate handling of NULL/missing optional fields
**Records:** 5 customers
**Expected Outcome:** Import succeeds with NULL values for optional fields
**Coverage:**
- Missing phone number
- Missing address
- Missing website
- Missing VAT number
- Multiple missing fields

**Validation Rules:**
- Required fields: name, email, currency
- Optional fields should accept NULL
- System should not error on missing optional data

#### 05_empty_vs_null_customers.csv
**Purpose:** Distinguish between empty strings ("") and NULL values
**Records:** 6 customers
**Expected Outcome:** System treats empty strings and NULL consistently
**Coverage:**
- Empty string in optional fields ("")
- NULL in optional fields (missing)
- Different behaviors for different field types

**Database Expectations:**
- Empty strings may be converted to NULL (depends on schema)
- Both should be handled gracefully

---

### 5. Duplicate Data Tests

#### 06_duplicate_customers.csv
**Purpose:** Handle duplicate customer names and emails
**Records:** 5 customers with intentional duplicates
**Expected Outcome:** System handles duplicates per business rules
**Coverage:**
- Same name, same email
- Same name, different email
- Different name, same email
- Multiple duplicates

**Business Rules to Test:**
- Should system prevent duplicate emails?
- Should system allow same company name?
- How should conflicts be resolved?
- What warnings/errors should be shown?

---

### 6. Date Format Variations

#### 08_date_format_variations_invoices.csv
**Purpose:** Test multiple date format parsing
**Records:** 5 invoices
**Expected Outcome:** All formats parsed correctly to consistent DB format
**Coverage:**
- European format: DD.MM.YYYY (15.01.2025)
- ISO format: YYYY-MM-DD (2025-01-20)
- Alternative format: DD/MM/YYYY (25/01/2025)
- Mixed formats in same file

**Parser Requirements:**
- Auto-detect date format
- Convert all to YYYY-MM-DD for database
- Validate date logic (e.g., month <= 12)

#### 18_invalid_dates_invoices.csv
**Purpose:** Test invalid date handling
**Records:** 10 invoices with bad dates
**Expected Outcome:** Validation errors, records rejected
**Coverage:**
- Invalid month (13)
- Invalid day (32, 45)
- February 30/31
- Zero dates (00.00.0000)
- Text instead of dates
- Wrong format (YYYY/DD/MM causes wrong interpretation)
- Empty dates
- Due date before invoice date

**Expected Errors:**
- "Invalid date format"
- "Due date must be after invoice date"
- Clear error messages for each validation failure

---

### 7. Number Format Variations

#### 09_number_format_variations_invoices.csv
**Purpose:** Test different number formatting conventions
**Records:** 5 invoices
**Expected Outcome:** All formats parsed to decimal values
**Coverage:**
- European format: 1.234,56 (period as thousands, comma as decimal)
- American format: 12,345.67 (comma as thousands, period as decimal)
- Space separator: 1 234,56
- Plain format: 1234.56
- Currency prefix: МКД 1234.56

**Parser Requirements:**
- Auto-detect number format
- Remove currency symbols
- Remove whitespace
- Convert to standard decimal (####.##)
- Handle edge cases (e.g., 1,234 could be 1234 or 1.234)

#### 16_special_chars_amounts_items.csv
**Purpose:** Test currency symbols in amount fields
**Records:** 5 items
**Expected Outcome:** Symbols stripped, amounts parsed correctly
**Coverage:**
- Currency symbols: €, $, МКД
- Mixed format with symbols
- Space-separated formats

---

### 8. Currency Variations

#### 10_currency_variations_invoices.csv
**Purpose:** Test multi-currency support
**Records:** 5 invoices
**Expected Outcome:** All currencies stored correctly
**Coverage:**
- MKD (Macedonian Denar)
- EUR (Euro)
- USD (US Dollar)
- GBP (British Pound)
- CHF (Swiss Franc)

**Requirements:**
- Currency code validation (ISO 4217)
- Proper decimal places per currency
- No automatic conversion (store as-is)

---

### 9. Tax Scenarios

#### 11_tax_scenarios_invoices.csv
**Purpose:** Test various Macedonian tax rates
**Records:** 7 invoices
**Expected Outcome:** All tax calculations validated
**Coverage:**
- Standard rate: 18% (most common in Macedonia)
- Reduced rate: 5% (specific goods/services)
- Zero rate: 0% (exports, special cases)
- Mixed rates within single invoice
- Tax calculation validation (subtotal + tax = total)

**Validation Rules:**
- Tax must equal subtotal × tax_rate
- Total must equal subtotal + tax
- Rounding rules (2 decimal places)

---

### 10. Invoice Status Tests

#### 12_all_invoice_statuses.csv
**Purpose:** Test all possible invoice lifecycle statuses
**Records:** 10 invoices
**Expected Outcome:** All statuses recognized
**Coverage:**
- DRAFT: Not finalized
- SENT: Sent to customer
- VIEWED: Customer opened invoice
- EXPIRED: Past due date, unpaid
- ACCEPTED: Customer accepted
- REJECTED: Customer rejected
- OVERDUE: Past due, unpaid
- PAID: Fully paid
- PARTIALLY_PAID: Partial payment received
- DUE: Payment is due

**Business Logic:**
- Status transitions validation
- Date-based status inference
- Status vs. date consistency

---

### 11. Long Text Fields

#### 13_long_text_fields_invoices.csv
**Purpose:** Test handling of long text in notes/description fields
**Records:** 3 invoices
**Expected Outcome:** Full text stored without truncation
**Coverage:**
- 500+ character notes
- Multiple paragraphs
- Mixed Cyrillic and Latin in long text
- Special characters in long text
- Short text for comparison

**Database Requirements:**
- TEXT or LONGTEXT column type
- No VARCHAR length limits
- Proper encoding for long Cyrillic text

---

### 12. Invalid/Malformed Data Tests

#### 19_negative_amounts_invoices.csv
**Purpose:** Test validation of negative amounts
**Records:** 6 invoices
**Expected Outcome:** Validation errors or special handling
**Coverage:**
- Negative total
- Negative subtotal
- Negative tax
- All negative
- Zero amounts
- Negative tax to balance total

**Business Rules:**
- Are negative amounts allowed? (credit notes, refunds)
- Should system reject or flag for review?
- Zero amounts - valid for proforma invoices?

#### 20_missing_required_fields_customers.csv
**Purpose:** Test required field validation
**Records:** 5 customers
**Expected Outcome:** Validation errors, import fails
**Coverage:**
- Missing name
- Missing email
- Missing currency
- Completely empty record
- Partial required fields

**Expected Errors:**
- "Name is required"
- "Email is required"
- "Currency is required"
- Line number in error message

#### 21_invalid_email_formats_customers.csv
**Purpose:** Test email validation
**Records:** 7 customers
**Expected Outcome:** Invalid emails rejected
**Coverage:**
- No @ symbol
- @ at start
- @ at end
- Double @@
- Space in email
- Missing domain
- Cyrillic email (may be valid with IDN)

**Validation Rules:**
- RFC 5322 email format
- Consider IDN (Internationalized Domain Names)

#### 22_invalid_tax_id_formats_customers.csv
**Purpose:** Test VAT/tax ID validation
**Records:** 7 customers
**Expected Outcome:** Invalid formats rejected
**Coverage:**
- Too short (12345)
- Incomplete MK prefix (MK123)
- Too long
- Wrong prefix
- Special characters
- Text instead of numbers
- Spaces in number

**Validation Rules for Macedonian VAT:**
- Format: MK + 13 digits
- Must start with "MK"
- Total length: 15 characters
- Should validate checksum if applicable

---

### 13. Encoding Tests

#### 23_encoding_issues_utf8.csv
**Purpose:** Test UTF-8 encoding edge cases
**Records:** 6 customers
**Expected Outcome:** All characters rendered correctly
**Coverage:**
- Full Cyrillic
- Latin extended (Č, Ž, Š, Ć)
- Serbian Cyrillic (Ђ, Љ, Њ)
- Emoji characters (if system supports)
- Macedonian-specific (Ѓ, Ќ, Ѕ, Џ)
- Currency symbols (€, ¥, £, $)

**Technical Requirements:**
- Database charset: utf8mb4
- Collation: utf8mb4_unicode_ci
- PHP/Laravel: UTF-8 throughout
- No mojibake (corrupted characters)

#### 27_windows1250_encoding.csv / 27_windows1250_encoding_converted.csv
**Purpose:** Test legacy Windows-1250 encoding
**Records:** 2 customers
**Expected Outcome:** Convert to UTF-8 during import
**Coverage:**
- Windows-1250 encoded file
- Common in older Macedonian systems
- Character mapping to UTF-8

**Handling:**
- Auto-detect encoding
- Convert to UTF-8
- Warn user about encoding conversion

#### 33_bom_file_with_bom.csv
**Purpose:** Test UTF-8 BOM (Byte Order Mark) handling
**Records:** 2 customers
**Expected Outcome:** BOM stripped, data imports correctly
**Coverage:**
- UTF-8 BOM (EF BB BF)
- Common in Excel-exported CSV files
- Should be invisible to parser

---

### 14. CSV Format Edge Cases

#### 24_missing_invoice_headers.csv
**Purpose:** Test handling of CSV without header row
**Records:** 2 invoices (no header)
**Expected Outcome:** Error or auto-map columns
**Coverage:**
- No header row
- Raw data only

**Expected Behavior:**
- Require header row (recommended)
- OR attempt to infer from first row
- Clear error message if unable to parse

#### 25_wrong_column_count.csv
**Purpose:** Test inconsistent column counts
**Records:** 3 customers
**Expected Outcome:** Validation errors for mismatched rows
**Coverage:**
- Too many columns (extra data)
- Too few columns (missing data)
- Correct columns (for comparison)

**Expected Errors:**
- "Row 2: Expected 7 columns, found 10"
- "Row 3: Expected 7 columns, found 3"
- Line numbers in errors

#### 26_special_csv_chars.csv
**Purpose:** Test CSV special character escaping
**Records:** 5 customers
**Expected Outcome:** Special chars handled per CSV RFC 4180
**Coverage:**
- Quoted strings with embedded quotes ("Company with ""quotes""")
- Commas within quoted fields
- Newlines within quoted fields
- Semicolons (alternative delimiter confusion)
- Tabs within fields

**CSV Parsing Rules (RFC 4180):**
- Fields with commas must be quoted
- Quotes within fields must be doubled ("")
- Newlines within quoted fields are allowed
- Leading/trailing spaces preserved in quotes

---

### 15. Security Tests

#### 29_sql_injection_attempts.csv
**Purpose:** Test protection against SQL injection
**Records:** 6 customers
**Expected Outcome:** All data treated as strings, no SQL execution
**Coverage:**
- Classic SQL injection ('; DROP TABLE)
- Boolean injection (1' OR '1'='1)
- Comment injection (admin'--)
- XSS attempts (<script>alert('XSS')</script>)
- Path traversal (../../../etc/passwd)
- UNION injection

**Security Requirements:**
- Use parameterized queries (Laravel ORM)
- Never concatenate user input into SQL
- Treat all CSV data as untrusted input
- HTML escape on output (XSS protection)

---

### 16. Edge Cases & Boundaries

#### 28_circular_references.csv
**Purpose:** Test circular reference detection
**Records:** 4 invoices
**Expected Outcome:** Detect and prevent circular references
**Coverage:**
- A → B → C → A (circular chain)
- Self-reference (A → A)

**Expected Behavior:**
- Detect circular references during import
- Reject or warn user
- Prevent database constraint violations

#### 30_extreme_values.csv
**Purpose:** Test boundary values
**Records:** 5 invoices
**Expected Outcome:** Handle extremes per database limits
**Coverage:**
- Maximum amount (999999999999.99)
- Minimum amount (0.01)
- Old date (1900-01-01)
- Future date (2099-12-31)
- Excessive decimal places

**Database Limits:**
- DECIMAL(15,2) for amounts (adjust as needed)
- DATE range (1000-01-01 to 9999-12-31)
- Rounding rules for extra decimals

#### 31_mixed_valid_invalid.csv
**Purpose:** Test partial import with mixed data quality
**Records:** 7 customers (4 valid, 3 invalid)
**Expected Outcome:** Import valid records, report invalid ones
**Coverage:**
- Mix of good and bad data
- Error reporting per row
- Transaction handling (all-or-nothing vs. partial import)

**Business Logic:**
- All-or-nothing import? (rollback on any error)
- OR partial import with error report?
- User choice?

---

### 17. Whitespace & Formatting

#### 32_whitespace_issues.csv
**Purpose:** Test whitespace handling
**Records:** 5 customers
**Expected Outcome:** Whitespace trimmed appropriately
**Coverage:**
- Leading spaces
- Trailing spaces
- Both leading and trailing
- Tabs
- Multiple consecutive spaces

**Handling Rules:**
- Trim leading/trailing whitespace from all fields
- Normalize internal whitespace (multiple spaces → single space)
- Preserve intentional spacing in addresses

---

### 18. Payment-Specific Tests

#### 34_payment_date_variations.csv
**Purpose:** Test payment date format parsing
**Records:** 5 payments
**Expected Outcome:** All date formats parsed correctly
**Coverage:**
- Same date format variations as invoices
- Invalid date handling

#### 35_payment_negative_amounts.csv
**Purpose:** Test payment amount validation
**Records:** 5 payments
**Expected Outcome:** Validate per business rules
**Coverage:**
- Negative amounts (refunds)
- Zero amounts
- Extremely large amounts
- Extremely small amounts
- Invalid non-numeric amounts

**Business Rules:**
- Negative payments = refunds (may be valid)
- Zero payments (should reject?)
- Maximum payment amount validation

---

## Test Execution Strategy

### 1. Unit Tests
Create unit tests for each parsing function:
- Date parser
- Number parser
- Email validator
- VAT number validator
- Encoding detector

### 2. Integration Tests
Test complete import flow:
- CSV file upload
- Parsing
- Validation
- Database insertion
- Error reporting

### 3. Test Priorities

**P0 (Critical):**
- Happy path tests (01, 07, 14, 17)
- Required field validation (20)
- SQL injection protection (29)
- Large dataset performance (03)

**P1 (High):**
- Date/number format parsing (08, 09, 18, 19)
- Macedonian character support (02, 15, 23)
- Duplicate handling (06)
- Email/VAT validation (21, 22)

**P2 (Medium):**
- Currency variations (10)
- Tax scenarios (11)
- Status handling (12)
- Long text fields (13)
- Encoding issues (27, 33)

**P3 (Low):**
- CSV format edge cases (24, 25, 26)
- Whitespace issues (32)
- Extreme values (30)
- Mixed valid/invalid (31)

---

## Expected Validation Error Format

```json
{
  "success": false,
  "errors": [
    {
      "line": 3,
      "field": "email",
      "value": "invalid-email",
      "message": "Invalid email format"
    },
    {
      "line": 5,
      "field": "invoice_date",
      "value": "2025-13-45",
      "message": "Invalid date: month must be 1-12"
    }
  ],
  "imported": 2,
  "failed": 2,
  "total": 4
}
```

---

## Database Schema Requirements

Based on test coverage, ensure schema supports:

**Customers:**
```sql
CREATE TABLE customers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NULL,
  address TEXT NULL,
  vat_number VARCHAR(50) NULL,
  website VARCHAR(255) NULL,
  currency CHAR(3) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_vat_number (vat_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Invoices:**
```sql
CREATE TABLE invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_number VARCHAR(50) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NOT NULL,
  invoice_date DATE NOT NULL,
  due_date DATE NOT NULL,
  subtotal DECIMAL(15,2) NOT NULL,
  tax DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(15,2) NOT NULL,
  status ENUM('DRAFT','SENT','VIEWED','ACCEPTED','REJECTED','PAID','PARTIALLY_PAID','OVERDUE','EXPIRED','DUE') NOT NULL DEFAULT 'DRAFT',
  currency CHAR(3) NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT,
  INDEX idx_customer (customer_id),
  INDEX idx_status (status),
  INDEX idx_dates (invoice_date, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Items:**
```sql
CREATE TABLE items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  price DECIMAL(15,2) NOT NULL,
  unit VARCHAR(50) NOT NULL DEFAULT 'piece',
  category VARCHAR(100) NULL,
  sku VARCHAR(100) NULL UNIQUE,
  tax_type ENUM('Standard','Reduced','Zero') NOT NULL DEFAULT 'Standard',
  tax_rate DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_category (category),
  INDEX idx_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Payments:**
```sql
CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  reference VARCHAR(100) NULL,
  currency CHAR(3) NOT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE RESTRICT,
  INDEX idx_invoice (invoice_id),
  INDEX idx_payment_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Running Tests

### Manual Testing:
```bash
# Test individual file
php artisan import:csv customers /path/to/01_happy_path_customers.csv

# Test all happy path files
php artisan test --filter HappyPathTest

# Test specific category
php artisan test --filter EncodingTest
```

### Automated Testing:
```bash
# Run all migration tests
php artisan test --testsuite=Migration

# With coverage
php artisan test --testsuite=Migration --coverage
```

---

## Success Criteria

### For Happy Path Tests:
- ✓ 100% of records imported successfully
- ✓ No validation errors
- ✓ Data matches input exactly
- ✓ Proper character encoding

### For Invalid Data Tests:
- ✓ Appropriate validation errors
- ✓ Clear error messages with line numbers
- ✓ No database corruption
- ✓ No partial/inconsistent imports

### For Edge Case Tests:
- ✓ System doesn't crash
- ✓ Graceful error handling
- ✓ Security vulnerabilities blocked
- ✓ Performance within acceptable limits

---

## Maintenance Notes

- Update test files when adding new fields to schema
- Add new test files when discovering edge cases in production
- Keep test data realistic (based on actual Macedonian business patterns)
- Version control all test files
- Document any test file modifications

---

**Last Updated:** 2025-11-12
**Version:** 1.0
**Maintainer:** Migration Testing Team
