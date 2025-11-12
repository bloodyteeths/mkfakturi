# Migration Wizard Quick Start Guide

**Get your data imported in 5 minutes!**

---

## 🚀 5-Minute Quick Start

### Step 1: Prepare Your Files (1 minute)

Export your data from your current software:

- **Onivo**: Извештаи → Извоз на податоци → CSV
- **Megasoft**: Podaci → Eksport → Excel
- **Manager.io**: Export → Excel
- **Other**: Export to CSV or Excel format

✅ **Files ready?** Continue to Step 2

---

### Step 2: Access Migration Wizard (30 seconds)

1. Log into Facturino as admin
2. Go to **Settings** → **Data Import** → **Migration Wizard**

```
Dashboard → Settings → Data Import → Migration Wizard
```

✅ **Wizard open?** Continue to Step 3

---

### Step 3: Upload & Map (2 minutes)

1. **Choose your source system** (Onivo, Megasoft, etc.)
2. **Upload your file** (drag & drop or click to browse)
3. **Review auto-mapped fields** (usually correct)
4. **Click "Preview Data"** to see how it looks

✅ **Preview looks good?** Continue to Step 4

---

### Step 4: Import (1 minute)

1. Click **"Run Dry-Run"** (optional but recommended)
2. Click **"Start Import"**
3. ☕ Wait for completion
4. **Download report** when finished

✅ **Done!** Your data is now in Facturino

---

## 📊 Visual Process Flow

```
┌─────────────────┐
│ 1. EXPORT DATA  │
│  from old       │
│  software       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 2. ACCESS       │
│  Migration      │
│  Wizard         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 3. UPLOAD FILE  │
│  & Review       │
│  Mappings       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 4. PREVIEW &    │
│  Validate       │
│  (Dry-Run)      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 5. START        │
│  IMPORT         │
│  (Live)         │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ ✅ COMPLETE!    │
│  Verify data    │
│  in Facturino   │
└─────────────────┘
```

---

## 📥 Download Links

### Sample CSV Templates

Right-click and "Save As" to download:

- [customers_template.csv](templates/customers_template.csv) - Customer data template
- [items_template.csv](templates/items_template.csv) - Items/products template
- [invoices_template.csv](templates/invoices_template.csv) - Invoice data template
- [payments_template.csv](templates/payments_template.csv) - Payment records template
- [expenses_template.csv](templates/expenses_template.csv) - Expense records template

### Sample Files with Data

Example files to see the format:

- [sample_customers.csv](examples/sample_customers.csv) - 10 sample customers
- [sample_items.csv](examples/sample_items.csv) - 20 sample items
- [sample_invoices.csv](examples/sample_invoices.csv) - 5 sample invoices

---

## 🎯 Essential Steps Only

### What You MUST Do

✅ **1. Export data as CSV or Excel**
✅ **2. Log in as administrator**
✅ **3. Upload file to Migration Wizard**
✅ **4. Verify field mappings**
✅ **5. Click "Start Import"**

### What You CAN Skip (First Time)

⏭️ Detailed field customization
⏭️ Transformation rules configuration
⏭️ Advanced validation options
⏭️ Error log analysis (if no errors)

---

## 📋 Checklist Before You Start

### Pre-Import Checklist

- [ ] Administrator account access
- [ ] Company created in Facturino
- [ ] Data exported from old software
- [ ] Files saved as CSV or Excel
- [ ] Cyrillic text displays correctly (if applicable)
- [ ] Backup of original data created

### During Import Checklist

- [ ] Correct source system selected
- [ ] File uploaded successfully (green checkmark)
- [ ] Required fields mapped (no red warnings)
- [ ] Data preview looks correct
- [ ] Dry-run completed without critical errors

### Post-Import Checklist

- [ ] Import completed (100%)
- [ ] Success message displayed
- [ ] Sample records verified in Facturino
- [ ] Error log downloaded (if errors occurred)
- [ ] Import report saved for records

---

## ⚡ Quick Tips

### For Fastest Import

1. **Start with customers** - Always import customers first
2. **Use auto-mapping** - It's usually correct
3. **Skip dry-run** - Only if you're confident (not recommended for first import)
4. **Import in order** - Customers → Items → Invoices → Payments

### For Most Accurate Import

1. **Run dry-run first** - Catch errors before importing
2. **Review mappings** - Verify each field mapping
3. **Check preview** - Ensure data looks correct
4. **Start small** - Test with 10-20 records first

### For Troubleshooting

1. **Check encoding** - Save as UTF-8 if Cyrillic looks wrong
2. **Verify format** - Ensure dates and numbers are consistent
3. **Review errors** - Download error log if import fails
4. **Try again** - Fix errors in source file and re-upload

---

## 🆘 Common Issues (Quick Fix)

| Issue | Quick Fix |
|-------|-----------|
| File won't upload | Check file extension (.csv, .xlsx) |
| Cyrillic looks wrong | Re-save file as UTF-8 |
| Fields not mapping | Manually select from dropdown |
| Duplicate errors | Invoice numbers must be unique |
| Customer not found | Import customers before invoices |
| Import taking too long | File might be too large, split it |

---

## 🔢 Import in Correct Order

**Always follow this sequence:**

```
1️⃣ CUSTOMERS
   ↓
2️⃣ ITEMS
   ↓
3️⃣ INVOICES
   ↓
4️⃣ PAYMENTS
   ↓
5️⃣ EXPENSES
```

**Why?** Invoices need customers to exist. Payments need invoices to exist.

---

## 📱 Interface Quick Guide

### 1. Source Selection Screen

```
┌────────────────────────────────────────┐
│  Choose Your Source System:            │
│                                         │
│  [ Onivo ]  [ Megasoft ]  [ Manager ]  │
│                                         │
│  [ Effect Plus ]  [ Eurofaktura ]      │
│                                         │
│  [ Generic CSV/Excel ]                 │
│                                         │
│              [Next Step →]              │
└────────────────────────────────────────┘
```

### 2. File Upload Screen

```
┌────────────────────────────────────────┐
│  Upload Your Data File                 │
│                                         │
│  ╔════════════════════════════════╗    │
│  ║  Drag file here or click to   ║    │
│  ║  browse                        ║    │
│  ╚════════════════════════════════╝    │
│                                         │
│  Supported: CSV, Excel, XML            │
│  Max size: 1GB                         │
│                                         │
│  [← Back]           [Next Step →]      │
└────────────────────────────────────────┘
```

### 3. Field Mapping Screen

```
┌────────────────────────────────────────┐
│  Review Field Mappings                 │
│                                         │
│  Source Field     →  Target Field      │
│  ─────────────────────────────────────│
│  Партнер         →  name        ✓      │
│  ЕДБ             →  vat_number  ✓      │
│  Email           →  email       ✓      │
│  Телефон         →  phone       ✓      │
│                                         │
│  [Auto-Map]  [Reset]                   │
│  [← Back]           [Preview Data →]   │
└────────────────────────────────────────┘
```

### 4. Import Progress Screen

```
┌────────────────────────────────────────┐
│  Importing Data...                     │
│                                         │
│  ████████████████░░░░░░░░░░  65%       │
│                                         │
│  Stage: Importing Invoices             │
│  Records: 650 / 1,000                  │
│  Time remaining: ~2 minutes            │
│                                         │
│  [Cancel Import]                       │
└────────────────────────────────────────┘
```

### 5. Completion Screen

```
┌────────────────────────────────────────┐
│  ✅ Import Complete!                   │
│                                         │
│  Customers:  150 / 150   ✓             │
│  Items:      450 / 450   ✓             │
│  Invoices: 1,200 / 1,250 ⚠ (50 errors)│
│  Payments:   980 / 1,000 ⚠ (20 errors)│
│                                         │
│  [Download Report]  [View in Facturino]│
└────────────────────────────────────────┘
```

---

## 🎓 First-Time User Path

### Never used migration wizard before? Follow this:

**Step 1: Test with Sample File (5 min)**
1. Download `sample_customers.csv`
2. Upload to wizard
3. Complete import
4. Verify 10 customers appear in Facturino

**Step 2: Import Your Customers (10 min)**
1. Export customers from old software
2. Upload to wizard
3. Review mappings
4. Run dry-run
5. Import if dry-run succeeds

**Step 3: Import Items (10 min)**
1. Export items/products
2. Upload and map
3. Import

**Step 4: Import Invoices (15 min)**
1. Export invoices
2. Upload and map
3. Review carefully (most complex)
4. Import

**Step 5: Import Payments (10 min)**
1. Export payments
2. Upload and map
3. Import

**Total Time: ~50 minutes** for complete migration

---

## 🌍 Language Support

### Македонски (Macedonian)

Целосна поддршка за:
- Кирилица во сите полиња
- Македонски називи на полиња
- ДДВ стапки (18%, 5%, 0%)
- ЕДБ валидација
- Македонски формати на датуми

### English

Full support for:
- International field names
- Standard date formats
- USD/EUR/MKD currencies
- English language interface

### Српски (Serbian)

Поддршка за:
- Српске називе поља
- ПДВ стапке
- ПИБ валидација

---

## 📞 Quick Support

**Need help?**

- 📧 **Email**: support@facturino.mk
- 💬 **Forum**: forum.facturino.mk
- 📚 **Full Guide**: See USER_GUIDE.md
- 🐛 **Bug Report**: github.com/facturino/issues

**Response time**: Usually within 4 hours (business days)

---

## ⏱️ Time Estimates by File Size

| Records | File Size | Upload | Import | Total |
|---------|-----------|--------|--------|-------|
| 10 | 5 KB | 2s | 10s | 5 min |
| 100 | 50 KB | 3s | 30s | 5 min |
| 1,000 | 500 KB | 5s | 2 min | 10 min |
| 10,000 | 5 MB | 15s | 15 min | 25 min |
| 100,000 | 50 MB | 45s | 2 hrs | 2.5 hrs |

*Times include upload, mapping review, and import. Add 5-10 minutes for first-time setup.*

---

## 🎯 Success Indicators

### You're Doing It Right If...

✅ Progress bar moving smoothly
✅ No red error messages
✅ Field mappings show green checkmarks
✅ Preview data looks correct
✅ Import completes at 100%

### Something's Wrong If...

❌ Upload fails repeatedly
❌ Many red error messages in mapping
❌ Preview shows garbled text (encoding issue)
❌ Import stuck at 0% for >5 minutes
❌ Browser shows error popup

**If something's wrong:** See Troubleshooting section in USER_GUIDE.md

---

## 🎨 Visual Cheat Sheet

### Required Fields (Must Have)

```
CUSTOMERS:
  ├─ name ⚠ Required
  ├─ email (recommended)
  └─ vat_number ⚠ Required

ITEMS:
  ├─ name ⚠ Required
  ├─ price ⚠ Required
  └─ unit_name ⚠ Required

INVOICES:
  ├─ invoice_number ⚠ Required
  ├─ customer_name ⚠ Required
  ├─ invoice_date ⚠ Required
  ├─ sub_total ⚠ Required
  ├─ tax ⚠ Required
  └─ total ⚠ Required

PAYMENTS:
  ├─ invoice_number ⚠ Required
  ├─ payment_date ⚠ Required
  └─ amount ⚠ Required
```

### File Format Quick Reference

```
CSV Format:
  ┌──────────────────────────────┐
  │ name,email,vat_number        │ ← Headers
  │ "Company A",a@test.com,MK123 │ ← Data row 1
  │ "Company B",b@test.com,MK456 │ ← Data row 2
  └──────────────────────────────┘

Excel Format:
  ┌────────┬─────────┬────────────┐
  │ name   │ email   │ vat_number │ ← Headers in row 1
  ├────────┼─────────┼────────────┤
  │ Comp A │ a@test  │ MK123      │ ← Data in row 2
  │ Comp B │ b@test  │ MK456      │ ← Data in row 3
  └────────┴─────────┴────────────┘
```

---

## 🔥 Pro Tips

### Speed Up Your Import

1. **Prepare data ahead** - Export and clean data before starting
2. **Close other apps** - Free up memory and CPU
3. **Use CSV format** - Faster than Excel for large files
4. **Split large files** - 10,000 records per file max for speed
5. **Import during off-hours** - Less server load = faster processing

### Avoid Common Mistakes

1. **Don't** close browser during import
2. **Don't** import invoices before customers
3. **Don't** use duplicate invoice numbers
4. **Don't** mix currencies in one file
5. **Don't** skip the dry-run on first import

### Make Mapping Easier

1. **Use consistent column names** in your exports
2. **Remove empty columns** before upload
3. **Clean data** (remove extra spaces, fix typos)
4. **Test with 10 rows first** before full import
5. **Save mapping presets** for future use

---

## 📖 Next Steps

After completing your quick start:

1. ✅ **Verify imported data** - Check a few records in Facturino
2. 📚 **Read full USER_GUIDE.md** - For advanced features
3. 🎯 **Import remaining data** - If you only did a test
4. 🔄 **Set up regular imports** - For ongoing data sync (if needed)
5. 🎓 **Train your team** - Share this guide with colleagues

---

## 📝 Quick Reference Card

**Print or bookmark this:**

```
╔══════════════════════════════════════════════╗
║  MIGRATION WIZARD - QUICK REFERENCE          ║
╠══════════════════════════════════════════════╣
║                                              ║
║  Import Order:                               ║
║  1. Customers → 2. Items → 3. Invoices →     ║
║  4. Payments → 5. Expenses                   ║
║                                              ║
║  Required Customer Fields:                   ║
║  • name  • vat_number                        ║
║                                              ║
║  Required Invoice Fields:                    ║
║  • invoice_number  • customer_name           ║
║  • invoice_date  • total                     ║
║                                              ║
║  File Formats:                               ║
║  • CSV (UTF-8)  • Excel (.xlsx)  • XML       ║
║                                              ║
║  Max File Size: 1GB                          ║
║                                              ║
║  Support: support@facturino.mk               ║
║                                              ║
╚══════════════════════════════════════════════╝
```

---

**Ready to migrate? Let's go! 🚀**

---

**Document Version:** 1.0
**Estimated Reading Time:** 5 minutes
**Estimated Completion Time:** 5-50 minutes (depending on data size)

---

Need more details? See the comprehensive [USER_GUIDE.md](USER_GUIDE.md)

---

© 2025 Facturino. All rights reserved.
