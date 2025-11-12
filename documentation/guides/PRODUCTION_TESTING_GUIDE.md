# 🧪 PRODUCTION TESTING GUIDE - Migration Wizard

## Railway Production URL
**Migration Wizard**: https://www.facturino.mk/admin/imports/wizard

---

## ⚠️ IMPORTANT: First-Time Setup Required

Before testing, you MUST enable the migration wizard feature flag for your user:

### Option 1: Via Database (Recommended)
```sql
-- Enable for your user (replace USER_ID with your actual user ID)
INSERT INTO feature_user (feature_name, scope_id, scope_type, created_at, updated_at)
VALUES ('migration-wizard', USER_ID, 'App\\Models\\User', NOW(), NOW());
```

### Option 2: Via Tinker (SSH into Railway)
```bash
# SSH into Railway container
railway connect

# Run tinker
php artisan tinker

# Enable feature for your user
\Crater\Models\User::find(YOUR_USER_ID)->features()->attach('migration-wizard');
```

---

## 📋 TESTING CHECKLIST

### Phase 1: UI Verification ✅
- [ ] Navigate to https://www.facturino.mk/admin/imports/wizard
- [ ] Verify 4-step wizard is visible (Upload → Mapping → Validation → Commit)
- [ ] Verify left sidebar shows progress (0%)
- [ ] Verify "Download CSV Templates" section is visible (green border)
- [ ] Verify 4 template download buttons:
  - [ ] Customer Template
  - [ ] Items Template
  - [ ] Invoice Template
  - [ ] Invoice with Items Template
- [ ] Verify file upload dropzone is present
- [ ] Verify "Choose a file or drag it here" text is visible

### Phase 2: Template Downloads 📥
Test each template downloads correctly:

1. **Customer Template**
   - Click "Customer Template" button
   - Verify file downloads: `customer_import_template.csv`
   - Open in text editor, verify UTF-8 encoding
   - Verify header comments are visible
   - Verify 3 sample rows with Macedonian data

2. **Items Template**
   - Click "Items Template" button
   - Verify file downloads: `items_import_template.csv`
   - Verify 5 sample rows

3. **Invoice Template**
   - Click "Invoice Template" button
   - Verify file downloads: `invoice_import_template.csv`
   - Verify 5 sample rows with different statuses

4. **Invoice with Items Template**
   - Click "Invoice with Items Template" button
   - Verify file downloads: `invoice_with_items_template.csv`
   - Verify hierarchical structure (INVOICE rows + ITEM rows)

---

## 🧪 CSV FILES TO TEST (Priority Order)

### Priority 1 - Critical Happy Path Tests

#### Test 1: Simple Customer Import
**File**: `tests/fixtures/migration/01_happy_path_customers.csv`
**Expected**: 3 customers imported successfully
**Steps**:
1. Upload file
2. Verify auto-mapper recognizes all fields (100% confidence)
3. Proceed to validation
4. Verify 3 valid records, 0 errors
5. Commit import
6. Verify customers appear in /admin/customers

**Success Criteria**: All 3 customers created with Macedonian names (Технологија ДООЕЛ, Балкан Консалтинг ООД, etc.)

---

#### Test 2: Macedonian Cyrillic Characters
**File**: `tests/fixtures/migration/02_macedonian_chars_customers.csv`
**Expected**: 6 customers with special Cyrillic characters imported
**Steps**:
1. Upload file
2. Verify encoding detection (UTF-8)
3. Verify special chars render correctly in preview: КИРИЛИЦА, Č, Ž, Š, Ć, Ђ, Љ, Њ, Ѓ, Ќ
4. Commit import
5. Verify all names display correctly (no mojibake)

**Success Criteria**: Characters like Ѓорѓи, Љубица, Њиколоски display correctly

---

#### Test 3: Simple Invoice Import
**File**: `tests/fixtures/migration/07_happy_path_invoices.csv`
**Expected**: 5 invoices imported successfully
**Steps**:
1. Upload file
2. Verify customer matching (should find existing customers by name/email)
3. Verify total calculations are correct
4. Commit import
5. Verify invoices appear in /admin/invoices

**Success Criteria**: 5 invoices with correct totals, dates, and customer associations

---

### Priority 2 - Competitor Presets

#### Test 4: Effect Plus Migration
**Preset**: Effect Plus
**File**: Create a CSV with Effect Plus field names:
```csv
klijent_pun_naziv,pdv_br,email,telefon,dok_br,dat_izdavanja,osnovica,porez,ukupno
Компанија Тест,MK4080012562123,test@company.mk,+38970123456,001/2025,2025-01-10,10000,1800,11800
```
**Steps**:
1. Upload file
2. Select "Effect Plus" preset from dropdown
3. Verify auto-mapping applies Effect Plus patterns
4. Verify fields map correctly
5. Commit import

**Success Criteria**: Effect Plus field names recognized, data imported correctly

---

#### Test 5: Eurofaktura Migration
**Preset**: Eurofaktura
**File**: Create CSV with Eurofaktura field names:
```csv
company_name,tin,email,phone,invoice_ref,issued_on,net_amount,vat,gross_amount
Test Company,MK4080012562124,info@test.mk,+38970987654,INV-001,2025-01-11,5000,900,5900
```
**Steps**:
1. Upload file
2. Select "Eurofaktura" preset
3. Verify English field names recognized
4. Commit import

**Success Criteria**: English field names map correctly to Facturino fields

---

### Priority 3 - Albanian Language Support

#### Test 6: Albanian Customer Import
**File**: Create CSV with Albanian field names:
```csv
emri_klientit,nipt,email,telefoni,qyteti,adresa
Klienti Shqiptar,123456789,client@example.al,+38345123456,Tetovë,Rruga Ilindenit 10
```
**Steps**:
1. Upload file
2. Verify Albanian field names recognized (emri_klientit → customer_name, nipt → tax_id)
3. Verify confidence scores (should be 0.8-1.0)
4. Commit import

**Success Criteria**: Albanian field names recognized, customer created

---

### Priority 4 - Manual CSV (Generic Fields)

#### Test 7: Manual User CSV
**File**: Create simple CSV with generic field names:
```csv
name,email,phone,qty,price,total
John Doe,john@example.com,+38970555555,10,100.50,1005.00
```
**Steps**:
1. Upload file
2. Verify generic patterns recognized (qty → quantity, price → unit_price)
3. Verify confidence scores moderate (0.6-0.75)
4. Commit import

**Success Criteria**: Generic field names recognized despite not matching any software

---

### Priority 5 - Edge Cases

#### Test 8: Large Dataset Performance
**File**: `tests/fixtures/migration/03_large_dataset_customers.csv`
**Expected**: 1,200+ customers imported
**Steps**:
1. Upload file
2. Verify parsing completes in <30 seconds
3. Monitor progress bar
4. Verify validation completes
5. Commit import (may take 2-3 minutes)

**Success Criteria**: Import completes successfully, no timeout errors

---

#### Test 9: Date Format Variations
**File**: `tests/fixtures/migration/08_date_format_variations_invoices.csv`
**Expected**: 5 invoices with different date formats all parsed correctly
**Formats tested**:
- DD.MM.YYYY (15.01.2025)
- YYYY-MM-DD (2025-01-20)
- DD/MM/YYYY (25/01/2025)

**Success Criteria**: All date formats recognized and converted to YYYY-MM-DD

---

#### Test 10: Number Format Variations
**File**: `tests/fixtures/migration/09_number_format_variations_invoices.csv`
**Expected**: 5 invoices with different number formats
**Formats tested**:
- European: 1.234,56
- American: 12,345.67
- Space separator: 1 234,56

**Success Criteria**: All number formats parsed correctly to decimal

---

### Priority 6 - Multi-Company Testing

#### Test 11: Same Email Different Companies
**Purpose**: Verify email uniqueness is per-company, not global
**Steps**:
1. Login as Company A admin
2. Import customer: test@example.com
3. Logout, login as Company B admin
4. Import customer: test@example.com (same email)
5. Verify both customers exist in their respective companies

**Success Criteria**: Same email allowed in different companies, no unique constraint error

---

### Priority 7 - Error Handling

#### Test 12: Invalid Data
**File**: `tests/fixtures/migration/20_missing_required_fields_customers.csv`
**Expected**: Validation errors shown, invalid records rejected
**Steps**:
1. Upload file with missing required fields
2. Proceed to validation
3. Verify error messages displayed
4. Verify invalid records count shown
5. Verify option to skip invalid records or fix CSV

**Success Criteria**: Clear error messages, user can download error report

---

## 🔍 WHAT TO CHECK DURING TESTING

### Step 1: Upload
- [ ] File upload works (drag & drop and file picker)
- [ ] Encoding detection works (UTF-8, Windows-1250)
- [ ] CSV structure analysis completes
- [ ] Preview shows first 10 rows
- [ ] Progress moves to Step 2

### Step 2: Mapping
- [ ] Auto-mapper runs automatically
- [ ] Detected fields shown with confidence scores
- [ ] Preset selector visible (Onivo, Megasoft, Effect Plus, Eurofaktura, Manager.io, Generic)
- [ ] Manual field mapping possible (drag & drop)
- [ ] Mapping save works
- [ ] Progress moves to Step 3

### Step 3: Validation
- [ ] Validation runs on all records
- [ ] Valid records count displayed (green)
- [ ] Invalid records count displayed (red)
- [ ] Error messages clear and actionable
- [ ] Error CSV downloadable
- [ ] Duplicate detection works
- [ ] Progress moves to Step 4

### Step 4: Commit
- [ ] Summary statistics displayed
- [ ] Commit button visible
- [ ] Commit executes (may take time for large datasets)
- [ ] Success message displayed
- [ ] Records visible in respective sections (customers, invoices, items)

---

## 🚨 COMMON ISSUES & TROUBLESHOOTING

### Issue 1: "Migration wizard feature is disabled"
**Solution**: Enable feature flag via database or tinker (see setup section)

### Issue 2: "Unauthorized" or 403 errors
**Solution**: ImportJobPolicy may not be registered
```bash
# Check AppServiceProvider.php line 85
php artisan route:list | grep migration
```

### Issue 3: File upload dropzone not visible
**Solution**:
- Check browser console for JavaScript errors
- Verify `npm run build` was executed
- Clear browser cache
- Check if user has `create-import-job` permission

### Issue 4: Auto-mapper not recognizing fields
**Solution**:
- Try selecting a preset manually
- Check field names match expected patterns
- Albanian/Macedonian fields require exact spelling
- Manual mapping is always available

### Issue 5: Character encoding issues (mojibake)
**Solution**:
- Save CSV as UTF-8 (not ANSI or Windows-1252)
- In Excel: File → Save As → CSV UTF-8
- Verify file with text editor before upload

### Issue 6: Validation fails with "Customer not found"
**Solution**:
- Import customers before invoices
- Ensure customer email/name matches exactly
- Check for whitespace or special characters

---

## 📊 SUCCESS METRICS

After testing, the system should achieve:

- ✅ **Competitor Coverage**: 100% (6 software supported)
- ✅ **Field Recognition Accuracy**: 85%+ auto-mapping success
- ✅ **Albanian Support**: Albanian field names recognized
- ✅ **Manual CSV Support**: Generic field patterns recognized
- ✅ **Performance**: 1,000 records imported in <2 minutes
- ✅ **Error Handling**: Clear error messages, actionable fixes
- ✅ **Multi-Company**: Same email works in different companies

---

## 🎯 TESTING PRIORITY

**Must Test (P0)**:
1. ✅ Upload feature flag enabled
2. ✅ UI renders correctly
3. ✅ Template downloads work
4. ✅ Simple customer import (Test 1)
5. ✅ Cyrillic characters (Test 2)

**Should Test (P1)**:
6. ✅ Invoice import (Test 3)
7. ✅ Effect Plus preset (Test 4)
8. ✅ Albanian support (Test 6)
9. ✅ Manual CSV (Test 7)

**Nice to Test (P2)**:
10. Large dataset (Test 8)
11. Date formats (Test 9)
12. Multi-company (Test 11)

---

## 📝 REPORTING ISSUES

If you find bugs, report with:
1. **Test number** (e.g., "Test 3: Invoice Import")
2. **Steps taken** (exactly what you clicked)
3. **Expected result** (what should happen)
4. **Actual result** (what actually happened)
5. **Browser console errors** (F12 → Console tab)
6. **Screenshots** (if UI issue)
7. **CSV file used** (attach or paste content)

---

## ✅ SIGN-OFF CHECKLIST

Before declaring production-ready:
- [ ] All P0 tests passed
- [ ] At least 6/9 P1 tests passed
- [ ] No critical bugs found
- [ ] Performance acceptable (<30s for 1,000 records)
- [ ] Multi-company isolation working
- [ ] Documentation reviewed and accurate
- [ ] Training materials prepared for end users

---

**Generated**: 2025-01-12
**Author**: Claude Code Audit
**Status**: Ready for Testing
