# End-to-End UI Testing Guide: AC-08 → AC-18 + FIX PATCH #5
**Environment**: Staging (app.facturino.mk)
**Duration**: ~2-3 hours
**Prerequisites**: Super admin access + test data

---

## 🎯 Testing Objectives

This guide will help you verify:
- ✅ Partner management system (AC-08)
- ✅ Partner-company linking (AC-09)
- ✅ Commission calculations (AC-10)
- ✅ All 4 invitation flows (AC-11, AC-12, AC-14, AC-15)
- ✅ Entity reassignment (AC-16)
- ✅ Network graph visualization (AC-17)
- ✅ Multi-level commissions (AC-18)
- ✅ **FIX PATCH #5**: Upline commission detection

---

## 📋 Pre-Test Setup (15 minutes)

### Step 1: Prepare Test Accounts

You'll need:
- 1 Super Admin account (you)
- 2 Partner accounts (Partner A, Partner B)
- 2 Company accounts (Company X, Company Y)
- Test email addresses (use + addressing: yourmail+partnerA@gmail.com)

### Step 2: Clear Browser Cache
```
1. Open Chrome DevTools (F12)
2. Right-click refresh button → "Empty Cache and Hard Reload"
3. Clear all cookies for app.facturino.mk
```

### Step 3: Login as Super Admin
```
URL: https://app.facturino.mk/admin/login
Email: [your-super-admin-email]
Password: [your-password]
```

**Expected**: Dashboard loads, you see admin sidebar with menu items

---

## 🧪 Test Suite 1: Partner Management (AC-08) - 30 minutes

### Test 1.1: Create Partner A

**Steps**:
1. Click **"Partners"** in left sidebar
   - Expected: Partners list page loads
   - URL: `/admin/partners`

2. Click **"Add Partner"** button (top right)
   - Expected: Modal opens with partner creation form

3. Fill in Partner A details:
   ```
   Name: John Smith
   Email: yourmail+partnerA@gmail.com
   Company Name: Smith Consulting
   Phone: +38970123456
   Tax ID: MK1234567890
   Registration Number: 12345678
   Bank Name: Komercijalna Banka
   Bank Account: 123-456789012-34
   Commission Rate: 22.00
   Notes: Test partner for AC-08→AC-18
   ```

4. Click **"Save"**
   - Expected: Success message "Partner created successfully"
   - Expected: Modal closes, partner appears in list
   - Expected: Partner status shows "Active" with green badge

5. **Verify in list**:
   - Partner name: "John Smith"
   - Company: "Smith Consulting"
   - Email: yourmail+partnerA@gmail.com
   - Status: Active (green)
   - Commission Rate: 22%

**Screenshot**: Take screenshot of partners list

---

### Test 1.2: View Partner Details

**Steps**:
1. Click on **Partner A row** in list
   - Expected: Partner detail modal opens

2. **Verify tabs exist**:
   - ✅ Details
   - ✅ Companies (linked companies)
   - ✅ Commissions (earnings)
   - ✅ Referrals (downline partners)
   - ✅ Permissions

3. Click **"Details"** tab
   - Expected: All info from creation shows correctly
   - Bank account: Shows masked (***-***789012-34) or encrypted

4. Click **"Companies"** tab
   - Expected: Empty state "No companies linked yet"
   - Expected: Button "Link Company"

5. Click **"Commissions"** tab
   - Expected: Empty state "No commissions yet"
   - Expected: Shows commission rate: 22%

**Screenshot**: Partner detail modal

---

### Test 1.3: Edit Partner

**Steps**:
1. In partner detail modal, click **"Edit"** button
   - Expected: Form becomes editable

2. Change commission rate: `22.00` → `20.00`

3. Add note: "Updated commission rate for testing"

4. Click **"Save"**
   - Expected: Success message "Partner updated"
   - Expected: Commission rate now shows 20%

**Verify**: Close modal, reopen partner → commission rate is 20%

---

### Test 1.4: Create Partner B (for FIX PATCH #5 testing)

**Steps**:
1. Click **"Add Partner"** again

2. Fill in Partner B details:
   ```
   Name: Jane Doe
   Email: yourmail+partnerB@gmail.com
   Company Name: Doe Digital Agency
   Phone: +38970654321
   Tax ID: MK9876543210
   Registration Number: 87654321
   Bank Name: Stopanska Banka
   Bank Account: 321-987654321-12
   Commission Rate: 22.00
   Notes: Downline partner for testing FIX PATCH #5
   ```

3. Click **"Save"**
   - Expected: Partner B created
   - Expected: Now have 2 partners in list

**Screenshot**: Partners list showing both Partner A and Partner B

---

### Test 1.5: Partner Activation/Deactivation

**Steps**:
1. Click on **Partner B** row

2. Click **"Deactivate"** button
   - Expected: Confirmation dialog "Are you sure you want to deactivate this partner?"

3. Click **"Confirm"**
   - Expected: Success message "Partner deactivated"
   - Expected: Status badge changes to "Inactive" (gray/red)

4. Click **"Activate"** button
   - Expected: Partner reactivated
   - Expected: Status badge back to "Active" (green)

**Screenshot**: Partner status toggle

---

## 🧪 Test Suite 2: Partner-Company Linking (AC-09) - 20 minutes

### Test 2.1: Create Test Company X

**Steps**:
1. Navigate to **"Companies"** in sidebar

2. Click **"Add Company"**

3. Fill in Company X details:
   ```
   Name: Test Company X
   Email: yourmail+companyX@gmail.com
   Owner: (create new user or select existing)
   ```

4. Click **"Save"**
   - Expected: Company X created

**Repeat for Company Y**:
   ```
   Name: Test Company Y
   Email: yourmail+companyY@gmail.com
   ```

---

### Test 2.2: Link Partner A to Company X (Primary)

**Steps**:
1. Navigate back to **"Partners"**

2. Click on **Partner A** row

3. Click **"Companies"** tab

4. Click **"Link Company"** button
   - Expected: Company selection modal opens

5. Select **"Company X"** from dropdown

6. Set permissions (checkboxes):
   - ✅ View Reports
   - ✅ Manage Invoices
   - ✅ Manage Customers
   - ⬜ Manage Settings (leave unchecked)

7. Check **"Set as Primary Partner"**
   - Expected: Checkbox enabled

8. Click **"Link"**
   - Expected: Success message "Company linked to partner"
   - Expected: Company X appears in "Companies" tab
   - Expected: Badge shows "Primary" next to Company X

**Screenshot**: Partner A companies tab showing Company X as primary

---

### Test 2.3: Link Partner B to Company X (Non-Primary)

**Steps**:
1. Go to **Partner B** detail

2. Click **"Companies"** tab

3. Click **"Link Company"**

4. Select **"Company X"** (same company as Partner A)

5. Set permissions:
   - ✅ View Reports (read-only access)
   - ⬜ All other permissions unchecked

6. **DO NOT** check "Set as Primary Partner"
   - Expected: Checkbox disabled (Company X already has Partner A as primary)
   - Expected: Tooltip: "This company already has a primary partner"

7. Click **"Link"**
   - Expected: Success message
   - Expected: Company X appears in Partner B's companies (no "Primary" badge)

**Screenshot**: Partner B companies tab showing Company X as non-primary

---

### Test 2.4: Verify Primary Partner Constraint

**Steps**:
1. Still in **Partner B** → Companies tab

2. Try to check **"Set as Primary Partner"** for Company X
   - Expected: Error message "Company X already has a primary partner (Partner A)"
   - Expected: Cannot save with primary checked

**Screenshot**: Error message for duplicate primary partner

---

## 🧪 Test Suite 3: Commission Calculations (AC-10) - 30 minutes

### Test 3.1: Create Subscription for Company X

**Steps**:
1. Navigate to **"Companies"**

2. Click on **Company X** row

3. Click **"Subscription"** tab

4. Click **"Create Subscription"** or **"Subscribe"**

5. Select plan:
   - Plan: Professional ($100/month) or your test plan
   - Billing cycle: Monthly

6. Click **"Subscribe"**
   - Expected: Subscription created
   - Expected: Status shows "Active"

**Note**: If using Paddle sandbox, you may need to use test card:
```
Card: 4242 4242 4242 4242
Expiry: 12/25
CVV: 123
```

---

### Test 3.2: Verify Direct Commission Created

**Steps**:
1. Go back to **"Partners"**

2. Click on **Partner A** (primary partner for Company X)

3. Click **"Commissions"** tab

4. **Verify commission entry**:
   - Company: Company X
   - Type: Direct Commission
   - Amount: $22.00 (22% of $100 subscription)
   - Month: [Current month]
   - Status: Pending

**Screenshot**: Partner A commissions tab showing direct commission

---

### Test 3.3: Check Database for Commission Record

**Alternative verification** (if UI doesn't show):
1. Open Railway CLI or database tool

2. Run query:
```sql
SELECT
    id,
    affiliate_partner_id,
    upline_partner_id,
    amount,
    upline_amount,
    created_at
FROM affiliate_events
WHERE affiliate_partner_id = [Partner A ID]
ORDER BY created_at DESC
LIMIT 5;
```

**Expected**:
- `affiliate_partner_id`: Partner A ID
- `upline_partner_id`: NULL (Partner A has no upline yet)
- `amount`: 22.00
- `upline_amount`: NULL

---

## 🧪 Test Suite 4: Partner→Partner Invitation (AC-15 + FIX PATCH #5) - 45 minutes

**⭐ This is the CRITICAL test for FIX PATCH #5**

### Test 4.1: Partner A Invites Partner B

**Steps**:
1. **Logout** from super admin

2. **Login as Partner A**:
   ```
   URL: https://app.facturino.mk/partner/login
   Email: yourmail+partnerA@gmail.com
   Password: [password you set]
   ```
   - Expected: Partner dashboard loads

3. Navigate to **"My Network"** or **"Referrals"** in partner sidebar

4. Click **"Invite Partner"** button
   - Expected: Invitation form modal opens

5. Fill in invitation:
   ```
   Invitee Email: yourmail+partnerB@gmail.com
   Message (optional): "Join our partner network!"
   ```

6. Click **"Send Invitation"**
   - Expected: Success message "Invitation sent to yourmail+partnerB@gmail.com"
   - Expected: Referral link generated
   - Expected: Email sent to Partner B (check inbox)

7. **Copy referral link** (format: `https://app.facturino.mk/partner/signup?ref=ABC123`)

**Screenshot**: Invitation sent confirmation with referral link

---

### Test 4.2: Verify partner_referrals Table Entry

**Database check**:
```sql
SELECT
    id,
    inviter_partner_id,
    invitee_partner_id,
    invitee_email,
    referral_token,
    status,
    invited_at
FROM partner_referrals
WHERE invitee_email = 'yourmail+partnerB@gmail.com'
ORDER BY invited_at DESC
LIMIT 1;
```

**Expected**:
- `inviter_partner_id`: Partner A ID
- `invitee_partner_id`: NULL (not accepted yet)
- `invitee_email`: yourmail+partnerB@gmail.com
- `status`: "pending"
- `referral_token`: [some unique token]

**Screenshot**: Database query result

---

### Test 4.3: Partner B Accepts Invitation

**Steps**:
1. **Logout** from Partner A

2. Open referral link in **incognito window** or different browser:
   ```
   https://app.facturino.mk/partner/signup?ref=[TOKEN]
   ```

3. **Verify referral token detected**:
   - Expected: Page shows "Invited by John Smith (Smith Consulting)"
   - Expected: Form pre-filled with Partner B email

4. Complete Partner B signup:
   ```
   Password: [create password]
   Confirm Password: [repeat]
   Accept Terms: ✅
   ```

5. Click **"Create Account"**
   - Expected: Success message "Account created"
   - Expected: Redirected to Partner B dashboard

6. **Verify upline relationship**:
   - Navigate to Partner B dashboard → "My Upline" section
   - Expected: Shows "Your upline: John Smith (Smith Consulting)"

**Screenshot**: Partner B dashboard showing upline

---

### Test 4.4: Verify partner_referrals Updated

**Database check**:
```sql
SELECT
    id,
    inviter_partner_id,
    invitee_partner_id,
    invitee_email,
    status,
    accepted_at
FROM partner_referrals
WHERE invitee_email = 'yourmail+partnerB@gmail.com'
ORDER BY invited_at DESC
LIMIT 1;
```

**Expected**:
- `inviter_partner_id`: Partner A ID ✅
- `invitee_partner_id`: Partner B ID ✅ (NOW FILLED)
- `status`: "accepted" ✅
- `accepted_at`: [timestamp] ✅

**This is critical for FIX PATCH #5!**

---

### Test 4.5: Partner B Refers Company (Trigger Upline Commission)

**Steps**:
1. Still logged in as **Partner B**

2. Navigate to **"My Companies"** or **"Invite Company"**

3. Click **"Invite Company"** button

4. Fill in:
   ```
   Company Email: yourmail+companyZ@gmail.com
   Company Name: Test Company Z
   ```

5. Click **"Send Invitation"**
   - Expected: Invitation sent

6. **Accept invitation** (as Company Z):
   - Check email inbox
   - Click signup link
   - Complete company registration
   - Link company to Partner B

7. **Create subscription** for Company Z:
   - Plan: Professional ($100/month)
   - Subscribe

**Expected**:
- Company Z subscription: $100/month
- Partner B (direct partner): Receives $22 commission (22%)
- Partner A (upline): Receives $5 commission (5%) ⭐ **FIX PATCH #5 VERIFICATION**

---

### Test 4.6: Verify Upline Commission (FIX PATCH #5 CRITICAL TEST)

**Steps**:
1. **Login as Super Admin**

2. Navigate to **"Partners"**

3. Click on **Partner A** (upline)

4. Click **"Commissions"** tab

5. **Verify TWO commission entries**:

   **Entry 1** (from earlier):
   - Company: Company X
   - Type: Direct Commission
   - Amount: $22.00

   **Entry 2** (NEW - from FIX PATCH #5):
   - Company: Company Z
   - Type: Upline Commission ⭐
   - Amount: $5.00 ⭐
   - Downline Partner: Partner B ⭐

**Screenshot**: Partner A commissions showing both direct and upline commissions

---

### Test 4.7: Database Verification (FIX PATCH #5 PROOF)

**Query**:
```sql
SELECT
    ae.id,
    ae.affiliate_partner_id,
    ae.upline_partner_id,
    ae.amount AS direct_commission,
    ae.upline_amount,
    ae.created_at,
    p1.name AS direct_partner_name,
    p2.name AS upline_partner_name,
    c.name AS company_name
FROM affiliate_events ae
LEFT JOIN partners p1 ON p1.id = ae.affiliate_partner_id
LEFT JOIN partners p2 ON p2.id = ae.upline_partner_id
LEFT JOIN companies c ON c.id = ae.company_id
WHERE ae.upline_partner_id IS NOT NULL
ORDER BY ae.created_at DESC
LIMIT 5;
```

**Expected result**:
```
| id | affiliate_partner_id | upline_partner_id | direct_commission | upline_amount | direct_partner_name | upline_partner_name | company_name  |
|----|---------------------|-------------------|-------------------|---------------|---------------------|---------------------|---------------|
| X  | Partner B ID        | Partner A ID      | 22.00             | 5.00          | Jane Doe            | John Smith          | Company Z     |
```

**✅ THIS PROVES FIX PATCH #5 WORKS!**

The fact that:
- `upline_partner_id` = Partner A ID
- `upline_amount` = 5.00

Confirms that CommissionService correctly:
1. Queried `partner_referrals` table
2. Found Partner A as inviter (upline) of Partner B
3. Calculated 5% upline commission
4. Recorded it in `affiliate_events`

**Screenshot**: Database query showing upline commission

---

## 🧪 Test Suite 5: Company→Partner Invitation (AC-11) - 20 minutes

### Test 5.1: Company X Invites New Partner

**Steps**:
1. **Login as Company X owner**

2. Navigate to **"Settings"** → **"Partners"**

3. Click **"Invite Partner"** button

4. Fill in:
   ```
   Partner Email: yourmail+partnerC@gmail.com
   Permissions:
     ✅ View Reports
     ✅ Manage Invoices
     ⬜ Manage Settings
   Set as Primary: ⬜ (already have Partner A)
   ```

5. Click **"Send Invitation"**
   - Expected: Success message
   - Expected: Email sent to partnerC@gmail.com

6. **Accept invitation** (as Partner C):
   - Check email
   - Click signup link
   - Complete registration

**Verify**: Partner C now linked to Company X (non-primary)

---

## 🧪 Test Suite 6: Entity Reassignment (AC-16) - 20 minutes

### Test 6.1: Reassign Company X Primary Partner

**Steps**:
1. **Login as Super Admin**

2. Navigate to **"Reassignments"** in sidebar
   - Expected: Reassignment management page

3. Click **"Reassign Company"** button

4. Fill in reassignment:
   ```
   Company: Company X
   Current Primary Partner: Partner A (shows automatically)
   New Primary Partner: Partner B
   Reason: Testing AC-16 reassignment functionality
   ```

5. Click **"Reassign"**
   - Expected: Confirmation dialog "This will change the primary partner. Continue?"

6. Click **"Confirm"**
   - Expected: Success message "Company reassigned"
   - Expected: Audit log entry created

**Verify changes**:
1. Go to **Partner A** → Companies tab
   - Expected: Company X no longer shows "Primary" badge
   - Expected: Company X still listed (as non-primary)

2. Go to **Partner B** → Companies tab
   - Expected: Company X now shows "Primary" badge

**Screenshot**: Before/after of reassignment

---

### Test 6.2: Reassign Partner Upline

**Steps**:
1. Navigate to **"Reassignments"**

2. Click **"Reassign Partner Upline"**

3. Fill in:
   ```
   Partner: Partner B
   Current Upline: Partner A
   New Upline: [None / Another Partner]
   Reason: Testing upline reassignment
   ```

4. Click **"Reassign"**
   - Expected: Success message
   - Expected: Future commissions will go to new upline

**Screenshot**: Upline reassignment confirmation

---

## 🧪 Test Suite 7: Network Graph Visualization (AC-17) - 15 minutes

### Test 7.1: View Network Graph

**Steps**:
1. **Login as Super Admin**

2. Navigate to **"Partners"** → **"Network Graph"**
   - Expected: Graph visualization loads
   - URL: `/admin/partners/network`

3. **Verify graph elements**:
   - Nodes (circles) represent partners and companies
   - Edges (lines) represent relationships
   - Partner nodes: Blue/green color
   - Company nodes: Yellow/orange color

4. **Verify Partner A node**:
   - Shows: "John Smith"
   - Connected to: Company X (line/edge)
   - Tooltip on hover: Commission total, # of companies

5. **Verify Partner B node**:
   - Shows: "Jane Doe"
   - Connected to: Partner A (upline relationship - dotted line)
   - Connected to: Company Z

**Screenshot**: Network graph showing partner relationships

---

### Test 7.2: Test Graph Pagination

**Steps**:
1. At bottom of graph, find pagination controls

2. Set **"Nodes per page"**: 10

3. Click **"Next"** if you have > 10 nodes
   - Expected: Graph updates with next 10 nodes

4. Try filter dropdown:
   - Filter: "Partners Only"
   - Expected: Only partner nodes shown (companies hidden)

5. Change to "Companies Only"
   - Expected: Only company nodes shown

**Screenshot**: Graph with different filters applied

---

### Test 7.3: Export Graph Data

**Steps**:
1. Click **"Export Graph"** button
   - Expected: JSON file downloads

2. Open downloaded file

3. **Verify JSON structure**:
```json
{
  "nodes": [
    {
      "id": "P1",
      "type": "partner",
      "name": "John Smith",
      "commission_total": 27.00
    },
    {
      "id": "P2",
      "type": "partner",
      "name": "Jane Doe",
      "commission_total": 22.00
    }
  ],
  "edges": [
    {
      "source": "P1",
      "target": "P2",
      "type": "partner_referral"
    }
  ]
}
```

**Screenshot**: Exported JSON file

---

## 🧪 Test Suite 8: Partner Portal (Company→Company & Partner→Company) - 15 minutes

### Test 8.1: Company→Company Referral (AC-14)

**Steps**:
1. **Login as Company X**

2. Navigate to **"Referrals"** tab

3. Click **"Refer Company"**

4. Fill in:
   ```
   Company Email: yourmail+companyW@gmail.com
   ```

5. Click **"Send Referral"**
   - Expected: Success message
   - Expected: Referral tracked in `company_referrals` table

**Database verification**:
```sql
SELECT * FROM company_referrals
WHERE inviter_company_id = [Company X ID]
ORDER BY created_at DESC;
```

---

### Test 8.2: Partner→Company Invitation (AC-12)

**Steps**:
1. **Login as Partner A**

2. Navigate to **"Invite Company"**

3. Fill in:
   ```
   Company Email: yourmail+companyV@gmail.com
   Company Name: Test Company V
   ```

4. Click **"Send Invitation"**
   - Expected: Invitation sent
   - Expected: Partner A will be primary partner when Company V accepts

**Verify**: Company V accepts → Partner A automatically set as primary

---

## 🧪 Test Suite 9: Dashboard & Reports - 15 minutes

### Test 9.1: Partner Dashboard

**Steps**:
1. **Login as Partner A**

2. **Verify dashboard widgets**:
   - Total Commissions: $27.00 (22 + 5)
   - This Month: [current month commissions]
   - Companies Managed: 1 (Company X)
   - Downline Partners: 1 (Partner B)

3. **Verify charts**:
   - Commission trend chart (line graph)
   - Commission breakdown (pie chart: Direct vs Upline)

**Screenshot**: Partner A dashboard

---

### Test 9.2: Admin Reports

**Steps**:
1. **Login as Super Admin**

2. Navigate to **"Reports"** → **"Partner Performance"**

3. **Verify report shows**:
   - Partner A: $27.00 total commissions (Direct $22 + Upline $5)
   - Partner B: $22.00 total commissions (Direct only)
   - Company X: $100/month subscription
   - Company Z: $100/month subscription

4. Export report as CSV

5. **Verify CSV contains**:
   - All partner commission data
   - Upline/downline relationships
   - Commission types

**Screenshot**: Partner performance report

---

## ✅ Final Verification Checklist

### AC-08: Partner Management ✅
- [x] Create partner
- [x] Edit partner
- [x] Activate/deactivate partner
- [x] View partner details (all tabs)

### AC-09: Partner-Company Linking ✅
- [x] Link partner to company
- [x] Set primary partner
- [x] Prevent multiple primary partners per company
- [x] Configure permissions

### AC-10: Commission Calculations ✅
- [x] Direct commission calculated (22% of subscription)
- [x] Commission recorded in `affiliate_events`
- [x] Commission visible in partner dashboard

### AC-11: Company→Partner Invitation ✅
- [x] Company can invite partner
- [x] Partner accepts invitation
- [x] Permissions configured correctly

### AC-12: Partner→Company Invitation ✅
- [x] Partner can invite company
- [x] Company accepts invitation
- [x] Partner becomes primary automatically

### AC-14: Company→Company Referral ✅
- [x] Company can refer another company
- [x] Referral tracked in database

### AC-15: Partner→Partner Invitation ⭐ ✅
- [x] Partner A invites Partner B
- [x] Invitation creates `partner_referrals` entry (status: pending)
- [x] Partner B accepts (status: accepted, invitee_partner_id filled)
- [x] Upline relationship established

### AC-16: Entity Reassignment ✅
- [x] Reassign company primary partner
- [x] Reassign partner upline
- [x] Audit log created

### AC-17: Network Graph ✅
- [x] Graph displays partners and companies
- [x] Edges show relationships
- [x] Pagination works
- [x] Filters work (partners only, companies only)
- [x] Export to JSON works

### AC-18: Multi-Level Commissions ✅
- [x] Direct commission calculated (Partner B → Company Z)
- [x] Upline commission calculated (Partner A ← Partner B) ⭐ **FIX PATCH #5**

### FIX PATCH #5: Upline Detection ⭐ ✅
- [x] CommissionService queries `partner_referrals` table
- [x] Upline partner identified correctly (Partner A)
- [x] 5% upline commission calculated
- [x] `affiliate_events.upline_partner_id` populated
- [x] `affiliate_events.upline_amount` = 5.00
- [x] Partner A dashboard shows upline commission
- [x] Database verification confirms logic

---

## 🚨 Common Issues & Troubleshooting

### Issue 1: "Partner referrals table not found"
**Fix**: Run migration `2025_11_18_100000_create_partner_referrals_table.php`

### Issue 2: Upline commission = $0
**Symptom**: FIX PATCH #5 not working
**Check**:
1. Verify `partner_referrals` entry exists with status='accepted'
2. Check CommissionService.php contains `partner_referrals` query (line 121-146)
3. Run: `railway run php artisan tinker --execute="echo strpos(file_get_contents(base_path('app/Services/CommissionService.php')), 'partner_referrals') ? 'DEPLOYED' : 'MISSING';"`

### Issue 3: Cannot set primary partner
**Symptom**: "Company already has primary partner"
**Fix**: This is correct behavior - use reassignment feature instead

### Issue 4: Graph not loading
**Check**:
1. Browser console for JavaScript errors
2. API endpoint `/api/v1/referral-network/graph` returns 200
3. Try pagination (reduce nodes per page to 10)

---

## 📊 Success Criteria

**Test is SUCCESSFUL if**:

1. ✅ All partners created without errors
2. ✅ Partner-company links working with primary designation
3. ✅ Direct commissions calculated: $22 (22% of $100)
4. ✅ **Upline commissions calculated: $5 (5% of $100)** ⭐ **FIX PATCH #5**
5. ✅ Database shows `upline_partner_id` and `upline_amount` populated
6. ✅ Partner A dashboard shows BOTH direct ($22) and upline ($5) commissions
7. ✅ Network graph displays correctly
8. ✅ All 4 invitation flows work (AC-11, AC-12, AC-14, AC-15)
9. ✅ Reassignment feature functional

**Test is FAILED if**:
- ❌ Upline commission = $0 (FIX PATCH #5 not working)
- ❌ `affiliate_events.upline_partner_id` is NULL when upline exists
- ❌ Partner A doesn't receive commission when Partner B generates sale
- ❌ Any database constraint errors
- ❌ Any HTTP 500 errors

---

## 📸 Required Screenshots

For documentation, take screenshots of:
1. Partners list (both Partner A and B)
2. Partner detail modal (all tabs)
3. Partner-company link (showing "Primary" badge)
4. Partner A commissions tab (showing direct + upline)
5. Database query result (affiliate_events with upline)
6. Network graph visualization
7. Partner B dashboard showing upline (Partner A)
8. Reassignment confirmation
9. Final totals: Partner A ($27), Partner B ($22)

---

## 📞 Support

**If tests fail**:
1. Check Railway logs: `railway logs | grep -i "commission\|partner"`
2. Check database: Run SQL queries provided above
3. Verify FIX PATCH #5 deployed: Check CommissionService.php line 121-146
4. Review `STAGING_QA_REPORT.md` for known issues

**Report issues** with:
- Screenshot of error
- Database query results
- Railway log excerpt
- Steps to reproduce

---

**Testing Duration**: 2-3 hours
**Recommended**: Test in staging first, then production
**Critical Test**: Test 4.6 (Upline Commission) - This verifies FIX PATCH #5

🎉 **Happy Testing!**

// CLAUDE-CHECKPOINT
