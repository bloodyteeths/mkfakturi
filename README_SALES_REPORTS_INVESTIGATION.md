# Sales Reports Investigation - Complete Documentation

**Investigation Date:** 2025-11-14  
**Issue:** Dashboard shows 8.437.000 ден but Sales Reports show 0 денари  
**Status:** Root causes identified, 4 critical bugs documented

---

## Quick Start

### If You're Busy
Read: **SALES_REPORTS_INVESTIGATION_SUMMARY.txt** (5 min read)
- 4 critical bugs identified
- Root causes explained
- Fix priorities listed

### If You Need Details
Read: **SALES_REPORTS_INVESTIGATION.md** (15 min read)
- Complete failure chain analysis
- Dashboard vs Reports comparison
- Field mapping issues
- Solution roadmap

### If You're Implementing Fixes
Read: **INVESTIGATION_CODE_LOCATIONS.md** (reference)
- Exact file paths with absolute paths
- Line numbers for each bug
- Code snippets showing the problems
- Evidence from schema migrations

---

## File Guide

### 1. SALES_REPORTS_INVESTIGATION_SUMMARY.txt
**Length:** ~500 lines  
**Format:** Plain text with ASCII tables  
**Audience:** Decision makers, team leads  
**Contains:**
- Executive summary
- 4 bugs with locations and fixes
- Why dashboard works but reports don't
- Schema mismatch explanation
- Failure chain diagram
- Testing checklist
- Affected files list

**Read this first if you want the TL;DR**

---

### 2. SALES_REPORTS_INVESTIGATION.md
**Length:** ~1500 lines  
**Format:** Markdown with code blocks  
**Audience:** Developers implementing fixes  
**Contains:**
- Detailed explanation of each bug
- How each bug causes report failure
- Complete data flow analysis
- Dashboard calculation vs Report calculation
- Field mapping comparison tables
- Complete failure chain diagram
- Solution roadmap with code examples
- Current state analysis

**Read this for comprehensive understanding**

---

### 3. INVESTIGATION_CODE_LOCATIONS.md
**Length:** ~600 lines  
**Format:** Markdown with file paths  
**Audience:** Developers fixing the code  
**Contains:**
- Exact file paths (absolute paths)
- Line numbers for each bug
- Code snippets showing problems
- Evidence from database migrations
- Query evidence from reports
- Database schema evidence
- Summary table with all locations

**Use this as your reference while coding fixes**

---

## The 4 Critical Bugs

### Bug #1: Wrong Field Name
```
File: /app/Jobs/Migration/CommitImportJob.php
Lines: 208, 325, 434, 539, 611
Bug: ->where('validation_status', 'valid')
Fix: ->where('status', 'validated')
Impact: Blocks ALL imports (query returns 0 rows)
Severity: CRITICAL
```

### Bug #2: Non-Existent Column
```
File: /app/Jobs/Migration/CommitImportJob.php
Lines: 219, 336, 445, etc.
Bug: json_decode($tempRecord->transformed_data, true)
Fix: Access columns directly ($tempRecord->invoice_number, etc.)
Impact: Data retrieval fails
Severity: CRITICAL
```

### Bug #3: Missing Customer Link
```
File: /app/Jobs/Migration/CommitImportJob.php
Lines: 356-362
Bug: No customer_id set in invoice creation
Fix: Resolve temp_customer_id to actual customer_id
Impact: Foreign key constraint fails
Severity: CRITICAL
```

### Bug #4: Items Not Created
```
File: /app/Jobs/Migration/CommitImportJob.php
Lines: 431-460
Bug: commitItems() has same validation_status bug
Fix: Fix validation_status -> status issue
Impact: No invoice items created, reports show 0
Severity: CRITICAL
```

---

## How to Use These Documents

### For Investigation Review
1. Start with: SALES_REPORTS_INVESTIGATION_SUMMARY.txt
2. If more detail needed: SALES_REPORTS_INVESTIGATION.md
3. For code changes: INVESTIGATION_CODE_LOCATIONS.md

### For Bug Fixes
1. Read: INVESTIGATION_CODE_LOCATIONS.md section "BUG #X"
2. Find the exact file and line number
3. Compare with the code provided
4. Implement the fix
5. Use Testing Checklist to verify

### For Team Communication
1. Share: SALES_REPORTS_INVESTIGATION_SUMMARY.txt
2. Include: Link to SALES_REPORTS_INVESTIGATION.md for deep dives
3. Reference: Specific line numbers from INVESTIGATION_CODE_LOCATIONS.md

---

## Key Insights

### Why It Fails
1. CommitImportJob queries wrong field name (`validation_status` vs `status`)
2. Query returns 0 rows
3. No invoices created in production
4. No invoice items created
5. Reports query empty invoice_items table
6. Results show 0 for all sales

### Why Dashboard Still Works
1. Dashboard uses Invoice.base_total directly
2. Dashboard might use Payment records instead
3. Dashboard shows 8.437.000 ден from other source

### The Root Cause
Code and schema are out of sync:
- Schema created with direct columns
- Code written expecting JSON fields
- Code never tested with real data

---

## Database Evidence

### What Exists
```
import_temp_invoices:
- status column (enum: pending, validated, mapped, failed, committed)
- invoice_number, total, etc. columns
- ready to be processed ✓

invoice_items table:
- completely empty ✗
- never populated because commit job fails ✗
```

### Why Reports Show 0
```
SELECT SUM(invoice_items.base_total) 
FROM invoice_items
WHERE created_at BETWEEN ... AND ...

Result: 0 or NULL (table is empty)
```

---

## Implementation Priority

### Priority 1 (MUST FIX)
Fix `validation_status` field name  
Impact: Unblocks all imports  
Time: 5 minutes

### Priority 2 (MUST FIX)
Fix data access pattern  
Impact: Enables data retrieval  
Time: 15 minutes

### Priority 3 (MUST FIX)
Add customer_id resolution  
Impact: Fixes FK constraint  
Time: 10 minutes

### Priority 4 (MUST FIX)
Create invoice items from temp items  
Impact: Fixes reports data  
Time: 20 minutes

**Total estimated fix time: 50 minutes**

---

## Verification Steps

### Quick Check
```sql
SELECT COUNT(*) FROM import_temp_invoices WHERE status = 'validated';
-- Should return > 0

SELECT COUNT(*) FROM invoices;
-- Should return > 0 (after fixes)

SELECT COUNT(*) FROM invoice_items;
-- Should return > 0 (after fixes)
```

### Full Test
1. Check logs for "Import commit started"
2. Check logs for "Import commit completed"
3. Check Dashboard still shows 8.437.000 ден
4. Check Customer Sales Report shows > 0 денари
5. Check Item Sales Report shows > 0 денари
6. Check no validation_status errors

---

## Related Documents

Also in the project:
- **MIGRATION_FIX_SUMMARY.md** - Previous mapping rule fixes
- **CLAUDE.md** - Project rules and guidelines
- **.claude/CLAUDE.md** - Claude code rules

---

## Next Steps

1. **Read:** Start with SALES_REPORTS_INVESTIGATION_SUMMARY.txt
2. **Understand:** Review SALES_REPORTS_INVESTIGATION.md for full context
3. **Locate:** Use INVESTIGATION_CODE_LOCATIONS.md to find exact locations
4. **Fix:** Apply fixes in priority order
5. **Test:** Run testing checklist to verify
6. **Commit:** Create PR with fixes

---

## Questions?

Refer to:
- **How does it fail?** → SALES_REPORTS_INVESTIGATION.md (Failure Chain section)
- **Where's the bug?** → INVESTIGATION_CODE_LOCATIONS.md (with line numbers)
- **What do I fix?** → SALES_REPORTS_INVESTIGATION_SUMMARY.txt (Fix Priority section)
- **How do I verify?** → SALES_REPORTS_INVESTIGATION_SUMMARY.txt (Testing Checklist)

---

**Generated:** 2025-11-14  
**Status:** Analysis complete, ready for implementation  
**Confidence:** HIGH (schema verification + code analysis)
