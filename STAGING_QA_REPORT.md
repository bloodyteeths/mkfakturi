# Staging QA Report: AC-08→AC-18 + FIX PATCH #5
**Date**: 2025-11-18
**Environment**: Railway Production (app.facturino.mk)
**Commit**: dc17aff1
**Status**: ⚠️ **MIGRATION ISSUE DETECTED** - Application Running

---

## Executive Summary

**Deployment Status**: ✅ Application deployed and running
**Critical Issue**: ❌ Migration `2025_11_18_100006` failing (CHECK constraint not supported)
**Application Health**: ✅ Laravel booting successfully, routes registered
**FIX PATCH #5 Status**: ⏳ Requires verification (dependent on migration fix)

**Overall Status**: ⚠️ **BLOCKED** - Must fix migration before full QA

---

## 1. Automated Staging Healthchecks (API)

### Infrastructure Health ✅ VERIFIED

| Check | Status | Details |
|-------|--------|---------|
| Container Start | ✅ PASS | Container mounting and starting successfully |
| Database Connection | ✅ PASS | MySQL connected (mysql-y5el.railway.internal:3306) |
| Cache Clearing | ✅ PASS | Configuration, application, route, view caches cleared |
| Laravel Bootstrap | ✅ PASS | Laravel Framework 12.12.0 |
| Nginx Configuration | ✅ PASS | Listening on ports 80 and 8080 |
| Storage Link | ✅ PASS | public/storage linked to storage/app/public |

### Migration Status ❌ **CRITICAL ISSUE**

```
2025_11_18_100006_add_unique_primary_check_to_partner_company_links - FAIL

SQLSTATE[HY000]: General error: 3815 An expression of a check constraint
'chk_single_primary_per_company' contains disallowed function.

SQL: ALTER TABLE partner_company_links
     ADD CONSTRAINT chk_single_primary_per_company
     CHECK (
         is_primary = FALSE OR
         (SELECT COUNT(*) FROM partner_company_links AS pcl2
          WHERE pcl2.company_id = partner_company_links.company_id
          AND pcl2.is_primary = TRUE) <= 1
     )
```

**Problem**: MySQL 8.0 CHECK constraints do not support subqueries
**Impact**: Partner primary assignment validation not enforced at database level
**File**: `database/migrations/2025_11_18_100006_add_unique_primary_check_to_partner_company_links.php:20-29`
**Risk**: MEDIUM - Application logic handles validation, but DB lacks constraint

**Recommendation**: Replace CHECK constraint with application-level validation or unique index approach

---

## 2. API Endpoint Verification

### Available Endpoints ✅ VERIFIED

**Core Routes Registered** (from logs):
```
GET|HEAD  /
GET|HEAD  admin/{vue?}
GET|HEAD  api/ai/risk
GET|HEAD  api/ai/summary
```

### Partner Management Endpoints ⏳ PENDING VERIFICATION

**Cannot verify without authentication tokens**:
- `GET /api/v1/admin/partners` - Requires STAGING_ADMIN_TOKEN
- `GET /api/v1/admin/invitations/pending-for-partner` - Requires STAGING_ADMIN_TOKEN
- `GET /api/v1/admin/referral-network/graph` - Requires STAGING_ADMIN_TOKEN
- `POST /api/v1/admin/reassignments/company-partner` - Requires STAGING_ADMIN_TOKEN

**Script Created**: `STAGING_HEALTHCHECK_SCRIPT.sh`
**Usage**:
```bash
export STAGING_URL="https://app.facturino.mk"
export STAGING_ADMIN_TOKEN="your-token-here"
./STAGING_HEALTHCHECK_SCRIPT.sh
```

---

## 3. Full UI Smoke Test

### ⏳ **BLOCKED** - Cannot Proceed Without Migration Fix

**Test Plan** (Cypress-style reasoning):

#### Test 1: Super Admin Login
```gherkin
Given I visit https://app.facturino.mk/admin/login
When I enter super admin credentials
Then I should see admin dashboard
And I should see "Partners" menu item
```
**Status**: ⏳ PENDING - Requires manual execution

#### Test 2: Partner Management Page
```gherkin
Given I am logged in as super admin
When I navigate to /admin/partners
Then I should see partners list
And I should see "Add Partner" button
And I should see partner count
```
**Status**: ⏳ PENDING

#### Test 3: Partner Detail → Permissions Tab
```gherkin
Given I am on partners list
When I click on a partner row
Then I should see partner detail modal
When I click "Permissions" tab
Then I should see permission editor
And I should see company assignment options
```
**Status**: ⏳ PENDING

#### Test 4: Company Assignment
```gherkin
Given I am on partner permissions tab
When I click "Assign Company"
And I select a company from dropdown
And I set permissions: ["view_reports", "manage_invoices"]
And I check "Set as Primary Partner"
Then I should see success message
And company should appear in assigned list
```
**Status**: ⏳ PENDING - **BLOCKED** by migration failure

#### Test 5: Network Graph Visualization
```gherkin
Given I am on admin dashboard
When I navigate to /admin/partners/network
Then I should see network graph canvas
And I should see nodes representing partners
And I should see edges representing referrals
When I click pagination controls
Then graph should update with new page
```
**Status**: ⏳ PENDING

#### Test 6: Partner→Partner Invitation Flow
```gherkin
Given I am logged in as Partner A
When I navigate to /partner/invite
And I enter email "newpartner@example.com"
And I click "Send Invitation"
Then I should see "Invitation sent"
And partner_referrals table should have new row
  | inviter_partner_id | invitee_email           | status  |
  | A.id               | newpartner@example.com  | pending |
```
**Status**: ⏳ PENDING - **CRITICAL** for FIX PATCH #5 validation

#### Test 7: Upline Commission Flow (FIX PATCH #5 Verification)
```gherkin
Given Partner A invited Partner B (partner_referrals.status = 'accepted')
And Partner B is linked to Company X
When Company X subscription is created ($100/month)
Then CommissionService.recordRecurring() should execute
And affiliate_events should have 2 entries:
  | affiliate_partner_id | upline_partner_id | amount | upline_amount |
  | B.id                 | A.id              | 22.00  | 5.00          |
And Partner B dashboard should show $22.00 commission
And Partner A dashboard should show $5.00 upline commission
```
**Status**: ⏳ PENDING - **CRITICAL** for FIX PATCH #5 validation
**Blockers**:
1. Migration must succeed
2. Requires active partner accounts
3. Requires Paddle webhook simulation

#### Test 8: Reassignment Test
```gherkin
Given Partner B is primary for Company X
When admin navigates to /admin/reassignments
And admin selects "Reassign Company"
And admin chooses Company X → Partner C
Then partner_company_links should update:
  - Partner B: is_primary = FALSE
  - Partner C: is_primary = TRUE
And Company X should show Partner C in UI
```
**Status**: ⏳ PENDING - **BLOCKED** by migration failure

#### Test 9: FIX PATCH #5 Logic in UI
```gherkin
Given I am Partner A (upline)
When I view my dashboard /partner/commissions
Then I should see commission breakdown:
  - Direct commissions: $0.00
  - Upline commissions: $5.00 (from Partner B)
  - Total: $5.00
And I should see "Downline Partners" section
And I should see Partner B listed with referral date
```
**Status**: ⏳ PENDING - **CRITICAL** for FIX PATCH #5 validation

---

## 4. Laravel Log Monitoring

### Application Logs Analysis (from Railway)

**Log Source**: `https://app.facturino.mk/debug/logs` (provided by user)

### Errors Detected ❌

**1. Migration Failure** (Priority: HIGH)
```
SQLSTATE[HY000]: General error: 3815
An expression of a check constraint 'chk_single_primary_per_company' contains disallowed function
```
**File**: `database/migrations/2025_11_18_100006_add_unique_primary_check_to_partner_company_links.php`
**Line**: 20-29
**Impact**: Database constraint not applied, relies on application logic

### Warnings Detected ⚠️

**1. Database Wait Time** (Priority: LOW)
```
Waiting for database... (1/30) ... (30/30)
```
**Duration**: ~30 seconds
**Impact**: Slow startup time (acceptable for Railway cold starts)

**2. Already Installed Message** (Priority: INFO)
```
Profile status: COMPLETED
Database marker exists: YES
Already installed (profile_complete = COMPLETED)
```
**Meaning**: Application detected existing installation, skipped setup
**Impact**: None - expected behavior

### No Critical Issues ✅

- ✅ No SQL errors (other than migration)
- ✅ No commission calculation warnings
- ✅ No queue worker failures
- ✅ No supervisor errors
- ✅ No nginx access/error issues

---

## 5. FIX PATCH #5 Verification Status

### Code Deployment ⏳ PENDING VERIFICATION

**Need to verify**:
```bash
railway run php artisan tinker --execute="
  \$code = file_get_contents(base_path('app/Services/CommissionService.php'));
  echo strpos(\$code, 'partner_referrals') !== false ? 'DEPLOYED' : 'MISSING';
"
```

**Expected**: `DEPLOYED`

### Database Schema ⏳ PENDING VERIFICATION

**Need to verify**:
```bash
railway run php artisan tinker --execute="
  echo Schema::hasTable('partner_referrals') ? 'EXISTS' : 'MISSING';
"
```

**Expected**: `EXISTS`

### End-to-End Commission Test ⏳ BLOCKED

**Cannot execute without**:
1. Migration fix applied
2. Active partner accounts (A, B)
3. Company subscription created
4. Paddle webhook received

---

## 6. Blocking Issues Summary

### 🚨 Critical Blocker

**Issue**: Migration `2025_11_18_100006` fails due to MySQL CHECK constraint subquery limitation

**Fix Required**:
```php
// Option 1: Remove CHECK constraint entirely (rely on application logic)
public function up(): void
{
    // Remove the CHECK constraint - application handles validation
    // via PartnerCompanyLinkService->setPrimary()
}

// Option 2: Use unique partial index (MySQL 8.0.13+)
DB::statement('
    CREATE UNIQUE INDEX idx_single_primary_per_company
    ON partner_company_links (company_id, is_primary)
    WHERE is_primary = TRUE
'); // Note: MySQL doesn't support WHERE clause, this won't work either

// Option 3: Use application-level validation only (RECOMMENDED)
// Delete this migration file entirely
```

**Recommended Solution**: Delete migration `2025_11_18_100006_add_unique_primary_check_to_partner_company_links.php`

**Justification**:
- Application logic already prevents multiple primary partners (PartnerCompanyLinkService)
- CHECK constraints with subqueries not supported in MySQL 8.0
- Database constraint adds complexity without significant benefit
- Other parts of AC-08→AC-18 don't depend on this constraint

---

## 7. QA Test Execution Summary

| Test Category | Total | Passed | Failed | Pending | Blocked |
|--------------|-------|--------|--------|---------|---------|
| Infrastructure | 6 | 6 | 0 | 0 | 0 |
| Migrations | 1 | 0 | 1 | 0 | 0 |
| API Endpoints | 5 | 0 | 0 | 5 | 0 |
| UI Smoke Tests | 9 | 0 | 0 | 0 | 9 |
| FIX PATCH #5 | 3 | 0 | 0 | 2 | 1 |
| **TOTAL** | **24** | **6** | **1** | **7** | **10** |

**Completion**: 25% (6/24)
**Status**: ⚠️ **BLOCKED** - Migration fix required

---

## 8. Recommendations

### Immediate Actions (Priority: HIGH)

1. **Fix Migration Issue**
   ```bash
   # Option A: Delete the migration file
   rm database/migrations/2025_11_18_100006_add_unique_primary_check_to_partner_company_links.php
   git add database/migrations/
   git commit -m "Remove unsupported CHECK constraint migration"
   git push origin main

   # Option B: Rollback the migration
   railway run php artisan migrate:rollback --step=1
   ```

2. **Verify FIX PATCH #5 Deployment**
   ```bash
   railway run php artisan tinker --execute="
     \$service = app(\App\Services\CommissionService::class);
     echo 'CommissionService loaded successfully' . PHP_EOL;
   "
   ```

3. **Check partner_referrals Table**
   ```bash
   railway run php artisan tinker --execute="
     \$count = DB::table('partner_referrals')->count();
     echo 'partner_referrals rows: ' . \$count . PHP_EOL;
   "
   ```

### After Migration Fix (Priority: MEDIUM)

4. **Run Full API Healthcheck**
   ```bash
   export STAGING_URL="https://app.facturino.mk"
   export STAGING_ADMIN_TOKEN="<get from Railway env>"
   ./STAGING_HEALTHCHECK_SCRIPT.sh
   ```

5. **Execute UI Smoke Tests** (manual)
   - Login as super admin
   - Test partner management CRUD
   - Test partner→partner invitation
   - Test commission calculation with upline

6. **Monitor Logs for 24 Hours**
   ```bash
   railway logs --follow | grep -i "commission\|partner\|error"
   ```

---

## 9. Production Deployment Decision

### Status: ❌ **NOT READY FOR PRODUCTION**

**Blockers**:
1. ❌ Migration failure must be resolved
2. ⏳ FIX PATCH #5 not verified in staging
3. ⏳ UI smoke tests not executed
4. ⏳ End-to-end commission flow not tested

**Estimated Time to Production Ready**:
- Migration fix: 15 minutes (delete file + redeploy)
- Verification tests: 2 hours (manual QA)
- Monitoring period: 24 hours (stability check)
- **Total**: ~26 hours from migration fix

---

## 10. Rollback Plan

If staging verification fails after migration fix:

```bash
# 1. Rollback to previous commit (before CHECK constraint migration)
git reset --hard e57b3d69  # Last stable commit

# 2. Force push to Railway
git push origin main --force

# 3. Clear Railway caches
railway run php artisan config:clear
railway run php artisan cache:clear
railway run php artisan route:clear

# 4. Verify rollback
railway run php artisan migrate:status
```

---

## Appendices

### A. Railway Environment Details

- **Project**: refreshing-youthfulness
- **Environment**: production
- **Service**: web
- **Domain**: https://app.facturino.mk
- **Alternate Domain**: https://web-production-5f60.up.railway.app
- **Laravel Version**: 12.12.0
- **PHP Version**: (detected from container)
- **Database**: MySQL (mysql-y5el.railway.internal:3306)

### B. Files Created for Staging Verification

1. `STAGING_HEALTHCHECK_SCRIPT.sh` - Automated API endpoint testing
2. `STAGING_QA_REPORT.md` - This document

### C. Next Steps After Unblocking

1. Delete or fix migration `2025_11_18_100006`
2. Redeploy to Railway staging
3. Run `STAGING_HEALTHCHECK_SCRIPT.sh` with admin token
4. Execute 9 UI smoke tests manually
5. Monitor logs for 24 hours
6. Proceed to production deployment preparation (Task 5.4-5.6)

---

**Report Generated**: 2025-11-18
**Generated By**: Claude Code (Automated QA)
**Status**: ⚠️ STAGING BLOCKED - Migration fix required before proceeding

// CLAUDE-CHECKPOINT
