# Intelligent Import System - Testing Guide

## 📋 Prerequisites

Before testing, ensure:
- ✅ Database seeded with mapping rules: `php artisan db:seed --class=MappingRuleSeeder`
- ✅ All intelligent import services are in place
- ✅ ImportController has been updated with ImportService integration

---

## 🚀 Railway Environment Variables

### Required Variables for Railway Deployment

Add these **4 environment variables** to your Railway project:

```bash
INTELLIGENT_IMPORT_ENABLED=true
INTELLIGENT_IMPORT_MIN_CONFIDENCE=0.60
INTELLIGENT_IMPORT_FALLBACK=true
INTELLIGENT_IMPORT_LOGGING=true
```

### How to Add Variables in Railway:

1. **Go to Railway Dashboard:**
   - Navigate to your project: https://railway.app/dashboard
   - Click on your service (e.g., "mkaccounting")

2. **Add Environment Variables:**
   - Click "Variables" tab
   - Click "+ New Variable"
   - Add each variable one by one:

   | Variable Name | Value | Description |
   |---------------|-------|-------------|
   | `INTELLIGENT_IMPORT_ENABLED` | `true` | Enable intelligent mapping system |
   | `INTELLIGENT_IMPORT_MIN_CONFIDENCE` | `0.60` | Minimum 60% confidence for auto-mapping |
   | `INTELLIGENT_IMPORT_FALLBACK` | `true` | Fall back to legacy if errors occur |
   | `INTELLIGENT_IMPORT_LOGGING` | `true` | Enable detailed logging |

3. **Deploy Changes:**
   - Railway will automatically redeploy with new variables
   - Wait for deployment to complete (~2-3 minutes)

4. **Verify Configuration:**
   - SSH into Railway or check deployment logs
   - Run: `php artisan config:cache` (Railway does this automatically)

---

## 🧪 Local Testing Steps

### Step 1: Enable Intelligent Import System

1. **Edit your `.env` file:**
```bash
# Add these lines (or update if they exist)
INTELLIGENT_IMPORT_ENABLED=true
INTELLIGENT_IMPORT_MIN_CONFIDENCE=0.60
INTELLIGENT_IMPORT_FALLBACK=true
INTELLIGENT_IMPORT_LOGGING=true
```

2. **Clear configuration cache:**
```bash
php artisan config:clear
```

3. **Verify configuration loaded:**
```bash
php artisan tinker --execute="echo 'Intelligent Import Enabled: ' . (config('import.intelligent_enabled') ? 'YES' : 'NO') . PHP_EOL;"
```

**Expected Output:**
```
Intelligent Import Enabled: YES
```

---

### Step 2: Verify Database is Seeded

Check that mapping rules are loaded:

```bash
php artisan tinker --execute="echo 'Total Mapping Rules: ' . \App\Models\MappingRule::count() . PHP_EOL; echo 'Invoice Rules: ' . \App\Models\MappingRule::where('entity_type', 'invoice')->count() . PHP_EOL;"
```

**Expected Output:**
```
Total Mapping Rules: 41
Invoice Rules: 11
```

If you see `0`, run the seeder:
```bash
php artisan db:seed --class=MappingRuleSeeder
```

---

### Step 3: Clear All Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

### Step 4: Start Development Server

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Keep this terminal open to watch logs.

---

### Step 5: Open Application in Browser

1. **Navigate to:**
   ```
   http://127.0.0.1:8000
   ```

2. **Login with your admin credentials**

3. **Go to Import Section:**
   - Click "Settings" → "Import Data" (or similar menu)
   - Or navigate directly to: `http://127.0.0.1:8000/admin/imports`

---

### Step 6: Test Invoice Import (Happy Path)

1. **Prepare Test CSV:**
   - Use the existing test file: `tests/fixtures/migration/07_happy_path_invoices.csv`
   - This file has 10 fields with standard naming

2. **Upload CSV:**
   - Click "Upload CSV" or "Import" button
   - Select file: `tests/fixtures/migration/07_happy_path_invoices.csv`
   - Click "Next" or "Upload"

3. **Watch the Logs:**
   Open a new terminal and tail the logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Expected Log Output:**
   ```
   [ImportService] Starting intelligent field detection for entity type: invoice
   [ImportController] Using intelligent mapping system for import
   [IntelligentFieldMapper] Processing 10 CSV fields
   [IntelligentFieldMapper] Matched 10/10 fields with average confidence: 0.95
   [MappingScorer] Overall quality score: 95 (EXCELLENT)
   [ImportController] Intelligent mapping completed with quality score: 95
   ```

5. **Check UI Response:**
   - You should see **field mapping suggestions**
   - Each field should show **confidence score** (e.g., "95%")
   - **Quality grade** should display (e.g., "EXCELLENT - A")
   - **0 missing required fields**
   - **100% mapping score** (or close to it)

---

### Step 7: Test Customer Import (Macedonian Characters)

1. **Upload CSV:**
   - File: `tests/fixtures/migration/02_macedonian_chars_customers.csv`
   - This file has Cyrillic characters

2. **Expected Results:**
   - Cyrillic field names should be recognized
   - Email validation should work with IDN (Internationalized Domain Names)
   - Confidence scores: 85-95%
   - Quality grade: GOOD or EXCELLENT

3. **Check Logs For:**
   ```
   [FieldAnalyzer] Detected Cyrillic characters in field: име
   [SynonymMatcher] Matched Macedonian synonym: име → name
   [IntelligentFieldMapper] Applied language variation: mk
   ```

---

### Step 8: Test Fuzzy Matching (Typos)

1. **Create Test CSV with Typos:**
   Create a file `test_typos.csv`:
   ```csv
   invoce_numbr,custmer_name,invoce_date,totl
   INV-001,ACME Corp,2025-01-13,1500.00
   INV-002,Test Inc,2025-01-14,2500.00
   ```

2. **Upload this CSV**

3. **Expected Results:**
   - `invoce_numbr` → matched to `invoice_number` (FuzzyMatcher)
   - `custmer_name` → matched to `customer_name` (FuzzyMatcher)
   - `totl` → matched to `total` (FuzzyMatcher)
   - Confidence scores: 70-85% (lower due to typos)
   - Quality grade: GOOD or FAIR

4. **Check Logs For:**
   ```
   [FuzzyMatcher] Fuzzy match: 'invoce_numbr' → 'invoice_number' (similarity: 0.85)
   [FuzzyMatcher] Levenshtein distance: 2
   ```

---

### Step 9: Test Fallback to Legacy System

1. **Temporarily break intelligent system:**
   - Edit `.env`: Set an invalid confidence threshold
   ```bash
   INTELLIGENT_IMPORT_MIN_CONFIDENCE=2.0  # Invalid (should be 0.0-1.0)
   ```

2. **Clear cache:**
   ```bash
   php artisan config:clear
   ```

3. **Upload any CSV**

4. **Expected Behavior:**
   - System should log an error
   - Automatically fall back to legacy mapping
   - Import wizard should still work (no crashes)

5. **Check Logs For:**
   ```
   [ImportService] Error in intelligent mapping: ...
   [ImportService] Falling back to legacy mapping system
   [ImportController] Using legacy mapping system
   ```

6. **Restore Configuration:**
   ```bash
   INTELLIGENT_IMPORT_MIN_CONFIDENCE=0.60
   php artisan config:clear
   ```

---

### Step 10: Verify API Response Structure

1. **Open Browser Developer Tools:**
   - Press `F12`
   - Go to "Network" tab

2. **Upload a CSV file**

3. **Find the API Request:**
   - Look for request to `/api/v1/admin/imports/{id}`
   - Click on it
   - Go to "Response" tab

4. **Verify JSON Structure:**
   ```json
   {
     "success": true,
     "data": {
       "id": 1,
       "type": "invoice",
       "detected_fields": [...],
       "mapping_suggestions": {
         "invoice_number": "invoice_number",
         "customer_name": "customer_name",
         ...
       },
       "auto_mapping_confidence": 0.92,
       "summary": {
         "intelligent_metadata": {
           "intelligent_mapping": true,
           "confidence_scores": {
             "invoice_number": 1.0,
             "customer_name": 0.95,
             ...
           },
           "quality_score": 92,
           "quality_grade": "A",
           "statistics": {
             "total_fields": 10,
             "mapped_fields": 10,
             "high_confidence_mappings": 8
           },
           "recommendations": [...]
         }
       }
     }
   }
   ```

---

### Step 11: Test Different Entity Types

Test all 5 supported entity types:

1. **Customer Import:**
   - File: `tests/fixtures/migration/02_macedonian_chars_customers.csv`
   - Expected: 10 customer rules applied

2. **Invoice Import:**
   - File: `tests/fixtures/migration/07_happy_path_invoices.csv`
   - Expected: 11 invoice rules applied

3. **Item Import:**
   - Create a CSV with: `name, price, description, quantity, unit, sku`
   - Expected: 7 item rules applied

4. **Payment Import:**
   - Create a CSV with: `payment_date, amount, payment_method, reference`
   - Expected: 7 payment rules applied

5. **Expense Import:**
   - Create a CSV with: `expense_date, amount, category, notes`
   - Expected: 6 expense rules applied

---

### Step 12: Monitor Performance

1. **Check Response Times:**
   - Intelligent mapping should complete in **< 1 second** for 100 rows
   - Larger files (1000+ rows) should complete in **< 5 seconds**

2. **Memory Usage:**
   - Monitor with: `php artisan tinker --execute="echo memory_get_usage(true) / 1024 / 1024 . ' MB' . PHP_EOL;"`
   - Should not exceed **128 MB** for typical imports

3. **Database Query Count:**
   - Enable query logging in `.env`: `DB_LOG_QUERIES=true`
   - Intelligent system should make **1 query per entity type** (cached rules)

---

## 🎯 Success Criteria

The intelligent import system is working correctly if:

✅ **Field Detection:**
- All standard fields auto-detected with 90%+ confidence
- Typos detected with 70-85% confidence
- Cyrillic/multi-language fields recognized

✅ **Quality Scoring:**
- Happy path CSVs score 90-100 (EXCELLENT - A)
- Good CSVs score 75-89 (GOOD - B)
- CSVs with typos score 60-74 (FAIR - C)

✅ **Logging:**
- All operations logged with timestamps
- Confidence scores logged for each field
- Quality metrics logged

✅ **Fallback:**
- System gracefully falls back to legacy on errors
- No crashes or blank screens
- User experience uninterrupted

✅ **Performance:**
- < 1 second for small CSVs (< 100 rows)
- < 5 seconds for large CSVs (1000+ rows)
- Minimal memory footprint

---

## 🐛 Troubleshooting

### Issue: "Intelligent Import Enabled: NO"

**Solution:**
```bash
# Check .env file has the variable
grep INTELLIGENT_IMPORT_ENABLED .env

# If missing, add it:
echo "INTELLIGENT_IMPORT_ENABLED=true" >> .env

# Clear cache
php artisan config:clear
```

---

### Issue: "Total Mapping Rules: 0"

**Solution:**
```bash
# Run the seeder
php artisan db:seed --class=MappingRuleSeeder

# Verify
php artisan tinker --execute="echo \App\Models\MappingRule::count() . PHP_EOL;"
```

---

### Issue: "Class 'App\Services\Import\ImportService' not found"

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

### Issue: No logs appearing

**Solution:**
```bash
# Check log file exists
touch storage/logs/laravel.log
chmod 666 storage/logs/laravel.log

# Verify logging is enabled
php artisan tinker --execute="echo config('import.intelligent.logging.enabled') ? 'YES' : 'NO';"
```

---

### Issue: All confidence scores are 0

**Solution:**
- This means IntelligentFieldMapper couldn't match fields
- Check that MappingRuleSeeder ran successfully
- Check CSV field names against synonym database
- Review logs for matching attempts

---

## 📊 Expected Test Results Summary

| Test Case | Expected Quality Score | Expected Grade | Expected Confidence |
|-----------|------------------------|----------------|---------------------|
| Happy Path Invoices | 95-100 | A (EXCELLENT) | 0.90-1.0 |
| Macedonian Customers | 85-95 | A-B (EXCELLENT/GOOD) | 0.85-0.95 |
| CSV with Typos | 60-75 | C-D (FAIR/POOR) | 0.65-0.80 |
| Custom/Unknown Fields | 40-60 | D-F (POOR/FAILED) | 0.40-0.65 |

---

## ✅ Final Verification Checklist

Before deploying to production:

- [ ] All test CSVs import successfully
- [ ] Intelligent metadata appears in API responses
- [ ] Quality grades are accurate (A, B, C, D, F)
- [ ] Confidence scores are reasonable (60-100%)
- [ ] Logs show detailed mapping information
- [ ] Fallback to legacy works on errors
- [ ] Performance is acceptable (< 1s per import)
- [ ] No crashes or errors in production logs
- [ ] Railway environment variables are set
- [ ] Railway deployment shows intelligent mapping in logs

---

## 🎬 Quick Start (TL;DR)

```bash
# 1. Enable intelligent import
echo "INTELLIGENT_IMPORT_ENABLED=true" >> .env
echo "INTELLIGENT_IMPORT_MIN_CONFIDENCE=0.60" >> .env
echo "INTELLIGENT_IMPORT_FALLBACK=true" >> .env
echo "INTELLIGENT_IMPORT_LOGGING=true" >> .env

# 2. Clear cache
php artisan config:clear

# 3. Seed database (if not already done)
php artisan db:seed --class=MappingRuleSeeder

# 4. Test
# - Upload: tests/fixtures/migration/07_happy_path_invoices.csv
# - Check logs: tail -f storage/logs/laravel.log
# - Verify API response has intelligent_metadata

# 5. For Railway:
# - Add 4 env vars in Railway dashboard
# - Redeploy
# - Test on production URL
```

---

*Last Updated: 2025-01-13*
*Status: Ready for Testing ✅*
