# Facturino Migration Wizard - Quick Reference Guide

**One-Page Cheat Sheet for Data Import**

---

## 📋 6 SIMPLE STEPS

```
1️⃣ DOWNLOAD    2️⃣ FILL DATA    3️⃣ UPLOAD    4️⃣ MAP FIELDS    5️⃣ VALIDATE    6️⃣ IMPORT
   Template       in CSV          Files        & Review          Data           Complete
   (2 min)        (varies)       (1 min)       (2 min)          (1 min)        (1 min)
```

---

## 🎯 REQUIRED FIELDS

| Entity | Required Fields | Optional Fields |
|--------|----------------|-----------------|
| **Customers** | name*, email*, currency* | phone, address, vat_number, website |
| **Invoices** | invoice_number*, customer_email*, invoice_date*, due_date*, total* | subtotal, tax, status, notes |
| **Items** | name*, price*, unit* | description, category, sku, tax_type, tax_rate |
| **Payments** | invoice_number*, payment_date*, amount* | payment_method, reference, notes |

---

## 🇲🇰 MACEDONIAN DATA FORMATS

### VAT Numbers
```
✓ Correct: MK4080012562345 (MK + 13 digits = 15 chars total)
✗ Wrong:   4080012562345   (missing MK prefix)
✗ Wrong:   MK123           (too short)
```

### Currency Codes
```
MKD - Macedonian Denar (денар)
EUR - Euro
USD - US Dollar
```

### Tax Rates
```
Standard:  18%  (ДДВ 18%) - Most goods/services
Reduced:    5%  (ДДВ 5%)  - Specific goods
Zero:       0%  (ДДВ 0%)  - Exports, exempt items
```

### Cyrillic Characters
```
✓ Fully supported: Македонска Трговска ДООЕЛ
✓ Use UTF-8 encoding when saving CSV files
```

---

## 📅 DATE FORMAT

```
Required Format:  YYYY-MM-DD
✓ Correct: 2025-01-15
✗ Wrong:   15.01.2025
✗ Wrong:   01/15/2025
✗ Wrong:   15-01-2025
```

---

## 💾 FILE REQUIREMENTS

| Requirement | Specification |
|-------------|---------------|
| **Format** | CSV UTF-8 (not Excel .xlsx) |
| **Max Size** | 50 MB per file |
| **Encoding** | UTF-8 (for Cyrillic support) |
| **Headers** | First row must contain column names |
| **Delimiter** | Comma (,) |

---

## 🔧 COMMON ISSUES & QUICK FIXES

| Issue | Fix |
|-------|-----|
| ❌ Characters appear as ??? | Save file as "CSV UTF-8" in Excel |
| ❌ File won't upload | Check format is CSV, not .xlsx |
| ❌ "Invalid date" error | Use YYYY-MM-DD format |
| ❌ "Invalid VAT number" | Add "MK" prefix + 13 digits |
| ❌ Payments not linking | Match invoice_number exactly (case-sensitive) |
| ❌ Duplicate email error | Each customer needs unique email |
| ❌ Import seems frozen | Large datasets take time (1,200 records ≈ 1 min) |

---

## 📊 EXAMPLE CSV DATA

### customers.csv
```csv
name,email,phone,address,vat_number,website,currency
Македонска Трговска ДООЕЛ,info@company.mk,+389 2 123 4567,"ул. Македонија 123, Скопје, 1000",MK4080012562345,https://company.mk,MKD
```

### invoices.csv
```csv
invoice_number,customer_email,invoice_date,due_date,subtotal,tax,total,status,currency,notes
INV-2025-001,info@company.mk,2025-01-15,2025-02-15,10000.00,1800.00,11800.00,SENT,MKD,Месечна услуга
```

### items.csv
```csv
name,description,price,unit,category,sku,tax_type,tax_rate
Веб Развој,Часовна стапка,2500.00,hour,Services,WEB-001,Standard,18.00
```

### payments.csv
```csv
invoice_number,payment_date,amount,payment_method,reference,currency,notes
INV-2025-001,2025-01-16,11800.00,Bank Transfer,PAY-001,MKD,Банкарска уплата
```

---

## ✅ PRE-UPLOAD CHECKLIST

- [ ] All required fields filled (marked with *)
- [ ] Files saved as CSV UTF-8 format
- [ ] Dates in YYYY-MM-DD format
- [ ] VAT numbers: MK + 13 digits
- [ ] Cyrillic text displays correctly
- [ ] Invoice numbers match between invoices and payments
- [ ] All email addresses are unique
- [ ] File size under 50MB
- [ ] No empty rows in CSV files

---

## 📈 PERFORMANCE GUIDELINES

| Dataset Size | Expected Import Time |
|--------------|---------------------|
| 100 records  | ~5 seconds |
| 500 records  | ~25 seconds |
| 1,200 records | ~60 seconds |

**💡 Tip:** For datasets over 1,500 records, consider splitting into multiple files.

---

## 🎓 VALIDATION CHECKS

The system automatically validates:

✓ Required fields are present
✓ Email formats are valid
✓ VAT numbers match MK format
✓ Dates are valid (month 1-12, day 1-31)
✓ Relationships preserved (payments → invoices → customers)
✓ Character encoding is correct
✓ No SQL injection attempts
✓ Numeric fields contain valid numbers

---

## 🚀 SUPPORTED SOURCE SYSTEMS

| System | Export Format | Auto-Mapping |
|--------|--------------|--------------|
| **Onivo Accounting** | CSV, SQL Server | ✓ 95%+ accuracy |
| **Megasoft ERP** | XML, CSV | ✓ 90%+ accuracy |
| **Generic CSV** | CSV | ✓ 85%+ accuracy |
| **Excel Files** | .xlsx, .csv | ✓ 80%+ accuracy |

---

## 📞 NEED HELP?

| Resource | Link/Contact |
|----------|-------------|
| **Help Documentation** | https://help.facturino.mk |
| **Video Tutorial** | 11-minute step-by-step guide |
| **Email Support** | support@facturino.mk |
| **CSV Templates** | Available in Migration Wizard |

---

## 💡 PRO TIPS

1. **Start Small:** Test with 5-10 records first, then import full dataset
2. **Backup First:** Export your current Facturino data before large imports
3. **Use Templates:** Always start with official Facturino CSV templates
4. **Check Encoding:** Preview your CSV in a text editor to verify Cyrillic displays correctly
5. **Invoice Numbers:** Use consistent format (e.g., INV-YYYY-NNN)
6. **Download Report:** Always download the import report for your records
7. **Review First:** Check imported data on Customers/Invoices pages before deleting source files

---

## 🔄 WORKFLOW SUMMARY

```
┌─────────────┐
│   Export    │  Export data from old system
│  Old Data   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Download   │  Get CSV templates from
│  Templates  │  Facturino Migration Wizard
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Format    │  Fill templates with your data
│    Data     │  Ensure correct formats (dates, VAT, etc.)
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Upload    │  Upload CSV files to
│    Files    │  Migration Wizard
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Review    │  Check field mappings
│   Mapping   │  Adjust if needed
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Validate   │  System checks for errors
│    Data     │  Fix any issues found
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   Import    │  Execute import
│  Complete   │  Download report
└─────────────┘
```

---

## 📋 INVOICE STATUS VALUES

Use these exact values in the `status` column:

```
DRAFT          - Invoice not yet finalized
SENT           - Invoice sent to customer
VIEWED         - Customer has opened the invoice
ACCEPTED       - Customer accepted the invoice
REJECTED       - Customer rejected the invoice
PAID           - Fully paid
PARTIALLY_PAID - Partial payment received
OVERDUE        - Past due date, unpaid
EXPIRED        - Expired without payment
DUE            - Payment is currently due
```

---

## 🎯 FIELD MAPPING TIPS

**Auto-Mapping Recognition:**
- Recognizes common column names (customer_name → name)
- Detects date fields automatically
- Identifies currency and amount fields
- Maps Macedonian tax rates (ДДВ)

**Manual Adjustments:**
- Click dropdown to change mapping
- Can skip fields (leave unmapped)
- Preview shows transformation results
- Confidence score indicates accuracy

---

**Version:** 1.0 | **Date:** 2025-11-12 | **Format:** A4 Portrait | **Pages:** 1
