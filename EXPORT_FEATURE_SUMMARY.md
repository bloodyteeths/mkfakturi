# Export Feature Implementation Summary

## Overview
Added export functionality to list views allowing users to download their data in CSV, XLSX, and PDF formats.

## Changes Made

### Backend Changes

#### 1. ExportController.php
- **File**: `/Users/tamsar/Downloads/mkaccounting/app/Http/Controllers/V1/Admin/ExportController.php`
- **Change**: Added 'items' to the list of supported export types
- **Line 22**: Updated validation to include `'items'` in the type enum

#### 2. ProcessExportJob.php
- **File**: `/Users/tamsar/Downloads/mkaccounting/app/Jobs/ProcessExportJob.php`
- **Change**: Added support for exporting items data
- **Line 88**: Added case for `'items' => \App\Models\Item::where('company_id', $this->exportJob->company_id)`

### Frontend Changes

#### 3. ExportButton.vue (NEW)
- **File**: `/Users/tamsar/Downloads/mkaccounting/resources/scripts/admin/components/ExportButton.vue`
- **Type**: New reusable component
- **Features**:
  - Dropdown button with export format options (CSV, Excel, PDF)
  - Accepts `type` prop (invoices, customers, suppliers, expenses, items, etc.)
  - Accepts `filters` prop to pass current filter state to export
  - Calls export API endpoint
  - Shows notification when export starts
  - Polls for export completion (up to 30 attempts)
  - Auto-downloads file when ready
  - Handles error states

#### 4. List View Updates
Added ExportButton component to all specified list views:

- **Invoices Index** (`/Users/tamsar/Downloads/mkaccounting/resources/scripts/admin/views/invoices/Index.vue`)
  - Line 28-33: Added ExportButton component
  - Line 305: Imported ExportButton component

- **Customers Index** (`/Users/tamsar/Downloads/mkaccounting/resources/scripts/admin/views/customers/Index.vue`)
  - Line 32-36: Added ExportButton component
  - Line 215: Imported ExportButton component

- **Suppliers Index** (`/Users/tamsar/Downloads/mkaccounting/resources/scripts/admin/views/suppliers/Index.vue`)
  - Line 26-31: Added ExportButton component
  - Line 123: Imported ExportButton component

- **Expenses Index** (`/Users/tamsar/Downloads/mkaccounting/resources/scripts/admin/views/expenses/Index.vue`)
  - Line 27-32: Added ExportButton component
  - Line 239: Imported ExportButton component

- **Items Index** (`/Users/tamsar/Downloads/mkaccounting/resources/scripts/admin/views/items/Index.vue`)
  - Line 27-31: Added ExportButton component
  - Line 225: Imported ExportButton component

#### 5. Translations
- **File**: `/Users/tamsar/Downloads/mkaccounting/lang/en.json`
- **Added keys**:
  - `export_csv`: "CSV"
  - `export_excel`: "Excel (XLSX)"
  - `export_pdf`: "PDF"
  - `export_started`: "Export job started. You will be able to download the file shortly."
  - `export_ready`: "Export ready! Downloading now..."
  - `export_failed`: "Export failed. Please try again."
  - `export_taking_long`: "Export is taking longer than expected. It will be available in your exports list when ready."

## How It Works

1. **User clicks Export button** in any list view
2. **Dropdown shows format options**: CSV, Excel, PDF
3. **User selects format**
4. **Component makes API call** to `POST /api/v1/admin/companies/{company}/exports` with:
   - `type`: The data type (invoices, customers, etc.)
   - `format`: Selected format (csv, xlsx, pdf)
   - `params`: Current filters (start_date, end_date, status, etc.)
5. **Backend creates ExportJob** and dispatches ProcessExportJob
6. **Frontend polls** for job completion every 2 seconds (up to 60 seconds)
7. **When complete**, file automatically downloads via browser
8. **User gets notifications** at each stage (started, ready, failed, taking long)

## API Endpoints Used

- `POST /api/v1/admin/companies/{company}/exports` - Create export job
- `GET /api/v1/admin/companies/{company}/exports` - List export jobs (for polling)
- `GET /api/v1/admin/companies/{company}/exports/{exportJob}/download` - Download completed export

## Supported Export Types

- invoices
- customers
- suppliers
- expenses
- items
- bills
- payments
- transactions

## Filter Support

The export respects current filters applied in the UI:
- Date ranges (from_date/to_date)
- Status filters
- Other filters are passed through the `params` object

## Notes

- Exports are processed asynchronously via queue jobs
- Export files are stored for 7 days
- Each user can only access their own export jobs
- The polling mechanism provides instant feedback for small exports
- For large exports, users are notified to check their exports list
