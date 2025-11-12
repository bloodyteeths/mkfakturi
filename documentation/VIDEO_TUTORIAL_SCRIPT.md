# Video Tutorial Script: Migration Wizard
## Complete Guide to Importing Data into Facturino

**Total Duration:** ~10 minutes
**Target Audience:** New users migrating from Onivo, Megasoft, or generic accounting systems
**Skill Level:** Beginner to Intermediate
**Language:** English (with Macedonian UI references)
**Version:** 1.0
**Date:** 2025-11-12

---

## Table of Contents
1. [Introduction](#introduction-30-seconds)
2. [Prerequisites](#prerequisites-1-minute)
3. [Step 1: Download Template](#step-1-download-template-1-minute)
4. [Step 2: Fill in Data](#step-2-fill-in-data-2-minutes)
5. [Step 3: Upload File](#step-3-upload-file-1-minute)
6. [Step 4: Review Mapping](#step-4-review-mapping-15-minutes)
7. [Step 5: Validate Data](#step-5-validate-data-1-minute)
8. [Step 6: Complete Import](#step-6-complete-import-1-minute)
9. [Common Issues and Fixes](#common-issues-and-fixes-15-minutes)
10. [Conclusion](#conclusion-30-seconds)

---

## INTRODUCTION (30 seconds)
### Timestamp: 00:00 - 00:30

**VISUAL:** Show Facturino dashboard with logo and main interface

**VOICEOVER:**
"Welcome to Facturino! In this tutorial, you'll learn how to migrate your existing customer, invoice, item, and payment data into Facturino using our Universal Migration Wizard. Whether you're coming from Onivo, Megasoft, or any other accounting system, this wizard will help you import your data quickly and accurately. By the end of this video, you'll have all your historical data in Facturino and be ready to start using the system."

**SCREEN RECORDING NOTES:**
- Open browser at Facturino dashboard
- Show main menu with Migration option highlighted
- Zoom in slightly on "Migration Wizard" menu item
- Use smooth cursor movements

**VISUAL CALLOUTS:**
- Highlight "Migration" menu item with animated circle
- Show small badge indicating "Easy Import" or similar

---

## PREREQUISITES (1 minute)
### Timestamp: 00:30 - 01:30

**VISUAL:** Split screen showing checklist on left, sample files on right

**VOICEOVER:**
"Before we begin, let's make sure you have everything you need. First, you'll need admin access to your Facturino account. Second, gather your data from your previous accounting system. This can be exported as CSV files, Excel files, or if you're using Onivo or Megasoft, you can export directly from those systems. Third, make sure your data includes customer information, invoices, items or products, and payment records. Finally, if you're in Macedonia, ensure your data includes proper VAT numbers in the Macedonian format, starting with 'MK' followed by 13 digits."

**SCREEN RECORDING NOTES:**
- Show login screen briefly
- Display file explorer with sample export files
- Show Excel file with data preview
- Highlight Macedonian VAT number format example

**VISUAL CALLOUTS:**
- Checklist items appear one by one with checkmarks:
  - ✓ Admin access to Facturino
  - ✓ Exported data from previous system
  - ✓ Data includes: Customers, Invoices, Items, Payments
  - ✓ Macedonian VAT format (MK + 13 digits)

**TEXT OVERLAY:**
```
PREREQUISITES CHECKLIST
□ Admin account access
□ Data exported from old system
□ Customer, invoice, item, payment data
□ Macedonian VAT numbers (if applicable)
```

---

## STEP 1: DOWNLOAD TEMPLATE (1 minute)
### Timestamp: 01:30 - 02:30

**VISUAL:** Navigate to Migration Wizard and show template selection

**VOICEOVER:**
"Let's get started. Log in to your Facturino account and navigate to the Migration Wizard from the main menu. On the first screen, you'll choose your source system. If you're migrating from Onivo, select 'Onivo Accounting.' For Megasoft users, choose 'Megasoft ERP.' If you're coming from another system, select 'Generic CSV' or 'Excel Files.'

For this tutorial, we'll use the Generic CSV option to show you the most flexible approach. Once you select your source system, click 'Download Template' to get the CSV template files. You'll receive four templates: one for customers, one for invoices, one for items, and one for payments. Save these templates to your computer."

**SCREEN RECORDING NOTES:**
- Click on "Migration" in main menu
- Show migration wizard landing page
- Hover over each system option (Onivo, Megasoft, CSV, Excel)
- Click "Generic CSV"
- Click "Download Template" button
- Show browser download notification
- Open downloaded ZIP file showing 4 CSV templates

**VISUAL CALLOUTS:**
- Circle highlight around each system option when hovering
- Animated arrow pointing to "Download Template" button
- Popup showing the 4 template files with labels:
  - 📄 customers_template.csv
  - 📄 invoices_template.csv
  - 📄 items_template.csv
  - 📄 payments_template.csv

**TEXT OVERLAY:**
```
SUPPORTED SYSTEMS
• Onivo Accounting
• Megasoft ERP
• Generic CSV Files
• Excel Spreadsheets
```

---

## STEP 2: FILL IN DATA (2 minutes)
### Timestamp: 02:30 - 04:30

**VISUAL:** Open CSV template in Excel/spreadsheet application

**VOICEOVER:**
"Now, let's fill in the template with your data. Open the customers template first in Excel or any spreadsheet application. You'll see column headers that explain what data goes in each field.

The required fields are marked with an asterisk. For customers, you must include: name, email, and currency. Optional fields include phone number, address, website, and VAT number.

Here's an example: Let's add a Macedonian company. In the name field, we'll type 'Македонска Трговска ДООЕЛ' - notice we can use Cyrillic characters. For email, 'info@makedonska.mk'. For currency, we use the three-letter code 'MKD' for Macedonian Denar. The VAT number follows the Macedonian format: 'MK4080012562345' - that's MK followed by 13 digits. Fill in the address using Cyrillic: 'улица Македонија 123, Скопје, 1000'.

Repeat this process for all your customers. Then move to the invoices template, items template, and payments template. Make sure your invoice numbers match between the invoices and payments files, so payments can be correctly linked to invoices."

**SCREEN RECORDING NOTES:**
- Open customers_template.csv in Excel
- Show column headers with required field indicators
- Type example data slowly and clearly
- Show Cyrillic keyboard input
- Highlight important fields with cell selection
- Show second and third customer rows being filled
- Switch briefly to invoices template
- Show how invoice_number in invoices matches invoice_number in payments

**VISUAL CALLOUTS:**
- Required fields highlighted in red: name*, email*, currency*
- Optional fields in green: phone, address, website, vat_number
- Annotation showing VAT format: "MK + 13 digits"
- Side panel showing example data:
  ```
  Name: Македонска Трговска ДООЕЛ
  Email: info@makedonska.mk
  Currency: MKD
  VAT: MK4080012562345
  Address: улица Македонија 123, Скопје, 1000
  ```

**TEXT OVERLAY:**
```
REQUIRED FIELDS*
✓ Name (can use Cyrillic)
✓ Email
✓ Currency (MKD, EUR, USD, etc.)

OPTIONAL FIELDS
• Phone (+389 for Macedonia)
• Address
• Website
• VAT Number (MK + 13 digits)
```

**EXAMPLE DATA TABLE (shown on screen):**
| name | email | phone | currency | vat_number | address |
|------|-------|-------|----------|------------|---------|
| Македонска Трговска ДООЕЛ | info@makedonska.mk | +389 2 123 4567 | MKD | MK4080012562345 | улица Македонија 123, Скопје, 1000 |

**COMMON MISTAKES HIGHLIGHT:**
- ❌ Currency code in wrong format: "Denar" → ✓ Use "MKD"
- ❌ VAT number missing MK prefix: "4080012562345" → ✓ "MK4080012562345"
- ❌ Missing required fields → ✓ Always fill name, email, currency

---

## STEP 3: UPLOAD FILE (1 minute)
### Timestamp: 04:30 - 05:30

**VISUAL:** Return to Migration Wizard and upload files

**VOICEOVER:**
"Once your templates are filled with data, save each file. Make sure to save them in CSV format, not Excel format. Now, return to the Migration Wizard in Facturino. You should be on Step 2: 'Upload Files.'

Click the upload area or drag and drop your CSV files. You can upload all four files at once - customers, invoices, items, and payments. As each file uploads, you'll see a progress indicator and a checkmark when complete.

The wizard will automatically detect the file type based on the column headers. If you see any warnings about file format or encoding, don't worry - we'll address those in the validation step. Once all files are uploaded, click 'Next Step' to proceed to field mapping."

**SCREEN RECORDING NOTES:**
- Show Excel Save As dialog, selecting "CSV UTF-8" format
- Return to browser with Migration Wizard
- Show Step 2 highlighted in wizard progress bar
- Drag and drop first file (customers.csv)
- Show upload progress bar
- Upload remaining files one by one
- Show all 4 files with checkmarks
- Click "Validate Files" button
- Show validation summary:
  - ✓ 15 customers found
  - ✓ 45 invoices found
  - ✓ 120 items found
  - ✓ 38 payments found
- Click "Next Step" button

**VISUAL CALLOUTS:**
- Highlight "Save as CSV UTF-8" option in Excel
- Arrow pointing to upload drop zone
- File upload progress indicators:
  - customers.csv ✓ (15 records)
  - invoices.csv ✓ (45 records)
  - items.csv ✓ (120 records)
  - payments.csv ✓ (38 records)
- Green checkmarks appearing after each upload

**TEXT OVERLAY:**
```
UPLOAD TIPS
• Save files as CSV UTF-8
• Can upload all files at once
• Maximum file size: 50MB
• For larger datasets, split into multiple files
```

---

## STEP 4: REVIEW MAPPING (1.5 minutes)
### Timestamp: 05:30 - 07:00

**VISUAL:** Show field mapping interface

**VOICEOVER:**
"The wizard has now analyzed your files and is ready to map your data fields to Facturino's database. This is where the magic happens.

On the Field Mapping screen, you'll see your source fields on the left and Facturino's target fields on the right. The wizard uses intelligent auto-mapping to match fields based on column names. For most standard templates, the mapping will be 100% correct. You can see the mapping confidence score at the top - we're aiming for 90% or higher.

Let's review the customer mapping. Your 'customer_name' field is mapped to Facturino's 'name' field. Your 'vat_number' is mapped to 'tax_id'. The 'address_line1' maps to 'address_street_1'. Everything looks good.

Now, if you're importing from a Macedonian system, pay special attention to the tax mapping. You'll see that the wizard recognizes Macedonian VAT rates: 18% standard rate, 5% reduced rate, and 0% for exempt items. Make sure these are mapped correctly.

If you need to adjust any mapping, simply click the dropdown menu next to the target field and select the correct mapping. You can also leave fields unmapped if you don't want to import that data. When you're satisfied with the mapping, click 'Save Mapping' and then 'Next Step.'"

**SCREEN RECORDING NOTES:**
- Show Step 3: Field Mapping screen
- Display mapping confidence score: 95%
- Show customer field mappings table
- Scroll through mappings slowly
- Click on a dropdown to show alternative field options
- Show tax rate mapping section
- Highlight Macedonian-specific mappings:
  - ДДВ 18% → Standard VAT
  - ДДВ 5% → Reduced VAT
  - ДДВ 0% → Zero-rated
- Click "Save Mapping" button
- Click "Next Step"

**VISUAL CALLOUTS:**
- Confidence score badge: "95% High Confidence"
- Mapping table with columns:
  ```
  Source Field → Target Field (Confidence)
  customer_name → name (100%)
  email_address → email (100%)
  vat_number → tax_id (98%)
  phone → phone (95%)
  address_line1 → address_street_1 (90%)
  ```
- Highlight Macedonian tax mappings in separate box:
  ```
  MACEDONIAN TAX RATES
  ДДВ 18% → Standard Rate
  ДДВ 5% → Reduced Rate
  ДДВ 0% → Zero-rated
  ```

**TEXT OVERLAY:**
```
FIELD MAPPING
✓ Auto-mapped fields: 95%
✓ Manual review recommended
✓ Macedonia VAT rates detected
✓ Cyrillic characters preserved

COMMON MAPPINGS
customer_name → name
vat_number → tax_id
address → address_street_1
invoice_date → invoice_date
```

**TIPS CALLOUT:**
"Pro Tip: The wizard automatically detects Macedonian VAT rates (ДДВ) and preserves Cyrillic characters throughout the import process."

---

## STEP 5: VALIDATE DATA (1 minute)
### Timestamp: 07:00 - 08:00

**VISUAL:** Show data validation and preview screen

**VOICEOVER:**
"Before we import, let's validate the data. The wizard will now check every record for errors and inconsistencies. This validation process checks for required fields, email formats, VAT number formats, date validity, and data relationships.

Watch as the validation runs. You'll see a progress indicator for each entity type. The system checks customers first, then items, then invoices, and finally payments.

Great! The validation is complete. Let's review the results. We have 14 customers validated successfully, with 1 warning about a duplicate email address. For invoices, 43 out of 45 passed validation. Two invoices failed because of invalid dates. For items, all 120 passed. For payments, 37 out of 38 passed, with one payment failing because it references an invoice that doesn't exist.

You can click on any warning or error to see details and fix the issues in your CSV file, then re-upload. Or, you can proceed with the valid records and manually add the problematic ones later. For this tutorial, we'll proceed with the valid records. Click 'Continue with Valid Records.'"

**SCREEN RECORDING NOTES:**
- Show Step 4: Data Validation screen
- Display validation progress bars:
  - Customers: Validating... → Complete
  - Items: Validating... → Complete
  - Invoices: Validating... → Complete
  - Payments: Validating... → Complete
- Show validation summary panel
- Click on "View Details" for errors
- Show error detail popup:
  - Line 23: Invoice date invalid "2025-13-45"
  - Line 24: Invoice date in wrong format "25/32/2025"
  - Line 15: Payment references non-existent invoice "INV-9999"
- Close error popup
- Click "Continue with Valid Records" button

**VISUAL CALLOUTS:**
- Validation summary box:
  ```
  ✓ Customers: 14/15 valid (1 warning)
  ✓ Items: 120/120 valid
  ⚠ Invoices: 43/45 valid (2 errors)
  ⚠ Payments: 37/38 valid (1 error)

  Overall: 214/218 records valid (98%)
  ```
- Warning icon badge showing "4 issues found"
- Expandable error details with line numbers

**TEXT OVERLAY:**
```
VALIDATION CHECKS
✓ Required fields present
✓ Email format valid
✓ VAT numbers in correct format
✓ Dates are valid
✓ Relationships maintained
✓ No SQL injection attempts
✓ Character encoding correct
```

**ERROR EXAMPLES (shown on screen):**
```
COMMON VALIDATION ERRORS

❌ Invalid date: "2025-13-45"
   → Month must be 1-12

❌ Invalid email: "user@"
   → Missing domain

❌ VAT number too short: "MK123"
   → Must be MK + 13 digits

✓ How to fix: Update CSV and re-upload
```

---

## STEP 6: COMPLETE IMPORT (1 minute)
### Timestamp: 08:00 - 09:00

**VISUAL:** Show import progress and completion

**VOICEOVER:**
"We're ready for the final step - importing the data. Click 'Start Import' to begin. The wizard will now insert your data into Facturino's database.

Watch the progress indicators as the import runs. First, customers are imported. Then items, since they don't depend on anything else. Next, invoices are imported and linked to customers. Finally, payments are imported and linked to invoices.

The import is processing... Customers complete. Items complete. Invoices are now being imported... almost done... Payments complete!

Excellent! The import has finished successfully. Let's review the summary. We imported 14 customers, 120 items, 43 invoices, and 37 payments - a total of 214 records. The system also shows us the Macedonian-specific validation: 14 valid VAT IDs were processed, and all amounts have been stored in the correct currency - Macedonian Denar for most records.

You can now download a detailed import report that lists every record imported, along with any warnings or errors. Click 'View Imported Data' to see your customers, invoices, and payments in Facturino. Congratulations - your migration is complete!"

**SCREEN RECORDING NOTES:**
- Show Step 5: Import screen
- Click "Start Import" button
- Show animated progress bar with stages:
  - Stage 1: Importing customers (0% → 25%)
  - Stage 2: Importing items (25% → 50%)
  - Stage 3: Importing invoices (50% → 75%)
  - Stage 4: Importing payments (75% → 100%)
- Show completion screen with confetti animation
- Display import summary statistics
- Click "Download Report" button
- Show downloaded PDF report preview
- Click "View Imported Data" button
- Show Customers page with newly imported data
- Show one customer detail page with all fields populated
- Show invoice linked to customer

**VISUAL CALLOUTS:**
- Import progress stages with checkmarks:
  ```
  ✓ Customers (14 imported) - 25%
  ✓ Items (120 imported) - 50%
  ✓ Invoices (43 imported) - 75%
  ✓ Payments (37 imported) - 100%
  ```
- Success message banner: "Migration completed successfully!"
- Statistics panel:
  ```
  IMPORT SUMMARY
  Total Records: 214/218 (98%)
  Customers: 14
  Items: 120
  Invoices: 43
  Payments: 37

  Time: 12.3 seconds
  Speed: ~17 records/second
  ```
- Macedonian validation panel:
  ```
  MACEDONIA VALIDATION
  ✓ 14 valid VAT IDs (MK...)
  ✓ 3 tax rates configured (18%, 5%, 0%)
  ✓ Currency: All amounts in MKD
  ✓ Cyrillic text: Properly encoded
  ```

**TEXT OVERLAY:**
```
IMPORT COMPLETE!

✓ 214 records imported
✓ All relationships preserved
✓ Macedonian data validated
✓ Ready to use Facturino
```

---

## COMMON ISSUES AND FIXES (1.5 minutes)
### Timestamp: 09:00 - 10:30

**VISUAL:** Split screen showing common errors and their solutions

**VOICEOVER:**
"Let's cover some common issues you might encounter and how to fix them.

**Issue 1: File Upload Fails**
If your file won't upload, check the file format. Make sure you saved as CSV UTF-8, not Excel format. Also check the file size - if it's over 50MB, split it into smaller files.

**Issue 2: Character Encoding Problems**
If you see question marks or garbled text instead of Cyrillic characters, your file encoding is incorrect. In Excel, when saving, choose 'CSV UTF-8' specifically, not just 'CSV'. If you're using Google Sheets, download as 'CSV UTF-8'.

**Issue 3: Validation Errors for Dates**
Facturino expects dates in YYYY-MM-DD format, like 2025-01-15. If your dates are in DD.MM.YYYY or DD/MM/YYYY format, the wizard will try to auto-convert them, but it's safer to format them as YYYY-MM-DD before uploading.

**Issue 4: VAT Number Validation Fails**
Macedonian VAT numbers must start with 'MK' followed by exactly 13 digits, for a total of 15 characters. Check that your VAT numbers follow this format: MK4080012562345.

**Issue 5: Payments Won't Link to Invoices**
Make sure the invoice_number in your payments file exactly matches the invoice_number in your invoices file. The match is case-sensitive, so 'INV-001' is different from 'inv-001'.

**Issue 6: Duplicate Customer Emails**
Facturino requires unique email addresses for customers. If you have duplicate emails, you'll need to modify one of them before importing, or manually merge the customers after import.

**Issue 7: Import Seems Stuck**
For large datasets with thousands of records, imports can take several minutes. The system is designed to handle up to 1,200 records efficiently. If your import has been running for more than 5 minutes, try refreshing the page and checking if the data was imported by navigating to the Customers or Invoices pages.

If you encounter any issues not covered here, consult the help documentation or contact Facturino support with your import report."

**SCREEN RECORDING NOTES:**
- Show error examples on screen
- Demonstrate fixing each issue:
  1. Show Excel "Save As" dialog highlighting "CSV UTF-8"
  2. Show garbled characters, then correct Cyrillic
  3. Show date format in Excel, demonstrate changing to YYYY-MM-DD
  4. Show invalid VAT number, edit to correct format
  5. Show payments CSV with mismatched invoice number, fix it
  6. Show duplicate email error, edit one email address
  7. Show large dataset import progress bar

**VISUAL CALLOUTS:**

**Issue 1: File Upload**
```
❌ WRONG: file.xlsx (Excel format)
✓ RIGHT: file.csv (CSV UTF-8)

TIP: File size limit is 50MB
```

**Issue 2: Character Encoding**
```
❌ WRONG: "???????????? ????????"
✓ RIGHT: "Македонска Трговска"

FIX: Save as CSV UTF-8 in Excel
```

**Issue 3: Date Format**
```
❌ WRONG: 15.01.2025 or 01/15/2025
✓ RIGHT: 2025-01-15

FIX: Use YYYY-MM-DD format
```

**Issue 4: VAT Number**
```
❌ WRONG: 4080012562345 (missing MK)
❌ WRONG: MK123 (too short)
✓ RIGHT: MK4080012562345 (MK + 13 digits)

FIX: Add "MK" prefix and ensure 13 digits
```

**Issue 5: Payment Linking**
```
Invoices CSV:
invoice_number: INV-2025-001

Payments CSV:
❌ WRONG: inv-2025-001 (wrong case)
✓ RIGHT: INV-2025-001 (exact match)

FIX: Make invoice numbers match exactly
```

**Issue 6: Duplicate Emails**
```
❌ ERROR: "Email info@company.mk already exists"

FIX: Change one email:
  info@company.mk
  → info+billing@company.mk
```

**Issue 7: Large Datasets**
```
Import Time Guidelines:
• 100 records: ~5 seconds
• 500 records: ~25 seconds
• 1,200 records: ~60 seconds

TIP: Be patient with large imports
```

**TEXT OVERLAY:**
```
TROUBLESHOOTING CHECKLIST
□ File format: CSV UTF-8
□ Character encoding: No garbled text
□ Date format: YYYY-MM-DD
□ VAT format: MK + 13 digits
□ Invoice numbers match
□ Unique email addresses
□ Reasonable file size (<50MB)
```

---

## CONCLUSION (30 seconds)
### Timestamp: 10:30 - 11:00

**VISUAL:** Show final Facturino dashboard with imported data

**VOICEOVER:**
"Congratulations! You've successfully migrated your data to Facturino using the Universal Migration Wizard. Your customers, invoices, items, and payments are now in the system and ready to use.

You can start creating new invoices, tracking payments, and managing your Macedonian business accounting with all your historical data at your fingertips. The wizard preserved all your Cyrillic text, Macedonian VAT information, and currency data perfectly.

If you need to import additional data in the future, just return to the Migration Wizard - you can use it as many times as needed.

Thanks for watching! For more tutorials, check out our help documentation at help.facturino.mk. Happy invoicing!"

**SCREEN RECORDING NOTES:**
- Show dashboard with data widgets showing imported counts
- Click on Customers - show list of imported customers
- Click on Invoices - show list of imported invoices
- Click on one invoice to show full details with Cyrillic text
- Show invoice PDF preview with Macedonian formatting
- Fade to Facturino logo
- Show help documentation URL: help.facturino.mk

**VISUAL CALLOUTS:**
- Dashboard statistics:
  ```
  📊 DASHBOARD OVERVIEW
  Customers: 14
  Invoices: 43
  Items: 120
  Payments: 37
  Total Revenue: MKD 2,450,678
  ```
- Success badge: "Migration Complete ✓"
- Call to action: "Need help? Visit help.facturino.mk"

**TEXT OVERLAY:**
```
YOU'RE ALL SET!

✓ Data successfully migrated
✓ Ready to create new invoices
✓ All Macedonian formats preserved
✓ Historical data accessible

NEXT STEPS
• Create your first invoice
• Explore reporting features
• Set up automated reminders
• Configure tax settings

Support: help.facturino.mk
```

**END SCREEN:**
- Facturino logo
- "Subscribe for more tutorials"
- Links to related videos:
  - "Creating Your First Invoice"
  - "Managing Customers"
  - "Payment Tracking"
- Social media links
- Help documentation URL

---

# PRODUCTION NOTES

## Screen Recording Setup

### Equipment & Software
- **Screen Resolution:** 1920x1080 (Full HD)
- **Recording Software:** OBS Studio, Camtasia, or ScreenFlow
- **Frame Rate:** 30fps minimum, 60fps preferred
- **Audio:** Professional USB microphone (Blue Yeti, Audio-Technica AT2020)
- **Video Bitrate:** 8-10 Mbps for high quality

### Recording Settings
- **Browser:** Chrome or Firefox (clean profile, no extensions visible)
- **Browser Zoom:** 100% (no zoom in/out)
- **Cursor:** Use cursor highlighting software (MousePointer Pro, Mouseposé)
- **Cursor Speed:** Slow and deliberate movements
- **Screen Annotations:** Use on-screen annotation tools for callouts

### Recording Environment
- **Desktop:** Clean desktop, hide personal files/folders
- **Notifications:** Disable all system notifications
- **Multiple Takes:** Record each section separately for easier editing
- **B-Roll:** Capture extra footage of each screen for transitions

## Visual Style Guide

### Colors
- **Highlight Color:** #3B82F6 (Blue) for important UI elements
- **Success Color:** #10B981 (Green) for checkmarks and success states
- **Warning Color:** #F59E0B (Amber) for warnings
- **Error Color:** #EF4444 (Red) for errors
- **Background:** #F9FAFB (Light gray) for callout boxes

### Typography
- **Main Overlay Text:** Roboto Bold, 48px
- **Subtitle Text:** Roboto Regular, 32px
- **Code/Data Text:** Roboto Mono, 28px
- **Small Notes:** Roboto Regular, 24px

### Animation
- **Transitions:** 0.3s smooth fade
- **Callout Entry:** Slide in from right
- **Checkmarks:** Scale pop animation (0.2s)
- **Progress Bars:** Smooth 1s animation
- **Highlights:** Pulse animation on important elements

### Callout Design
- **Style:** Rounded corners (8px radius)
- **Shadow:** Subtle drop shadow (0 4px 6px rgba(0,0,0,0.1))
- **Border:** 2px solid matching color
- **Padding:** 16px internal padding
- **Max Width:** 400px for text callouts

## Audio Production

### Voice Recording
- **Environment:** Quiet room with minimal echo
- **Microphone Distance:** 6-8 inches from mouth
- **Pop Filter:** Always use to reduce plosives
- **Script Reading:** Natural pace, not too fast
- **Retakes:** Record problematic sections separately
- **Breaths:** Edit out loud breaths between sentences

### Audio Post-Production
- **Noise Reduction:** Use iZotope RX or Audacity noise reduction
- **Equalization:**
  - High-pass filter at 80Hz
  - Slight boost at 3-5kHz for clarity
  - Reduce muddy frequencies around 200-300Hz
- **Compression:** Light compression (3:1 ratio, -20dB threshold)
- **Normalization:** -3dB peak level
- **Background Music:** Subtle, royalty-free music at -25dB (25% volume)

### Background Music Suggestions
- **Intro/Outro:** Upbeat, modern, professional
- **Tutorial Sections:** Soft, non-distracting ambient music
- **Success Moments:** Brief celebratory music sting
- **Fade In/Out:** 2-second fade transitions

## Video Editing

### Structure
1. **Intro Sequence (0:00-0:10):**
   - Facturino logo animation
   - Title card: "Migration Wizard Tutorial"
   - Upbeat intro music

2. **Main Content (0:10-10:30):**
   - Follow script timestamps exactly
   - Add visual callouts as specified
   - Include text overlays at key moments
   - Use smooth transitions between sections

3. **Outro Sequence (10:30-11:00):**
   - Summary of accomplishments
   - Call to action
   - Subscribe button
   - Related video suggestions
   - Fade to black with music

### Transitions
- **Between Major Sections:** 0.5s cross-fade
- **Within Sections:** Direct cuts (no transition)
- **To Callouts:** Slide in from right
- **Error Examples:** Quick fade to red overlay

### Pacing
- **Cursor Movement:** Slow and smooth (use ease-in/ease-out)
- **Text Entry:** Slightly faster than real-time, but readable
- **Screen Changes:** Brief pause (0.5s) after navigation
- **Validation/Processing:** Show realistic timing, can speed up 2x

## Example CSV Data Files

### customers_example.csv
```csv
name,email,phone,address,vat_number,website,currency
Македонска Трговска ДООЕЛ,info@makedonska.mk,+389 2 123 4567,"улица Македонија 123, Скопје, 1000",MK4080012562345,https://makedonska.mk,MKD
ТехноСофт ООД,contact@tehnosoft.mk,+389 2 234 5678,"булевар Партизански Одреди 45, Скопје, 1000",MK4080013673456,https://tehnosoft.mk,MKD
Европа Консалтинг ДООЕЛ,info@europa.com.mk,+389 2 345 6789,"улица Димитрие Чуповски 15, Скопје, 1000",MK4080014784567,https://europa.com.mk,EUR
```

### invoices_example.csv
```csv
invoice_number,customer_email,invoice_date,due_date,subtotal,tax,total,status,currency,notes
INV-2025-001,info@makedonska.mk,2025-01-15,2025-02-15,10000.00,1800.00,11800.00,SENT,MKD,Месечна услуга за јануари 2025
INV-2025-002,contact@tehnosoft.mk,2025-01-20,2025-02-20,25000.00,4500.00,29500.00,PAID,MKD,Развој на софтвер - фаза 1
INV-2025-003,info@europa.com.mk,2025-01-25,2025-02-25,1500.00,270.00,1770.00,DRAFT,EUR,Консултантски услуги
```

### items_example.csv
```csv
name,description,price,unit,category,sku,tax_type,tax_rate
Веб Развој - Часовно,Развој на веб апликации,2500.00,hour,Services,WEB-DEV-001,Standard,18.00
Графички Дизајн,Логотипи и графички материјали,1500.00,project,Design,GFX-DES-001,Standard,18.00
Хостинг Услуга,Месечен хостинг пакет,500.00,month,Hosting,HOST-001,Reduced,5.00
Домен Регистрација,.mk домен за 1 година,1200.00,year,Domains,DOM-MK-001,Zero,0.00
```

### payments_example.csv
```csv
invoice_number,payment_date,amount,payment_method,reference,currency,notes
INV-2025-001,2025-01-16,11800.00,Bank Transfer,PAY-001-2025,MKD,Уплата преку банка
INV-2025-002,2025-01-22,29500.00,Bank Transfer,PAY-002-2025,MKD,Комплетна уплата
```

## Quality Checklist

### Pre-Recording
- [ ] Script reviewed and approved
- [ ] Example data prepared
- [ ] Clean browser profile created
- [ ] Desktop cleaned and organized
- [ ] Notifications disabled
- [ ] Microphone tested
- [ ] Recording software configured
- [ ] Backup recording device ready

### During Recording
- [ ] Cursor movements slow and deliberate
- [ ] Clear audio with no background noise
- [ ] All UI elements visible and readable
- [ ] Cyrillic characters display correctly
- [ ] No personal information visible
- [ ] Each section recorded multiple times
- [ ] Timestamps noted for each section

### Post-Production
- [ ] Audio cleaned and normalized
- [ ] Background music added at appropriate levels
- [ ] All callouts and overlays added
- [ ] Transitions smooth and professional
- [ ] Text overlays readable at all sizes
- [ ] Color correction applied
- [ ] Subtitles/captions added (optional but recommended)
- [ ] Export at 1080p 30fps minimum

### Final Review
- [ ] Watch entire video start to finish
- [ ] Check for audio sync issues
- [ ] Verify all Cyrillic text is readable
- [ ] Confirm all links and URLs are correct
- [ ] Test video on different devices
- [ ] Verify file size is reasonable for upload
- [ ] Get stakeholder approval before publishing

## Export Settings

### YouTube Upload
- **Resolution:** 1920x1080 (1080p)
- **Frame Rate:** 30fps
- **Codec:** H.264
- **Bitrate:** 8-10 Mbps
- **Audio:** AAC, 192kbps, 48kHz
- **Format:** MP4

### Thumbnail
- **Size:** 1280x720 pixels
- **Format:** JPG or PNG
- **File Size:** <2MB
- **Text:** Large, readable text highlighting "Migration Wizard Tutorial"
- **Branding:** Facturino logo visible
- **Visual:** Screenshot of completed import with success indicators

## Metadata for Publishing

### Title
"Facturino Migration Wizard Tutorial - Import Customers, Invoices & Payments (Macedonia)"

### Description
```
Learn how to migrate your accounting data to Facturino using the Universal Migration Wizard. This comprehensive tutorial covers:

✓ Downloading CSV templates
✓ Preparing your data with Macedonian formats
✓ Uploading and validating files
✓ Field mapping and data transformation
✓ Completing the import process
✓ Troubleshooting common issues

Perfect for users migrating from Onivo, Megasoft, or any other accounting system.

🇲🇰 Macedonian-specific features:
• VAT number format (MK + 13 digits)
• Cyrillic character support
• Macedonian Denar (MKD) currency
• Tax rates: 18%, 5%, 0%

Timestamps:
0:00 - Introduction
0:30 - Prerequisites
1:30 - Download Template
2:30 - Fill in Data
4:30 - Upload Files
5:30 - Review Field Mapping
7:00 - Validate Data
8:00 - Complete Import
9:00 - Common Issues & Fixes
10:30 - Conclusion

📚 Resources:
• Help Documentation: https://help.facturino.mk
• CSV Templates: Available in Migration Wizard
• Support: support@facturino.mk

#Facturino #MigrationWizard #Accounting #Macedonia #Tutorial #DataImport #CSV #InvoiceSoftware
```

### Tags
facturino, migration wizard, data import, csv import, accounting software, macedonia, macedonian accounting, invoice software, onivo migration, megasoft migration, cyrillic support, vat numbers, data migration, tutorial, how to

---

# SCRIPT STATISTICS

- **Total Word Count:** ~2,850 words
- **Estimated Speaking Time:** ~11 minutes (at 260 words/minute)
- **Total Video Duration:** ~11 minutes (including pauses and demonstrations)
- **Number of Sections:** 10 major sections
- **Number of Visual Callouts:** 45+
- **Number of Screen Recording Segments:** 25+
- **Example CSV Records:** 12 complete examples

# KEY TOPICS COVERED

1. ✓ System navigation and wizard access
2. ✓ Source system selection (Onivo, Megasoft, CSV, Excel)
3. ✓ Template download and structure
4. ✓ Data preparation with Macedonian formats
5. ✓ Cyrillic character support
6. ✓ VAT number formatting (MK + 13 digits)
7. ✓ CSV UTF-8 encoding
8. ✓ File upload process
9. ✓ Intelligent field mapping
10. ✓ Auto-mapping with confidence scores
11. ✓ Macedonian tax rate mapping (18%, 5%, 0%)
12. ✓ Data validation and error checking
13. ✓ Import progress tracking
14. ✓ Success confirmation and reporting
15. ✓ Common error troubleshooting
16. ✓ Date format issues
17. ✓ Character encoding problems
18. ✓ VAT number validation
19. ✓ Payment linking to invoices
20. ✓ Duplicate handling

# FILES TO BE CREATED

1. **VIDEO_TUTORIAL_SCRIPT.md** - This file (Complete) ✓
2. **QUICK_REFERENCE_GUIDE.pdf** - One-page visual guide (See below)

---

# NEXT STEPS

After video production:
1. Record and edit video following this script
2. Create Quick Reference PDF guide
3. Upload to YouTube/Vimeo
4. Add video to Facturino help documentation
5. Create shorter clips for social media
6. Translate script to Macedonian for МК version
7. Add subtitles in both English and Macedonian

---

**Document Version:** 1.0
**Last Updated:** 2025-11-12
**Author:** Facturino Documentation Team
**Status:** Ready for Production
