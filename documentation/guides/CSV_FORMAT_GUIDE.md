# CSV Format Guide for Migration Wizard

**Complete specification for CSV file formats accepted by Facturino Migration Wizard**

---

## Table of Contents

1. [CSV Basics](#csv-basics)
2. [Required vs Optional Fields](#required-vs-optional-fields)
3. [Data Types Reference](#data-types-reference)
4. [Date Formats](#date-formats)
5. [Number Formats](#number-formats)
6. [Character Encoding](#character-encoding)
7. [Special Characters](#special-characters)
8. [Entity-Specific Formats](#entity-specific-formats)
9. [Examples](#examples)
10. [Validation Rules](#validation-rules)

---

## CSV Basics

### What is CSV?

CSV (Comma-Separated Values) is a simple text file format where:
- Each line represents one record (row)
- Fields are separated by commas (or semicolons)
- First line contains column headers
- Text values can be enclosed in quotes

### Basic Structure

```csv
header1,header2,header3
value1,value2,value3
value4,value5,value6
```

### File Requirements

| Property | Requirement |
|----------|-------------|
| **Extension** | `.csv` (required) |
| **Encoding** | UTF-8 (required for Cyrillic) |
| **Line Endings** | CRLF (`\r\n`) or LF (`\n`) |
| **Delimiter** | Comma (`,`) or Semicolon (`;`) |
| **Headers** | First row must contain column names |
| **Max Size** | 1GB (500MB recommended) |

### Delimiters

**Comma-separated (standard):**
```csv
name,email,phone
Company A,info@a.com,123456
```

**Semicolon-separated (European):**
```csv
name;email;phone
Company A;info@a.com;123456
```

**Auto-Detection:**
The Migration Wizard automatically detects the delimiter used in your file.

---

## Required vs Optional Fields

### Field Classification

Fields are marked as:
- 🔴 **Required**: Must be present and non-empty
- 🟡 **Recommended**: Should be present for best results
- 🟢 **Optional**: Can be empty or omitted

---

## Data Types Reference

### String (Text)

**Description:** Any text value, including names, addresses, descriptions

**Format:**
- Plain text: `Company Name`
- Quoted text: `"Company, LLC"`
- Cyrillic: `Македонска Компанија`
- Mixed: `Company-123 ДОО`

**Length Limits:**
- Short text: 255 characters
- Long text (descriptions/notes): 5,000 characters

**Example:**
```csv
name,description
"Македонска Трговска ДОО","Консалтинг услуги и обуки"
```

---

### Email

**Description:** Valid email address

**Format:** `user@domain.ext`

**Rules:**
- Must contain `@` symbol
- Domain must have at least one `.`
- No spaces allowed
- Case insensitive

**Valid Examples:**
```
info@company.com
contact@компанија.mk
user+tag@domain.co.uk
```

**Invalid Examples:**
```
notanemail
user@domain
user @domain.com
@domain.com
```

**Example CSV:**
```csv
name,email
Company A,info@company-a.mk
Company B,contact@компанија-б.com
```

---

### Phone

**Description:** Phone number with optional country code

**Format:**
- International: `+389 2 123 4567`
- Local: `02 123 4567`
- Mobile: `+389 70 123 456`
- No separators: `023123456`

**Accepted Formats:**
```
+389 2 123 4567     ✓
+389 70 123 456     ✓
02/123-4567         ✓
023123456           ✓
(02) 123-4567       ✓
```

**Length:** 5-20 characters

**Example CSV:**
```csv
name,phone
Company A,+389 2 123 4567
Company B,070 123 456
```

---

### Tax ID / VAT Number

**Description:** Company tax identification number

**Macedonia Format:**
- **Length**: 13 digits
- **Prefix**: `MK` (optional, added automatically)
- **Format**: `MK4080003501234` or `4080003501234`

**Rules:**
- Numeric only (after MK prefix)
- Exactly 13 digits
- No spaces or dashes

**Valid Examples:**
```
MK4080003501234
4080003501234
```

**Invalid Examples:**
```
MK123          (too short)
40800035012345 (too long)
MK40-8000-3501 (contains dashes)
```

**Example CSV:**
```csv
name,vat_number
Company A,MK4080003501234
Company B,4080003502345
```

---

### Currency / Money

**Description:** Monetary amounts

**Format:** Decimal number with 2-4 decimal places

**Accepted Formats:**

| Format | Example | Description |
|--------|---------|-------------|
| Dot decimal | `1000.50` | US/International |
| Comma decimal | `1000,50` | European |
| Space thousands | `1 000,50` | Macedonian |
| Dot thousands | `1.000,50` | Alternative |
| No separator | `1000.50` | Plain |

**Precision:**
- Minimum: `0.01`
- Maximum: `999,999,999.9999`
- Decimal places: 2-4 (default: 2)

**Example CSV:**
```csv
invoice_number,sub_total,tax,total
FAK-001,10000.00,1800.00,11800.00
FAK-002,"1 500,00","270,00","1 770,00"
FAK-003,2.500,450,2.950
```

**Currency Codes:**
- `MKD` - Macedonian Denar (default)
- `EUR` - Euro
- `USD` - US Dollar

---

### Percentage

**Description:** Percentage values for tax rates, discounts

**Format:** Number between 0 and 100

**Accepted Formats:**
```
18       ✓ (as number)
18%      ✓ (with percent symbol)
0.18     ✓ (as decimal - converted to 18)
18.00    ✓ (with decimals)
```

**Common Tax Rates (Macedonia):**
- `18` - Standard VAT rate
- `5` - Reduced VAT rate
- `0` - Zero-rated or exempt

**Example CSV:**
```csv
item_name,price,tax_rate
Consulting,2500.00,18
Books,500.00,5%
Export Services,1000.00,0
```

---

### Integer

**Description:** Whole numbers (no decimals)

**Format:** `1`, `100`, `1000`

**Use Cases:**
- Quantities
- Counts
- IDs

**Example CSV:**
```csv
item_name,quantity,sku
Laptop,5,TECH-001
Mouse,25,TECH-002
```

---

### Boolean (Yes/No)

**Description:** True/false values

**Accepted Values:**

| True | False |
|------|-------|
| `true` | `false` |
| `1` | `0` |
| `yes` | `no` |
| `да` | `не` |
| `Y` | `N` |

**Case insensitive**

**Example CSV:**
```csv
name,is_active,is_taxable
Item A,true,yes
Item B,1,да
Item C,false,no
```

---

## Date Formats

### Supported Formats

The Migration Wizard auto-detects these date formats:

| Format | Example | Description |
|--------|---------|-------------|
| `dd.mm.yyyy` | 25.11.2024 | Dot separator (Macedonia standard) |
| `dd/mm/yyyy` | 25/11/2024 | Slash separator |
| `dd-mm-yyyy` | 25-11-2024 | Dash separator |
| `yyyy-mm-dd` | 2024-11-25 | ISO 8601 (international) |
| `d.m.yyyy` | 5.1.2024 | Single-digit day/month |
| `dd.mm.yy` | 25.11.24 | Two-digit year |
| `m/d/yyyy` | 11/25/2024 | US format (auto-detected) |

### Date Components

**Day**: 1-31 (single or double digit)
**Month**: 1-12 (single or double digit)
**Year**: 2000-2099 (four digits preferred, two digits accepted)

### DateTime Formats

**Date + Time:**
```
25.11.2024 14:30
25.11.2024 14:30:00
2024-11-25 14:30:00
25/11/2024 2:30 PM
```

**Time is optional** - If omitted, defaults to 00:00:00

### Special Date Values

| Value | Interpretation |
|-------|----------------|
| Empty field | No date (null) |
| `today` | Current date |
| `now` | Current date and time |

### Date Range Examples

**Valid Date Range:**
- Minimum: `01.01.1900`
- Maximum: `31.12.2099`

### Example CSV

```csv
invoice_number,invoice_date,due_date
FAK-001,25.11.2024,25.12.2024
FAK-002,25/11/2024,25/12/2024
FAK-003,2024-11-25,2024-12-25
FAK-004,5.1.2024,5.2.2024
```

### Consistency Requirement

**Important:** Use the same date format throughout your file.

❌ **Don't mix formats:**
```csv
invoice_date
25.11.2024
25/11/2024     ← Different separator
2024-11-25     ← Different order
```

✅ **Do use consistent format:**
```csv
invoice_date
25.11.2024
26.11.2024
27.11.2024
```

---

## Number Formats

### Decimal Separators

| Format | Decimal | Thousands | Example |
|--------|---------|-----------|---------|
| US/International | `.` (dot) | `,` (comma) | 1,234.56 |
| European | `,` (comma) | `.` (dot) | 1.234,56 |
| Macedonian | `,` (comma) | ` ` (space) | 1 234,56 |
| Scientific | E notation | - | 1.23E+3 |

### Precision

**Financial amounts:** Always use 2 decimal places
```
100.00   ✓
100      ✓ (converted to 100.00)
100.5    ✓ (converted to 100.50)
100.456  ✓ (rounded to 100.46)
```

**Quantities:** Can be whole numbers or decimals
```
5        ✓ (5 pieces)
2.5      ✓ (2.5 kg)
0.750    ✓ (750 grams)
```

**Percentages:** 0-4 decimal places
```
18       ✓ (18%)
18.0     ✓ (18%)
18.50    ✓ (18.5%)
18.5000  ✓ (18.5000%)
```

### Negative Numbers

**Format:**
```
-100.00        ✓ (minus sign)
(100.00)       ✓ (parentheses)
-1.234,56      ✓ (European format)
```

**Use Cases:**
- Credit notes (negative invoice totals)
- Refunds (negative payment amounts)
- Discounts (can be negative)

**Example CSV:**
```csv
invoice_number,total,notes
FAK-001,11800.00,Regular invoice
FAK-002,-1180.00,Credit note
FAK-003,-590.00,Refund
```

### Scientific Notation

Automatically converted:
```
1.5E+3  → 1500
1.5E-2  → 0.015
2.5E+4  → 25000
```

---

## Character Encoding

### UTF-8 (Required for Cyrillic)

**What is UTF-8?**
- Universal character encoding
- Supports all languages (including Macedonian Cyrillic)
- Standard for web and modern systems

### How to Save as UTF-8

#### Microsoft Excel (Windows)

1. Open your file in Excel
2. Click **File** → **Save As**
3. Choose **CSV UTF-8 (Comma delimited) (*.csv)**
4. Click **Save**

**Important:** Standard "CSV (Comma delimited)" does NOT preserve Cyrillic!

#### Microsoft Excel (Mac)

1. Open your file in Excel
2. Click **File** → **Save As**
3. Format: **CSV UTF-8 (Comma delimited) (.csv)**
4. Click **Save**

#### Google Sheets

1. Open your file in Google Sheets
2. Click **File** → **Download** → **Comma-separated values (.csv)**
3. Google Sheets automatically uses UTF-8

#### LibreOffice Calc

1. Open your file
2. Click **File** → **Save As**
3. File type: **Text CSV (.csv)**
4. In dialog: Character set: **UTF-8**
5. Click **OK**

#### Notepad++ (Windows)

1. Open your CSV file
2. Click **Encoding** → **Convert to UTF-8**
3. Click **File** → **Save**

#### TextEdit (Mac)

1. Open your CSV file
2. Click **Format** → **Make Plain Text**
3. Click **File** → **Save**
4. In dialog: Encoding: **UTF-8**

### Verify Encoding

**Test:** Open your file in a text editor and check:

✅ **Correct (UTF-8):**
```csv
name,address
Македонска Компанија,Бул. Кирил и Методиј
```

❌ **Wrong (non-UTF-8):**
```csv
name,address
�����������,���. ����� � ������
```

### UTF-8 with BOM

**BOM** (Byte Order Mark) is a special character at the beginning of UTF-8 files.

- **With BOM**: Recommended for Excel exports
- **Without BOM**: Also accepted

The Migration Wizard handles both automatically.

---

## Special Characters

### Comma in Text

**Problem:** Commas are field separators in CSV

**Solution:** Quote the text field

❌ **Wrong:**
```csv
name,address
Company A, LLC,123 Main St
```
*This creates 3 fields instead of 2!*

✅ **Correct:**
```csv
name,address
"Company A, LLC",123 Main St
```

### Quotes in Text

**Problem:** Quotes delimit text fields

**Solution:** Double the quotes

❌ **Wrong:**
```csv
name,notes
Company "Premium",Best quality
```

✅ **Correct:**
```csv
name,notes
"Company ""Premium""",Best quality
```

**Result:** `Company "Premium"`

### Newlines in Text

**Problem:** Newlines separate records

**Solution:** Quote multi-line text

✅ **Correct:**
```csv
name,address
"Company A","123 Main Street
Building 5
Floor 3"
"Company B","456 Oak Avenue"
```

### Special Macedonian Characters

**All Cyrillic characters are supported:**

| Character | Name | Use |
|-----------|------|-----|
| Ќ ќ | Kje | Македонски |
| Љ љ | Lje | Љубљана |
| Њ њ | Nje | Његош |
| Џ џ | Dzhe | Џамбо |
| Ѓ ѓ | Gje | Ѓорѓи |
| Ж ж | Zhe | Железара |
| Ш ш | Sha | Штампа |
| Ч ч | Che | Чевли |

**Example CSV:**
```csv
name,city
Љубљана Трговија,Скопје
Железара Ѓорѓи,Битола
Џамбо Маркет,Прилеп
```

### Symbols and Punctuation

**Supported:**
```
@ # $ % & * ( ) - _ = + [ ] { } ; : ' " < > , . / ? \ |
€ £ ¥ © ® ™ § ¶
```

**Example:**
```csv
name,email,website
Company™,info@company.mk,https://company.mk
Café & Restaurant,contact@cafe.com,www.café.mk
```

---

## Entity-Specific Formats

### Customers CSV

#### Minimal Format (Required Fields Only)

```csv
name,vat_number
"Македонска Трговска ДОО",MK4080003501234
"Електро Комерц",MK4080003502345
```

#### Standard Format (Recommended)

```csv
name,email,phone,vat_number,contact_name
"Македонска Трговска ДОО",info@mtd.mk,+389 2 123 4567,MK4080003501234,"Петар Петровски"
"Електро Комерц",contact@ek.mk,070 123 456,MK4080003502345,"Ана Николовска"
```

#### Complete Format (All Fields)

```csv
name,email,phone,vat_number,contact_name,website,address_street_1,city,state,zip,country
"Македонска Трговска ДОО",info@mtd.mk,"+389 2 123 4567",MK4080003501234,"Петар Петровски",https://mtd.mk,"Бул. Кирил и Методиј 54","Скопје","Скопски Регион",1000,MK
"Електро Комерц",contact@ek.mk,"070 123 456",MK4080003502345,"Ана Николовска",https://ek.mk,"Ул. Партизанска 12","Битола","Пелагониски",7000,MK
```

#### Field Specifications

| Field | Type | Required | Max Length | Example |
|-------|------|----------|------------|---------|
| `name` | String | 🔴 Yes | 255 | Македонска Трговска ДОО |
| `email` | Email | 🟡 Recommended | 255 | info@company.mk |
| `phone` | Phone | 🟢 Optional | 20 | +389 2 123 4567 |
| `vat_number` | Tax ID | 🔴 Yes | 20 | MK4080003501234 |
| `contact_name` | String | 🟢 Optional | 255 | Петар Петровски |
| `website` | URL | 🟢 Optional | 255 | https://company.mk |
| `address_street_1` | String | 🟢 Optional | 255 | Бул. Кирил и Методиј 54 |
| `city` | String | 🟢 Optional | 100 | Скопје |
| `state` | String | 🟢 Optional | 100 | Скопски Регион |
| `zip` | String | 🟢 Optional | 10 | 1000 |
| `country` | Country Code | 🟢 Optional | 2 | MK |

---

### Items CSV

#### Minimal Format

```csv
name,price,unit_name
"Консалтинг услуги",2500.00,час
"Маркетинг услуги",3000.00,час
```

#### Standard Format

```csv
name,description,price,unit_name,tax_rate
"Консалтинг услуги","Професионални консалтинг услуги",2500.00,час,18
"Маркетинг услуги","Дигитален маркетинг и реклама",3000.00,час,18
```

#### Complete Format

```csv
name,description,price,unit_name,sku,tax_rate
"Консалтинг услуги","Професионални консалтинг услуги за бизнис развој",2500.00,час,CONS-001,18
"Маркетинг услуги","Дигитален маркетинг, SEO, и социјални медиуми",3000.00,час,MARK-001,18
"Обука","Обуки за вработени и тимови",1500.00,ден,TRNG-001,5
```

#### Field Specifications

| Field | Type | Required | Max Length | Example |
|-------|------|----------|------------|---------|
| `name` | String | 🔴 Yes | 255 | Консалтинг услуги |
| `description` | Text | 🟡 Recommended | 5000 | Професионални консалтинг услуги |
| `price` | Currency | 🔴 Yes | - | 2500.00 |
| `unit_name` | String | 🔴 Yes | 50 | час, парче, кг |
| `sku` | String | 🟢 Optional | 50 | CONS-001 |
| `tax_rate` | Percentage | 🟢 Optional | - | 18 |

---

### Invoices CSV

#### Minimal Format

```csv
invoice_number,customer_name,invoice_date,sub_total,tax,total
FAK-2024-001,"Македонска Трговска ДОО",25.11.2024,10000.00,1800.00,11800.00
FAK-2024-002,"Електро Комерц",26.11.2024,5000.00,900.00,5900.00
```

#### Standard Format

```csv
invoice_number,customer_name,invoice_date,due_date,sub_total,tax,total,status
FAK-2024-001,"Македонска Трговска ДОО",25.11.2024,25.12.2024,10000.00,1800.00,11800.00,SENT
FAK-2024-002,"Електро Комерц",26.11.2024,26.12.2024,5000.00,900.00,5900.00,SENT
```

#### Complete Format

```csv
invoice_number,customer_name,invoice_date,due_date,sub_total,tax,total,discount,discount_val,notes,status
FAK-2024-001,"Македонска Трговска ДОО",25.11.2024,25.12.2024,10000.00,1800.00,11800.00,0,0,"Плаќање во рок од 30 дена",SENT
FAK-2024-002,"Електро Комерц",26.11.2024,26.12.2024,5000.00,900.00,5900.00,10,555.56,"Попуст 10% за редовен клиент",SENT
```

#### Field Specifications

| Field | Type | Required | Max Length | Example |
|-------|------|----------|------------|---------|
| `invoice_number` | String | 🔴 Yes | 50 | FAK-2024-001 |
| `customer_name` | String | 🔴 Yes | 255 | Македонска Трговска ДОО |
| `invoice_date` | Date | 🔴 Yes | - | 25.11.2024 |
| `due_date` | Date | 🟡 Recommended | - | 25.12.2024 |
| `sub_total` | Currency | 🔴 Yes | - | 10000.00 |
| `tax` | Currency | 🔴 Yes | - | 1800.00 |
| `total` | Currency | 🔴 Yes | - | 11800.00 |
| `discount` | Percentage | 🟢 Optional | - | 10 |
| `discount_val` | Currency | 🟢 Optional | - | 1000.00 |
| `notes` | Text | 🟢 Optional | 5000 | Плаќање во рок |
| `status` | Enum | 🟢 Optional | - | SENT, PAID, DRAFT |

#### Invoice Status Values

| Value | Description |
|-------|-------------|
| `DRAFT` | Draft invoice (not sent) |
| `SENT` | Sent to customer |
| `VIEWED` | Customer viewed invoice |
| `OVERDUE` | Payment overdue |
| `PAID` | Fully paid |
| `PARTIALLY_PAID` | Partially paid |
| `CANCELLED` | Cancelled invoice |

---

### Payments CSV

#### Minimal Format

```csv
invoice_number,payment_date,amount
FAK-2024-001,30.11.2024,11800.00
FAK-2024-002,01.12.2024,5900.00
```

#### Standard Format

```csv
invoice_number,payment_date,amount,payment_method
FAK-2024-001,30.11.2024,11800.00,BANK_TRANSFER
FAK-2024-002,01.12.2024,5900.00,CASH
```

#### Complete Format

```csv
invoice_number,payment_date,amount,payment_method,payment_number,notes
FAK-2024-001,30.11.2024,11800.00,BANK_TRANSFER,УПЛ-2024-001,"Банкарски трансфер - НЛБ Банка"
FAK-2024-002,01.12.2024,5900.00,CASH,УПЛ-2024-002,"Готовинска уплата"
```

#### Field Specifications

| Field | Type | Required | Max Length | Example |
|-------|------|----------|------------|---------|
| `invoice_number` | String | 🔴 Yes | 50 | FAK-2024-001 |
| `payment_date` | Date | 🔴 Yes | - | 30.11.2024 |
| `amount` | Currency | 🔴 Yes | - | 11800.00 |
| `payment_method` | Enum | 🟡 Recommended | - | BANK_TRANSFER |
| `payment_number` | String | 🟢 Optional | 50 | УПЛ-2024-001 |
| `notes` | Text | 🟢 Optional | 1000 | Банкарски трансфер |

#### Payment Method Values

| Value | Description (English) | Description (Macedonian) |
|-------|----------------------|--------------------------|
| `BANK_TRANSFER` | Bank transfer | Банкарски трансфер |
| `CASH` | Cash payment | Готовинска уплата |
| `CARD` | Credit/Debit card | Картичка |
| `CHECK` | Check payment | Чек |
| `PAYPAL` | PayPal | PayPal |
| `OTHER` | Other method | Друго |

---

### Expenses CSV

#### Minimal Format

```csv
expense_date,amount,category
15.11.2024,5000.00,Office Supplies
20.11.2024,15000.00,Travel
```

#### Standard Format

```csv
expense_date,amount,category,vendor_name
15.11.2024,5000.00,Office Supplies,"Канцелариски Материјали ДОО"
20.11.2024,15000.00,Travel,"Македонија Травел"
```

#### Complete Format

```csv
expense_date,amount,category,vendor_name,notes,payment_method
15.11.2024,5000.00,Office Supplies,"Канцелариски Материјали ДОО","Купување канцелариски материјал за канцеларија",CASH
20.11.2024,15000.00,Travel,"Македонија Травел","Деловна патување во Софија",CARD
```

#### Field Specifications

| Field | Type | Required | Max Length | Example |
|-------|------|----------|------------|---------|
| `expense_date` | Date | 🔴 Yes | - | 15.11.2024 |
| `amount` | Currency | 🔴 Yes | - | 5000.00 |
| `category` | String | 🔴 Yes | 100 | Office Supplies |
| `vendor_name` | String | 🟡 Recommended | 255 | Канцелариски Материјали |
| `notes` | Text | 🟢 Optional | 1000 | Купување материјал |
| `payment_method` | Enum | 🟢 Optional | - | CASH, CARD |

---

## Examples

### Example 1: Simple Customer Import

**File:** `customers_basic.csv`

```csv
name,email,vat_number
"Македонска Трговска ДОО",info@mtd.mk,MK4080003501234
"Електро Комерц",contact@ek.mk,MK4080003502345
"Фармација Здравје",farma@zdravje.mk,MK4080003503456
```

**Result:** 3 customers imported

---

### Example 2: Complete Customer Import

**File:** `customers_complete.csv`

```csv
name,email,phone,vat_number,contact_name,website,address_street_1,city,zip,country
"Македонска Трговска ДОО",info@mtd.mk,"+389 2 123 4567",MK4080003501234,"Петар Петровски",https://mtd.mk,"Бул. Кирил и Методиј 54","Скопје",1000,MK
"Електро Комерц",contact@ek.mk,"070 123 456",MK4080003502345,"Ана Николовска",https://ek.mk,"Ул. Партизанска 12","Битола",7000,MK
"Фармација Здравје",farma@zdravje.mk,"02 234 567",MK4080003503456,"Марко Марковски",https://zdravje.mk,"Ул. Гоце Делчев 89","Прилеп",7500,MK
```

**Result:** 3 customers with full details imported

---

### Example 3: Items with Different Tax Rates

**File:** `items_tax_rates.csv`

```csv
name,description,price,unit_name,tax_rate
"Консалтинг услуги","Професионални консалтинг услуги",2500.00,час,18
"Книги","Стручни книги и публикации",500.00,парче,5
"Извозни услуги","Услуги за извоз (без ДДВ)",3000.00,час,0
```

**Result:** 3 items with different tax rates (18%, 5%, 0%)

---

### Example 4: Invoices with Line Items

**File:** `invoices_with_items.csv`

```csv
invoice_number,customer_name,invoice_date,due_date,item_name,quantity,price,tax_rate,sub_total,tax,total
FAK-001,"Македонска Трговска ДОО",25.11.2024,25.12.2024,"Консалтинг услуги",10,1000.00,18,10000.00,1800.00,11800.00
FAK-002,"Електро Комерц",26.11.2024,26.12.2024,"Маркетинг услуги",5,1200.00,18,6000.00,1080.00,7080.00
FAK-003,"Фармација Здравје",27.11.2024,27.12.2024,"Обука",3,800.00,5,2400.00,120.00,2520.00
```

**Result:** 3 invoices with items imported

---

### Example 5: Payments Linked to Invoices

**File:** `payments_linked.csv`

```csv
invoice_number,payment_date,amount,payment_method,notes
FAK-001,30.11.2024,11800.00,BANK_TRANSFER,"Уплата преку НЛБ Банка"
FAK-002,01.12.2024,7080.00,CASH,"Готовинска уплата"
FAK-003,05.12.2024,2520.00,CARD,"Уплата со картичка"
```

**Result:** 3 payments linked to invoices

---

### Example 6: Mixed Number Formats

**File:** `invoices_number_formats.csv`

```csv
invoice_number,customer_name,invoice_date,sub_total,tax,total
FAK-001,"Customer A",25.11.2024,10000.00,1800.00,11800.00
FAK-002,"Customer B",26.11.2024,"5 000,00","900,00","5 900,00"
FAK-003,"Customer C",27.11.2024,2.500,450,2.950
```

**Result:** All 3 invoices imported (number formats normalized)

---

### Example 7: Cyrillic Text

**File:** `customers_cyrillic.csv` (UTF-8 encoded)

```csv
name,address,city
"Македонска Трговска ДОО","Бул. Кирил и Методиј 54","Скопје"
"Електро Комерц","Ул. Партизанска 12","Битола"
"Фармација Здравје","Ул. Гоце Делчев 89","Прилеп"
"Книжевно Друштво Љубљана","Ул. Ќосе Даќи 15","Куманово"
```

**Result:** All Cyrillic characters preserved correctly

---

### Example 8: Special Characters in Text

**File:** `customers_special_chars.csv`

```csv
name,notes
"Company ""Premium"" ДОО","Најдобар квалитет"
"Кафе & Ресторант","Специјална понуда: 10% попуст"
"Трговија ""Успех""","Email: info@uspeh.mk, Tel: +389 2 123-4567"
```

**Result:** Quotes and special characters handled correctly

---

### Example 9: Multi-line Notes

**File:** `invoices_multiline.csv`

```csv
invoice_number,customer_name,invoice_date,total,notes
FAK-001,"Customer A",25.11.2024,11800.00,"Услови за плаќање:
- Рок: 30 дена
- Метод: Банкарски трансфер
- Попуст: 5% за рано плаќање"
FAK-002,"Customer B",26.11.2024,5900.00,"Забелешка: Фактурата опфаќа услуги за периодот јануари-март 2024"
```

**Result:** Multi-line notes preserved

---

## Validation Rules

### Automatic Validations

The Migration Wizard automatically validates:

#### Email Validation
✅ Valid:
- `user@domain.com`
- `first.last@domain.co.uk`
- `user+tag@domain.com`

❌ Invalid:
- `notanemail`
- `user@`
- `@domain.com`
- `user @domain.com` (space)

#### Tax ID Validation (Macedonia)
✅ Valid:
- `MK4080003501234` (13 digits with prefix)
- `4080003501234` (13 digits, prefix added)

❌ Invalid:
- `MK123` (too short)
- `40800035012345` (too long)
- `MK40-8000-3501` (contains dashes)

#### Date Validation
✅ Valid:
- `25.11.2024`
- `25/11/2024`
- `2024-11-25`

❌ Invalid:
- `32.11.2024` (day out of range)
- `25.13.2024` (month out of range)
- `25/11/24` (year ambiguous - 1924 or 2024?)
- `25-11-2024` (inconsistent with rest of file)

#### Number Validation
✅ Valid:
- `1000.00`
- `1 000,00`
- `1.000,50`

❌ Invalid:
- `1,000,00` (wrong separator combination)
- `text` (not a number)
- `$1000` (currency symbol)

#### Referential Integrity
✅ Valid:
- Invoice references existing customer
- Payment references existing invoice

❌ Invalid:
- Invoice for non-existent customer
- Payment for non-existent invoice

### Business Logic Validations

#### Invoice Total Calculation
Must satisfy: `total = sub_total + tax - discount_val`

✅ Valid:
```csv
sub_total,tax,discount_val,total
10000.00,1800.00,0,11800.00
```

❌ Invalid:
```csv
sub_total,tax,discount_val,total
10000.00,1800.00,0,15000.00  ← Total doesn't match
```

#### Date Logic
- `due_date` must be >= `invoice_date`
- `payment_date` should be <= today (warning only)

#### Amount Validation
- Amounts must be >= 0 (except credit notes)
- Tax rate must be 0-100

---

## Troubleshooting Common Issues

### Issue 1: Cyrillic Shows as ���

**Cause:** File not UTF-8 encoded

**Solution:**
1. Open file in Excel
2. Save As → CSV UTF-8 (Comma delimited)
3. Re-upload

---

### Issue 2: Extra Columns Detected

**Cause:** Commas in text not quoted

**Wrong:**
```csv
name,address
Company A, LLC,123 Main St  ← Creates 3 columns
```

**Fix:**
```csv
name,address
"Company A, LLC",123 Main St  ← Correctly quoted
```

---

### Issue 3: Dates Not Recognized

**Cause:** Inconsistent date format

**Wrong:**
```csv
invoice_date
25.11.2024
25/11/2024  ← Different format
Nov 25, 2024  ← Text format
```

**Fix:**
```csv
invoice_date
25.11.2024
26.11.2024  ← Same format
27.11.2024
```

---

### Issue 4: Numbers Imported as Text

**Cause:** Non-numeric characters in number fields

**Wrong:**
```csv
price
$1000
1,000 USD
1000,-
```

**Fix:**
```csv
price
1000.00
1000.00
1000.00
```

---

### Issue 5: Empty Required Fields

**Cause:** Missing required field values

**Wrong:**
```csv
name,email,vat_number
Company A,,MK123  ← Missing email (recommended)
Company B,info@b.com,  ← Missing tax ID (required)
```

**Fix:**
```csv
name,email,vat_number
Company A,info@a.com,MK4080003501234
Company B,info@b.com,MK4080003502345
```

---

## Best Practices

### DO ✅

1. **Use UTF-8 encoding** for all files (especially with Cyrillic)
2. **Include column headers** in the first row
3. **Use consistent date formats** throughout the file
4. **Quote text fields** containing commas, quotes, or newlines
5. **Test with small sample** (10-20 rows) before full import
6. **Validate data** in source system before exporting
7. **Remove empty rows** at the end of file
8. **Use two decimal places** for monetary amounts
9. **Keep file size under 500MB** for best performance
10. **Backup original files** before modifying

### DON'T ❌

1. **Don't mix date formats** in same column
2. **Don't use non-UTF-8 encoding** for Cyrillic text
3. **Don't include formulas** in Excel exports (export values only)
4. **Don't use merged cells** in Excel
5. **Don't skip required fields** or leave them empty
6. **Don't use special characters** in column names
7. **Don't mix languages** in field names (use one language)
8. **Don't include currency symbols** in amount fields
9. **Don't use relative dates** like "today" or "yesterday"
10. **Don't exceed 1GB file size**

---

## Quick Reference

### Required Fields Summary

| Entity | Required Fields |
|--------|----------------|
| **Customers** | `name`, `vat_number` |
| **Items** | `name`, `price`, `unit_name` |
| **Invoices** | `invoice_number`, `customer_name`, `invoice_date`, `sub_total`, `tax`, `total` |
| **Payments** | `invoice_number`, `payment_date`, `amount` |
| **Expenses** | `expense_date`, `amount`, `category` |

### Date Format Quick Reference

| Format | Example |
|--------|---------|
| Macedonian | 25.11.2024 |
| European | 25/11/2024 |
| ISO | 2024-11-25 |

### Number Format Quick Reference

| Format | Example |
|--------|---------|
| International | 1234.56 |
| European | 1234,56 |
| Macedonian | 1 234,56 |

---

**Document Version:** 1.0
**Last Updated:** November 12, 2025
**For Questions:** support@facturino.mk

---

© 2025 Facturino. All rights reserved.
