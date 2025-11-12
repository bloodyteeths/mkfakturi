# Migration Wizard User Guide

**Version:** 1.0
**Last Updated:** November 2025
**For:** Facturino Accounting Software

---

## Table of Contents

1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Getting Started](#getting-started)
4. [Supported Software Formats](#supported-software-formats)
5. [Step-by-Step Tutorial](#step-by-step-tutorial)
6. [Field Mapping Guide](#field-mapping-guide)
7. [Common Errors and Solutions](#common-errors-and-solutions)
8. [FAQ](#faq)
9. [Troubleshooting](#troubleshooting)

---

## Introduction

Welcome to the Facturino Migration Wizard! This tool helps you seamlessly transfer your accounting data from other software into Facturino. Whether you're migrating from Onivo, Megasoft, Effect Plus, Eurofaktura, Manager.io, or importing from custom CSV files, this guide will walk you through every step.

### What Can You Import?

- **Customers**: Client information, contact details, tax IDs
- **Items**: Products and services you sell
- **Invoices**: Historical invoicing data with line items
- **Payments**: Payment records linked to invoices
- **Expenses**: Business expense records

### Key Features

- **Automatic Field Mapping**: Intelligent detection of your data structure
- **Macedonian Language Support**: Full support for Cyrillic characters and local formats
- **Progress Tracking**: Real-time updates during import
- **Error Recovery**: Rollback capability if something goes wrong
- **Data Preview**: See your data before importing

---

## Prerequisites

Before starting your migration, please ensure you have:

### 1. System Requirements

✅ **Admin Access**: You must be logged in as an administrator
✅ **Active Company**: At least one company set up in Facturino
✅ **Browser**: Modern web browser (Chrome, Firefox, Safari, Edge)
✅ **Internet Connection**: Stable connection for large file uploads

### 2. Data Preparation

✅ **Backup**: Create a backup of your current accounting software
✅ **Export Data**: Export data from your existing software (see [Supported Software Formats](#supported-software-formats))
✅ **File Format**: Ensure files are in CSV, Excel (.xlsx, .xls), or XML format
✅ **File Size**: Maximum 1GB per file (recommended: under 500MB)
✅ **Encoding**: Files should be UTF-8 encoded (Windows-1251 also supported for Cyrillic)

### 3. Feature Access

✅ **Feature Enabled**: Contact your administrator if the Migration Wizard is not visible
✅ **Permissions**: Ensure you have import permissions for your company

---

## Supported Software Formats

### Onivo Accounting

**Export Instructions:**
1. Open Onivo software
2. Go to "Извештаи" (Reports) → "Извоз на податоци" (Data Export)
3. Select data type: Партнери (Customers), Артикли (Items), or Фактури (Invoices)
4. Choose export format: CSV or Excel
5. Click "Извези" (Export) and save the file

**Field Names (Cyrillic):**
- Customers: Партнер, ЕДБ, Email, Телефон, Контакт
- Items: Производ, Опис, Цена, Единица
- Invoices: Број на фактура, Купувач, Датум на фактура, Основица, ДДВ, Вкупно

### Megasoft ERP

**Export Instructions:**
1. Open Megasoft application
2. Navigate to "Podaci" → "Eksport"
3. Select entity: Partneri, Artikli, or Fakturi
4. Choose format: CSV, Excel, or XML
5. Export to desktop

**Field Names (Mixed Latin/Cyrillic):**
- Customers: ParnerName, ParnerEDB, ParnerEmail, ParnerTel
- Items: ArtikalNaziv, ArtikalOpis, ArtikalCena
- Invoices: FakturaBroj, Kupuvac, FakturaDatum, Osnovica, DDV

### Effect Plus

**Export Instructions:**
1. Open Effect Plus
2. Go to "Alati" (Tools) → "Export podataka"
3. Select data module and date range
4. Export as CSV or Excel
5. Save to known location

**Field Names:**
- Customers: Klient_Naziv, Klient_EDB, Klient_Email
- Items: Proizvod_Naziv, Proizvod_Cena
- Invoices: Faktura_Broj, Faktura_Datum, Faktura_Iznos

### Eurofaktura

**Export Instructions:**
1. Open Eurofaktura
2. Go to "Podešavanja" → "Import/Export"
3. Select "Export u CSV/Excel"
4. Choose data type and date range
5. Click "Exportuj"

**Field Names (Serbian):**
- Customers: Kupac_Naziv, Kupac_PIB, Kupac_Email
- Items: Artikal_Naziv, Artikal_Cena
- Invoices: Faktura_Broj, Faktura_Datum, Faktura_Ukupno

### Manager.io

**Export Instructions:**
1. Open Manager.io
2. Go to desired tab (Customers, Items, Sales Invoices)
3. Click "Export" button at top right
4. Choose "Excel" or "Tab-separated" format
5. Save file

**Field Names (English):**
- Customers: Customer Name, Tax ID, Email, Phone
- Items: Item Name, Description, Unit Price
- Invoices: Invoice Number, Date, Customer, Total

### Generic CSV/Excel

If your software isn't listed above, you can create custom CSV or Excel files. See [CSV Format Guide](#csv-format-guide) for detailed specifications.

---

## Step-by-Step Tutorial

### Step 1: Access the Migration Wizard

1. Log into Facturino as an administrator
2. Navigate to **Settings** → **Data Import** → **Migration Wizard**
3. You'll see the Migration Wizard dashboard with system options

![Migration Wizard Dashboard](screenshots/wizard-dashboard.png)

### Step 2: Choose Your Source System

Select where your data is coming from:

- **Onivo Accounting** - For Onivo software exports
- **Megasoft ERP** - For Megasoft exports
- **Effect Plus** - For Effect Plus exports
- **Eurofaktura** - For Eurofaktura exports
- **Manager.io** - For Manager.io exports
- **Generic CSV/Excel** - For custom files or other software

Click on your source system card to continue.

![Source System Selection](screenshots/source-selection.png)

### Step 3: Upload Your Files

#### For Single Entity Import:

1. Click **"Choose File"** or drag and drop your file
2. Select the entity type: Customers, Items, Invoices, Payments, or Expenses
3. The wizard will validate your file automatically
4. You'll see a preview of detected columns and record count

#### For Complete Import (Multiple Files):

1. Upload multiple files for different entity types
2. The wizard recognizes the entity type from file structure
3. Files can be uploaded individually or all at once
4. Each file is validated independently

**Supported File Formats:**
- CSV files (.csv)
- Excel files (.xlsx, .xls)
- XML files (.xml)
- OpenDocument Spreadsheet (.ods)

**File Size Limits:**
- Maximum: 1GB per file
- Recommended: Under 500MB for better performance

![File Upload Interface](screenshots/file-upload.png)

#### Upload Progress

Watch the upload progress bar:
- **Green**: Upload successful
- **Yellow**: Upload complete with warnings
- **Red**: Upload failed (see error message)

### Step 4: Review Field Mappings

After upload, the wizard automatically maps your source fields to Facturino fields.

#### Automatic Mapping

The wizard uses intelligent algorithms to match your fields:

- **High Confidence (90-100%)**: Shown in green with checkmark ✓
- **Medium Confidence (70-89%)**: Shown in yellow with warning icon ⚠
- **Low Confidence (<70%)**: Shown in red with error icon ✗
- **No Match**: Fields marked for manual selection

![Field Mapping Interface](screenshots/field-mapping.png)

#### Manual Field Mapping

For fields that need review:

1. Click on the **target field dropdown**
2. Select the correct Facturino field from the list
3. The field will turn green when correctly mapped
4. Repeat for all unmapped fields

**Required Fields** (marked with red asterisk *):
- **Customers**: Name, Email (recommended), Tax ID
- **Items**: Name, Price, Unit
- **Invoices**: Invoice Number, Customer, Date, Total
- **Payments**: Amount, Date, Invoice Reference

#### Field Transformation Rules

Configure how data is transformed:

- **Date Format**: Select your source date format (dd.mm.yyyy, dd/mm/yyyy, yyyy-mm-dd)
- **Number Format**: Configure decimal and thousand separators
- **Currency**: Set currency conversion rules if needed
- **Tax Rates**: Map your VAT/tax rates to Macedonian rates (18%, 5%, 0%)

![Transformation Rules](screenshots/transformation-rules.png)

### Step 5: Preview Your Data

Before importing, preview how your data will look:

1. Click **"Preview Data"** button
2. Review the first 10-20 rows of transformed data
3. Check for:
   - Correct character encoding (Cyrillic text displays properly)
   - Proper date formats
   - Correct decimal numbers
   - Tax IDs formatted correctly (MK prefix)
   - Valid email addresses

![Data Preview](screenshots/data-preview.png)

#### Preview Statistics

The preview shows:
- **Total Records**: Number of rows to import
- **Valid Records**: Rows passing validation
- **Warnings**: Rows with non-critical issues
- **Errors**: Rows that will be skipped

### Step 6: Run Dry-Run Validation

Before the actual import, run a dry-run test:

1. Click **"Run Dry-Run"** button
2. The wizard simulates the import without saving data
3. All validation rules are checked
4. You'll see a detailed report of:
   - Records that will succeed
   - Records that will fail
   - Duplicate detection results
   - Constraint violation warnings

![Dry-Run Results](screenshots/dry-run.png)

**Review the Dry-Run Report:**
- ✅ Green: Records ready to import
- ⚠ Yellow: Records with warnings (will import but review recommended)
- ✗ Red: Records that will be skipped (errors must be fixed)

### Step 7: Start the Import

When you're satisfied with the preview and dry-run:

1. Click **"Start Import"** button
2. Confirm the import in the dialog box
3. The import process begins immediately

**During Import:**
- Real-time progress bar shows completion percentage
- Current stage indicator (Customers → Items → Invoices → Payments)
- Record counter (e.g., "Importing customers: 150/500")
- Estimated time remaining

![Import Progress](screenshots/import-progress.png)

**Import Stages:**
1. **Customers**: Creates customer records first
2. **Items**: Imports products and services
3. **Invoices**: Creates invoices with line items
4. **Payments**: Links payments to invoices
5. **Expenses**: Imports expense records

**⚠ Important:** Do not close your browser window during import!

### Step 8: Review Import Results

After completion, you'll see the import summary:

**Success Statistics:**
- ✅ Customers Imported: 150/150
- ✅ Items Imported: 450/450
- ✅ Invoices Imported: 1,200/1,250
- ⚠ Payments Imported: 980/1,000

**Error Details:**
- 50 invoices skipped (duplicate invoice numbers)
- 20 payments skipped (missing invoice references)
- Download error log for detailed information

![Import Summary](screenshots/import-summary.png)

#### Download Import Report

Click **"Download Report"** to get:
- Complete import statistics
- List of successfully imported records
- Detailed error log with row numbers
- Recommendations for fixing failed records

### Step 9: Verify Imported Data

After import, verify your data:

1. Go to **Customers** and check a few records
2. Review **Items** for correct prices and descriptions
3. Open some **Invoices** to verify line items and totals
4. Check **Payments** are linked to correct invoices

**Data Integrity Checks:**
- ✓ Customer names display correctly (Cyrillic characters)
- ✓ Tax IDs formatted as MK4080003501234
- ✓ Dates are in correct format
- ✓ Invoice totals match (subtotal + tax = total)
- ✓ Payments linked to invoices

### Step 10: Handle Errors (If Any)

If some records failed to import:

1. Download the error log from import summary
2. Fix errors in your source file
3. Re-upload only the failed records
4. The wizard will skip duplicates automatically

**Common Fixes:**
- Add missing required fields
- Correct invalid email formats
- Fix duplicate invoice numbers
- Ensure customer references exist before importing invoices

---

## Field Mapping Guide

### Customer Fields

| Facturino Field | Required | Description | Example |
|----------------|----------|-------------|---------|
| `name` | Yes | Customer/Company name | Македонска Трговска ДОО |
| `email` | Recommended | Primary email address | info@kompanija.mk |
| `phone` | No | Phone number | +389 2 123 4567 |
| `vat_number` (Tax ID) | Yes | VAT registration number | MK4080003501234 |
| `contact_name` | No | Contact person name | Петар Петровски |
| `website` | No | Company website | https://kompanija.mk |
| `address_street_1` | No | Street address | Бул. Кирил и Методиј 54 |
| `city` | No | City | Скопје |
| `state` | No | State/Region | Скопски Регион |
| `zip` | No | Postal code | 1000 |
| `country` | No | Country code | MK |

**Field Mapping Tips:**
- If your export has "ЕДБ", "EMBG", or "Danocen Broj", map to `vat_number`
- "Партнер", "Klient", or "Kupac" should map to `name`
- "Adresa" or "Ulica" maps to `address_street_1`

### Item Fields

| Facturino Field | Required | Description | Example |
|----------------|----------|-------------|---------|
| `name` | Yes | Item/product name | Услуга консалтинг |
| `description` | Recommended | Detailed description | Професионални консалтинг услуги |
| `price` | Yes | Unit price | 2500.00 |
| `unit_name` | Yes | Unit of measure | час (hour), парче (piece), кг |
| `sku` | No | Stock keeping unit | CONS-001 |
| `tax_rate` | No | Default tax rate | 18 |

**Field Mapping Tips:**
- "Производ", "Artikal", or "Stavka" should map to `name`
- "Cena", "Cijena", or "Price" maps to `price`
- "Единица", "Jedinica", or "Unit" maps to `unit_name`
- "Шифра", "Kod", or "Code" maps to `sku`

### Invoice Fields

| Facturino Field | Required | Description | Example |
|----------------|----------|-------------|---------|
| `invoice_number` | Yes | Unique invoice number | ФАК-2024-0001 |
| `customer_name` | Yes | Customer name (must exist) | Македонска Трговска ДОО |
| `invoice_date` | Yes | Invoice issue date | 25.11.2024 |
| `due_date` | Recommended | Payment due date | 25.12.2024 |
| `sub_total` | Yes | Subtotal before tax | 10000.00 |
| `tax` | Yes | Tax amount (VAT/ДДВ) | 1800.00 |
| `total` | Yes | Total including tax | 11800.00 |
| `discount` | No | Discount percentage | 10 |
| `discount_val` | No | Discount amount | 1000.00 |
| `notes` | No | Invoice notes | Плаќање во рок од 30 дена |
| `status` | No | Invoice status | SENT, PAID, DRAFT |

**Invoice Line Items (if separate CSV):**

| Facturino Field | Required | Description | Example |
|----------------|----------|-------------|---------|
| `invoice_number` | Yes | Parent invoice number | ФАК-2024-0001 |
| `item_name` | Yes | Product/service name | Консалтинг услуги |
| `quantity` | Yes | Quantity ordered | 10 |
| `price` | Yes | Unit price | 1000.00 |
| `discount_val` | No | Line item discount | 100.00 |
| `tax_rate` | Yes | Tax rate (%) | 18 |

**Field Mapping Tips:**
- "Broj Faktura", "Invoice No", or "Број фактура" maps to `invoice_number`
- "Kupuvac", "Customer", or "Купувач" maps to `customer_name`
- "Datum", "Date", or "Датум" maps to `invoice_date`
- "Osnovica", "Subtotal", or "Основица" maps to `sub_total`
- "DDV", "PDV", or "Tax" maps to `tax`
- "Vkupno", "Total", or "Вкупно" maps to `total`

### Payment Fields

| Facturino Field | Required | Description | Example |
|----------------|----------|-------------|---------|
| `invoice_number` | Yes | Reference invoice | ФАК-2024-0001 |
| `payment_date` | Yes | Payment date | 30.11.2024 |
| `amount` | Yes | Payment amount | 11800.00 |
| `payment_method` | Recommended | Payment method | BANK_TRANSFER, CASH, CARD |
| `notes` | No | Payment notes | Уплата од 30.11.2024 |
| `payment_number` | No | Payment reference | УПЛ-2024-0001 |

**Payment Methods:**
- `BANK_TRANSFER` - Банкарски трансфер
- `CASH` - Готовина
- `CARD` - Картичка
- `CHECK` - Чек

**Field Mapping Tips:**
- "Uplata", "Payment", or "Плаќање" maps to `amount`
- "Nacin Plakanje" or "Payment Method" maps to `payment_method`
- Always ensure invoice references exist before importing payments

### Expense Fields

| Facturino Field | Required | Description | Example |
|----------------|----------|-------------|---------|
| `expense_date` | Yes | Expense date | 15.11.2024 |
| `amount` | Yes | Expense amount | 5000.00 |
| `category` | Yes | Expense category | Office Supplies, Travel |
| `vendor_name` | Recommended | Vendor name | Канцелариски Материјали ДОО |
| `notes` | No | Expense notes | Купување канцелариски материјал |

---

## CSV Format Guide

### General CSV Requirements

**File Structure:**
- First row must contain column headers
- Each subsequent row is one record
- Fields separated by comma (`,`) or semicolon (`;`)
- Text fields can be quoted with double quotes (`"`)

**Encoding:**
- **Preferred**: UTF-8 with BOM (for Cyrillic)
- **Alternative**: UTF-8 without BOM
- **Legacy**: Windows-1251 (auto-detected)

**Line Endings:**
- Windows (CRLF): `\r\n`
- Unix/Mac (LF): `\n`
- Both are supported

### Required vs Optional Fields

#### Customers CSV

**Required:**
```csv
name,email,vat_number
"Македонска Трговска ДОО",info@kompanija.mk,MK4080003501234
```

**Complete (with optional fields):**
```csv
name,email,vat_number,phone,contact_name,address_street_1,city,zip,country
"Македонска Трговска ДОО",info@kompanija.mk,MK4080003501234,"+389 2 123 4567","Петар Петровски","Бул. Кирил и Методиј 54","Скопје",1000,MK
```

#### Items CSV

**Required:**
```csv
name,price,unit_name
"Консалтинг услуги",2500.00,час
```

**Complete (with optional fields):**
```csv
name,description,price,unit_name,sku,tax_rate
"Консалтинг услуги","Професионални консалтинг услуги",2500.00,час,CONS-001,18
```

#### Invoices CSV

**Required:**
```csv
invoice_number,customer_name,invoice_date,sub_total,tax,total
"ФАК-2024-0001","Македонска Трговска ДОО",25.11.2024,10000.00,1800.00,11800.00
```

**Complete (with optional fields):**
```csv
invoice_number,customer_name,invoice_date,due_date,sub_total,tax,total,discount,discount_val,notes,status
"ФАК-2024-0001","Македонска Трговска ДОО",25.11.2024,25.12.2024,10000.00,1800.00,11800.00,0,0,"Плаќање во рок од 30 дена",SENT
```

### Date Formats Accepted

The wizard auto-detects these date formats:

| Format | Example | Description |
|--------|---------|-------------|
| `dd.mm.yyyy` | 25.11.2024 | Dot separator (common in Macedonia) |
| `dd/mm/yyyy` | 25/11/2024 | Slash separator |
| `dd-mm-yyyy` | 25-11-2024 | Dash separator |
| `yyyy-mm-dd` | 2024-11-25 | ISO format |
| `d.m.yyyy` | 5.1.2024 | Single digit day/month |
| `m/d/yyyy` | 11/25/2024 | US format (auto-detected) |

**Time Support:**
- Full datetime: `25.11.2024 14:30:00`
- Date only: `25.11.2024`

### Number Formats Accepted

The wizard handles various number formats:

| Format | Example | Description |
|--------|---------|-------------|
| Dot decimal | 1000.50 | US/International format |
| Comma decimal | 1000,50 | European format (Macedonia) |
| Space thousands | 1 000,50 | Space as thousand separator |
| Dot thousands | 1.000,50 | Dot as thousand separator |
| No separator | 1000.50 | Plain numbers |

**Scientific Notation:** `1.5E+3` (converted to 1500)

**Percentages:**
- With symbol: `18%` (converted to 18)
- Decimal: `0.18` (converted to 18 if in tax rate context)

### Character Encoding (UTF-8)

**For Cyrillic Text:**

1. **In Excel:**
   - File → Save As
   - Format: CSV UTF-8 (Comma delimited)
   - This preserves Macedonian characters

2. **In Notepad++ (Windows):**
   - Encoding → Convert to UTF-8
   - Save the file

3. **In TextEdit (Mac):**
   - Format → Make Plain Text
   - Save with UTF-8 encoding

**Testing Encoding:**
Open your CSV in a text editor and verify:
- Македонски текст displays correctly
- Special characters (Ќ, Љ, Њ, Џ, Ѓ, Ж, Ш) are not replaced with ?
- Accented letters (ć, č, š, ž) display properly

### Special Characters in CSV

**Commas in Text:**
Use quotes around fields containing commas:
```csv
name,address,city
"Македонска Трговска, ДОО","Бул. Кирил и Методиј 54","Скопје"
```

**Quotes in Text:**
Escape quotes by doubling them:
```csv
name,notes
"Компанија ""Македонија"" ДОО","Премиум услуги"
```

**Newlines in Text:**
Quote fields containing newlines:
```csv
name,address,notes
"Компанија ДОО","Бул. Кирил и Методиј 54
Скопје, Македонија","Multi-line
notes here"
```

### Sample CSV Templates

Download sample templates:
- [customers_template.csv](templates/customers_template.csv)
- [items_template.csv](templates/items_template.csv)
- [invoices_template.csv](templates/invoices_template.csv)
- [payments_template.csv](templates/payments_template.csv)
- [expenses_template.csv](templates/expenses_template.csv)

---

## Common Errors and Solutions

### Upload Errors

#### Error: "Unsupported file type"

**Cause:** File format not recognized
**Solution:**
- Ensure file has extension: `.csv`, `.xlsx`, `.xls`, or `.xml`
- If using Numbers (Mac), export as Excel format
- Avoid `.txt` or `.doc` files

#### Error: "File size exceeds maximum limit"

**Cause:** File larger than 1GB
**Solution:**
- Split large datasets into multiple files
- Remove unnecessary columns from export
- Export data in date ranges (e.g., by year)

#### Error: "File appears to be corrupted"

**Cause:** File encoding issue or malformed structure
**Solution:**
- Re-export from source software
- Open in Excel and re-save as CSV UTF-8
- Check for special characters or NULL bytes

### Validation Errors

#### Error: "Required field 'name' is missing"

**Cause:** Required field not mapped or empty
**Solution:**
- Review field mappings
- Ensure source column is not empty
- Map correct source field to required target field

#### Error: "Invalid email format"

**Cause:** Email address doesn't match pattern
**Solution:**
- Check emails contain @ symbol
- Remove spaces around email addresses
- Ensure format is: `user@domain.ext`

#### Error: "Invalid date format"

**Cause:** Date not recognized
**Solution:**
- Use supported date formats (see [Date Formats](#date-formats-accepted))
- Avoid text like "today" or "N/A"
- Ensure consistency throughout the file

#### Error: "Duplicate invoice number detected"

**Cause:** Invoice number already exists
**Solution:**
- Check existing invoices in Facturino
- Add prefix to invoice numbers (e.g., OLD-FAK-001)
- Use unique invoice numbers

### Import Errors

#### Error: "Customer 'Компанија ДОО' not found"

**Cause:** Invoice references customer that doesn't exist
**Solution:**
- Import customers before invoices
- Check customer names match exactly (including spaces)
- Verify Cyrillic spelling is identical

#### Error: "Database constraint violation"

**Cause:** Referential integrity issue
**Solution:**
- Import in correct order: Customers → Items → Invoices → Payments
- Ensure all references exist before importing dependent records
- Check foreign key relationships

#### Error: "Tax rate must be between 0 and 100"

**Cause:** Invalid tax rate value
**Solution:**
- Use percentage values (0-100), not decimals (0-1)
- Common rates: 18, 5, 0
- Check for typos or extra characters

### Character Encoding Errors

#### Error: "Cyrillic characters displaying as ???"

**Cause:** File not UTF-8 encoded
**Solution:**
- Re-save file as UTF-8 (see [Character Encoding](#character-encoding-utf-8))
- In Excel: Save As → CSV UTF-8
- Verify encoding in text editor before upload

#### Error: "Special characters like Ќ, Љ not displaying"

**Cause:** Encoding mismatch
**Solution:**
- Use UTF-8 with BOM
- Avoid Windows-1252 encoding
- Re-export from source with proper encoding

### Mapping Errors

#### Error: "Cannot map 'broj_faktura' to any field"

**Cause:** Field mapper didn't find a match
**Solution:**
- Manually select the target field from dropdown
- Use field mapping hints in the interface
- Check field name spelling

#### Error: "Confidence score too low for auto-mapping"

**Cause:** Uncertain field mapping
**Solution:**
- Review suggested mappings manually
- Choose correct field from alternatives
- Increase confidence threshold if appropriate

---

## FAQ

### General Questions

**Q: How long does import take?**
A: Depends on file size:
- 100 records: ~30 seconds
- 1,000 records: ~2-3 minutes
- 10,000 records: ~15-20 minutes
- 100,000 records: ~2-3 hours

**Q: Can I cancel an import in progress?**
A: Yes, click "Cancel Import" button. Imported records will be rolled back.

**Q: What happens if my browser crashes during import?**
A: The import continues on the server. Reload the page to see progress. If import was interrupted, it can be rolled back.

**Q: Can I import data multiple times?**
A: Yes, but duplicate detection will skip existing records based on unique identifiers (invoice numbers, email addresses, etc.).

**Q: Is there a limit to how many records I can import?**
A: No hard limit, but performance is best under 50,000 records per import. For larger datasets, split into multiple imports.

### Data Questions

**Q: Will my existing data be overwritten?**
A: No, imports only add new records. Existing records are not modified unless you explicitly enable update mode.

**Q: How are duplicates detected?**
A: By unique identifiers:
- Customers: Email and Tax ID
- Items: Name and SKU
- Invoices: Invoice Number
- Payments: Payment Number and Invoice Reference

**Q: Can I import invoices without customers?**
A: No, customers must be imported first. Invoices require existing customer records.

**Q: What if I have invoices in multiple currencies?**
A: Currently, all amounts are converted to MKD. Exchange rates are applied during import.

**Q: Can I import historical data from multiple years?**
A: Yes, there are no date restrictions. You can import data from any time period.

### Technical Questions

**Q: What file encoding should I use?**
A: UTF-8 is preferred, especially for Cyrillic text. UTF-8 with BOM is best for Excel exports.

**Q: Do I need to format numbers in a specific way?**
A: No, the wizard auto-detects number formats. Both `1000.50` and `1000,50` are supported.

**Q: Can I import XML files from e-Faktura system?**
A: Yes, XML import is supported for UBL invoices and custom XML formats.

**Q: What happens to failed records?**
A: They are logged in the error report. You can fix them and re-import.

**Q: Is my data secure during import?**
A: Yes, files are uploaded over HTTPS and stored encrypted. Files are deleted after import completion.

### Macedonian-Specific Questions

**Q: Are Macedonian VAT rates automatically applied?**
A: Yes, standard rates (18%, 5%, 0%) are recognized. You can map custom rates during field mapping.

**Q: How are Tax IDs (ЕДБ) validated?**
A: Tax IDs are validated for proper format (13 digits) and MK prefix is added automatically.

**Q: Can I import data with mixed Cyrillic and Latin text?**
A: Yes, both scripts are fully supported in the same file.

**Q: What if my software uses old Yugoslav terminology?**
A: The field mapper recognizes Serbian and older Macedonian terms (партнер, kupac, firma, etc.).

---

## Troubleshooting

### Diagnostic Steps

If you encounter issues:

1. **Check Prerequisites**
   - ✓ Logged in as administrator?
   - ✓ Company selected?
   - ✓ Feature enabled?

2. **Verify File Format**
   - ✓ Correct file extension (.csv, .xlsx, .xml)?
   - ✓ UTF-8 encoding?
   - ✓ File size under 1GB?

3. **Review Field Mappings**
   - ✓ All required fields mapped?
   - ✓ Field names recognized?
   - ✓ Data types compatible?

4. **Check Data Quality**
   - ✓ No empty required fields?
   - ✓ Valid date formats?
   - ✓ Valid email addresses?
   - ✓ Numeric fields contain numbers?

5. **Run Dry-Run**
   - ✓ Test import before committing
   - ✓ Review validation errors
   - ✓ Fix issues in source file

### Getting Help

**Error Logs:**
- Download the import error log from the summary page
- Check Laravel logs: `storage/logs/laravel.log`
- Review import logs in database

**Support Resources:**
- Documentation: [/docs/migration-wizard](../docs/migration-wizard)
- Community Forum: [forum.facturino.mk](https://forum.facturino.mk)
- Email Support: support@facturino.mk
- Live Chat: Available 9 AM - 5 PM (Monday-Friday)

**Reporting Issues:**
When contacting support, include:
- Import error log
- Source file sample (first 10 rows)
- Screenshots of error messages
- Browser and version
- Company ID

### Performance Tips

**For Large Imports:**
- Split files into chunks of 10,000 records
- Import during off-peak hours
- Close unnecessary browser tabs
- Use wired internet connection (not Wi-Fi)
- Disable browser extensions

**For Better Accuracy:**
- Review auto-mapped fields before importing
- Run dry-run validation first
- Test with small sample file (10-20 rows)
- Clean data in source system before export

---

## Appendix

### Glossary

**Terms used in this guide:**

- **CSV**: Comma-Separated Values, a text file format
- **Excel**: Spreadsheet file format (.xlsx, .xls)
- **XML**: Extensible Markup Language, structured data format
- **UTF-8**: Unicode character encoding supporting all languages
- **Field Mapping**: Matching source fields to target fields
- **Dry-Run**: Test import without saving data
- **Rollback**: Undo import and restore previous state
- **Tax ID (ЕДБ)**: Единствен даночен број, unique tax identification number
- **VAT (ДДВ)**: Данок на додадена вредност, value-added tax
- **MKD**: Macedonian Denar, official currency

### Quick Reference

**Import Order:**
1. Customers
2. Items
3. Invoices
4. Payments
5. Expenses

**Required Fields:**
- Customers: `name`, `vat_number`
- Items: `name`, `price`, `unit_name`
- Invoices: `invoice_number`, `customer_name`, `invoice_date`, `total`
- Payments: `invoice_number`, `payment_date`, `amount`

**File Formats:**
- CSV: UTF-8, comma or semicolon separated
- Excel: .xlsx, .xls, .ods
- XML: UBL 2.1, custom formats

**Support:**
- Email: support@facturino.mk
- Forum: forum.facturino.mk
- Docs: /docs/migration-wizard

---

**Document Version:** 1.0
**Last Updated:** November 12, 2025
**Feedback:** docs@facturino.mk

---

© 2025 Facturino. All rights reserved.
