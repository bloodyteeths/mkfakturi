# Plan: Multi-File Support for Receipt Scanner & Bank Statement Import

## Context
Both the receipt scanner and bank statement import currently accept only one file at a time. Users want to upload multiple files at once. `BaseFileUploader` already supports `multiple: true` natively.

## Changes

### 1. Receipt Scanner — Multi-File Upload & Batch Processing

**File:** `resources/scripts/admin/views/receipts/Scan.vue`
- Change `BaseFileUploader` from `:multiple="false"` to `:multiple="true"`
- Replace single `scanResult` ref with `scanResults` array
- Update `onFileChange` to collect all selected files into a `selectedFiles` array
- Update `scan()` to loop through files, calling `scannerStore.scanReceipt(file)` sequentially
  - Each result gets appended to `scanResults` array
  - AI overlay shows progress: "Processing file 1 of N..."
- Replace single result display with a scrollable list of results, each with:
  - Thumbnail of scanned receipt
  - Pre-filled bill form (vendor, number, date, amount, tax)
  - "Create Bill" button per result
- Add "Scan" button text: "Scan N Receipts" when multiple files selected

**File:** `resources/scripts/admin/stores/receipt-scanner.js`
- Keep `scanReceipt(file)` as single-file (called in a loop from the Vue component)
- Add `currentFileIndex` and `totalFiles` to state for progress tracking

### 2. Bank Statement Import — Multi-File Upload & Combined Preview

**File:** `resources/scripts/admin/views/banking/ImportStatement.vue`
- Change file input from single to `multiple` attribute
- Replace `uploadedFile` (single ref) with `uploadedFiles` (array ref)
- Update `handleFileSelect`, `handleFileDrop`, `validateAndSetFile` for arrays
- Update file display to show list of selected files with individual remove buttons
- Update `uploadAndPreview`: loop through files, call `/banking/import/preview` for each
  - Collect all `import_id`s and merge all transactions into single preview
  - Store array of `{ import_id, file_name }` objects
- Update `confirmImport`: send all `import_id`s, confirm each sequentially
- AI overlay step text: "Processing file 1 of N..."

**File:** `app/Http/Controllers/V1/Admin/Banking/BankImportController.php`
- No backend changes needed — frontend calls preview per file and confirm per import_id
- The existing single-file endpoints work as-is, called multiple times

### 3. Summary Counts Update

**File:** `resources/scripts/admin/views/banking/ImportStatement.vue`
- After merging previews, recalculate `previewData.total`, `previewData.new`, `previewData.duplicates` from combined transactions array

## Key Insight
`BaseFileUploader` (`resources/scripts/components/base/BaseFileUploader.vue`) already supports multi-file natively via the `multiple` prop. It handles file preview cards, remove buttons, and emits the full file list. No changes needed to the component itself.

## Implementation Order
1. Receipt Scanner: multi-file upload UI + batch scan loop
2. Bank Statement: multi-file upload UI + per-file preview + combined table
3. Test on production with multiple files

## Verification
1. Receipt scanner: select 3 receipt images → scan all → see 3 result cards with pre-filled forms
2. Bank import: select 2 CSV files → see combined preview table → edit → confirm all
3. AI overlay shows file progress during multi-file processing
