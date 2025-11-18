# Manual QA Checklist: AC-08 → AC-18
## Partner Management & Commission System

**QA Engineer**: _____________________
**Test Date**: _____ / _____ / _____
**Environment**: [ ] Staging [ ] Production
**Build Version**: _____________________

---

## Test Preparation

- [ ] Test database seeded with sample data
- [ ] Super admin account created
- [ ] Regular user account created
- [ ] Partner account created
- [ ] Company accounts created (minimum 3)
- [ ] Browser DevTools open (Network + Console tabs)
- [ ] Postman/Insomnia ready for API testing

---

## AC-08: Partner Management (CRUD)

### Test Case 1.1: Create New Partner
**Path**: Admin → Partners → Create

- [ ] Click "Create Partner" button
- [ ] Fill all required fields:
  - Name: "Test Partner Agency"
  - Email: "partner@example.com"
  - Company Name: "QA Partner Co"
  - Phone: "+38970123456"
  - Address: "123 Test Street, Skopje"
  - VAT Number: "MK1234567890"
  - Bank Name: "Komercijalna Banka"
  - Bank Account: "300-1234567890-12"
- [ ] Click Save
- [ ] ✅ Partner created successfully
- [ ] ✅ Redirected to partner list
- [ ] ✅ New partner appears in list

### Test Case 1.2: View Partner Details
**Path**: Admin → Partners → [Select Partner]

- [ ] Click on newly created partner
- [ ] ✅ All fields display correctly
- [ ] ✅ Company assignment section visible
- [ ] ✅ KYC status displayed
- [ ] ✅ Active/Inactive toggle visible

### Test Case 1.3: Edit Partner
**Path**: Admin → Partners → [Edit Partner]

- [ ] Click Edit button
- [ ] Change name to "Updated Partner Name"
- [ ] Change phone number
- [ ] Click Save
- [ ] ✅ Changes saved successfully
- [ ] ✅ Updated values display in view

### Test Case 1.4: Delete Partner
**Path**: Admin → Partners → [Delete]

- [ ] Create a test partner specifically for deletion
- [ ] Click Delete button
- [ ] ✅ Confirmation dialog appears
- [ ] Confirm deletion
- [ ] ✅ Partner removed from list
- [ ] ✅ Database record deleted

### Test Case 1.5: Partner Search
**Path**: Admin → Partners → Search

- [ ] Enter partner name in search box
- [ ] ✅ Results filter in real-time
- [ ] Enter partial email
- [ ] ✅ Matching partners displayed
- [ ] Clear search
- [ ] ✅ Full list restored

### Test Case 1.6: Partner Status Filter
**Path**: Admin → Partners → Filters

- [ ] Select "Active" filter
- [ ] ✅ Only active partners shown
- [ ] Select "Inactive" filter
- [ ] ✅ Only inactive partners shown
- [ ] Select "All"
- [ ] ✅ All partners shown

---

## AC-09: Company Assignment

### Test Case 2.1: Assign Company to Partner
**Path**: Admin → Partners → [Partner] → Assign Company

- [ ] Click "Assign Company" button
- [ ] Select company from dropdown
- [ ] Select permissions: "View Reports", "Manage Invoices"
- [ ] Click Assign
- [ ] ✅ Company assigned successfully
- [ ] ✅ Company appears in partner's company list
- [ ] ✅ Permissions displayed correctly

### Test Case 2.2: Update Company Assignment Permissions
**Path**: Admin → Partners → [Partner] → Edit Assignment

- [ ] Click Edit on existing company assignment
- [ ] Add permission: "Manage Customers"
- [ ] Remove permission: "View Reports"
- [ ] Click Save
- [ ] ✅ Permissions updated
- [ ] ✅ Changes reflected immediately
- [ ] Verify in database: `partner_company_links.permissions` JSON updated

### Test Case 2.3: Unassign Company from Partner
**Path**: Admin → Partners → [Partner] → Unassign

- [ ] Click "Unassign" on company assignment
- [ ] ✅ Confirmation dialog appears
- [ ] Confirm unassignment
- [ ] ✅ Company removed from partner's list
- [ ] ✅ Link deactivated (is_active = false in DB)

### Test Case 2.4: Cannot Assign Same Company Twice
**Path**: Admin → Partners → [Partner] → Assign Company

- [ ] Assign Company A to Partner
- [ ] Try to assign Company A again
- [ ] ✅ Error message: "Partner already linked" or company not in dropdown
- [ ] ✅ No duplicate entry created

---

## AC-10: Partner Portal Dashboard

### Test Case 3.1: Partner Login & Dashboard
**Path**: Partner Portal Login

- [ ] Log out from admin
- [ ] Navigate to partner portal (`/console` or designated URL)
- [ ] Login with partner credentials
- [ ] ✅ Dashboard loads successfully
- [ ] ✅ Displays assigned companies count
- [ ] ✅ Displays total commissions earned
- [ ] ✅ Displays recent commission events

### Test Case 3.2: Switch Between Assigned Companies
**Path**: Console → Company Switcher

- [ ] Click company switcher dropdown
- [ ] ✅ All assigned companies listed
- [ ] Select different company
- [ ] ✅ Context switches to selected company
- [ ] ✅ Dashboard data updates for new company

### Test Case 3.3: View Commissions
**Path**: Console → Commissions

- [ ] Navigate to Commissions page
- [ ] ✅ Commission history displayed
- [ ] ✅ Shows commission type (direct/upline/sales_rep)
- [ ] ✅ Shows amounts correctly formatted
- [ ] ✅ Shows event dates
- [ ] ✅ Total earnings calculated correctly

---

## AC-11: Company Invites Partner

### Test Case 4.1: Send Partner Invitation
**Path**: Admin → Settings → Invite Partner

- [ ] Navigate to Settings → Partner Invitations
- [ ] Enter partner email: "newpartner@example.com"
- [ ] Select permissions: "View Reports"
- [ ] Click "Send Invitation"
- [ ] ✅ Invitation sent successfully
- [ ] ✅ Confirmation message displayed

### Test Case 4.2: Partner Accepts Invitation
**Path**: Partner Console → Invitations

- [ ] Log in as invited partner
- [ ] Navigate to Invitations page
- [ ] ✅ Pending invitation displayed
- [ ] ✅ Shows company name and permissions
- [ ] Click "Accept"
- [ ] ✅ Invitation status changes to "Accepted"
- [ ] ✅ Company now appears in partner's assigned companies
- [ ] Verify in DB: `invitation_status = 'accepted'`, `is_active = true`

### Test Case 4.3: Partner Declines Invitation
**Path**: Partner Console → Invitations

- [ ] Send new invitation to partner
- [ ] Log in as partner
- [ ] Navigate to Invitations
- [ ] Click "Decline" on pending invitation
- [ ] ✅ Invitation status changes to "Declined"
- [ ] ✅ Company NOT added to assigned companies
- [ ] Verify in DB: `invitation_status = 'declined'`, `is_active = false`

---

## AC-12: Partner Invites Company

### Test Case 5.1: Generate Affiliate Link
**Path**: Partner Console → Invite Company

- [ ] Log in as partner
- [ ] Navigate to "Invite Company" page
- [ ] Click "Generate Affiliate Link"
- [ ] ✅ Unique link generated: `/signup?ref=XXXXXXXXXX`
- [ ] ✅ QR code image displayed
- [ ] ✅ Copy link button works
- [ ] Verify in DB: `affiliate_links` table has entry with unique `code`

### Test Case 5.2: Affiliate Link is Idempotent
**Path**: Partner Console → Invite Company

- [ ] Generate affiliate link (first time)
- [ ] Note the referral code
- [ ] Generate affiliate link again
- [ ] ✅ Same referral code returned
- [ ] ✅ No duplicate entries in `affiliate_links` table

### Test Case 5.3: Send Email Invitation
**Path**: Partner Console → Invite Company → Email

- [ ] Enter email: "newclient@example.com"
- [ ] Click "Send Email Invitation"
- [ ] ✅ Success message displayed
- [ ] (If email implemented) Check email inbox for invitation
- [ ] ✅ Email contains signup link with affiliate code

### Test Case 5.4: QR Code Download/Display
**Path**: Partner Console → Invite Company

- [ ] Generate affiliate link
- [ ] ✅ QR code image renders
- [ ] Right-click → Save Image
- [ ] ✅ QR code image downloadable
- [ ] Scan QR code with mobile device
- [ ] ✅ Opens signup page with correct ref parameter

---

## AC-13: Permission Editor

### Test Case 6.1: Display All Available Permissions
**Path**: Admin → Partners → Assign Company → Permissions

- [ ] Open permission editor
- [ ] ✅ All permissions listed:
  - VIEW_REPORTS
  - MANAGE_INVOICES
  - MANAGE_CUSTOMERS
  - MANAGE_PAYMENTS
  - MANAGE_EXPENSES
  - FULL_ACCESS
  - (others as per PartnerPermission enum)
- [ ] ✅ Descriptions displayed for each permission

### Test Case 6.2: Select Multiple Permissions
**Path**: Admin → Partners → Permissions

- [ ] Check "View Reports"
- [ ] Check "Manage Invoices"
- [ ] Check "Manage Customers"
- [ ] ✅ All selected permissions highlighted
- [ ] Click Save
- [ ] ✅ All selected permissions saved
- [ ] Verify in DB: `permissions` JSON contains all 3

### Test Case 6.3: FULL_ACCESS Overrides Other Permissions
**Path**: Admin → Partners → Permissions

- [ ] Select several specific permissions
- [ ] Select "FULL_ACCESS"
- [ ] Save assignment
- [ ] Log in as partner
- [ ] ✅ Partner can access all features regardless of other permissions
- [ ] Verify in code: `hasPermission()` returns true for all when FULL_ACCESS set

---

## AC-14: Company Invites Company

### Test Case 7.1: Send Company Referral
**Path**: Admin → Settings → Invite Company

- [ ] Navigate to Company Referral section
- [ ] Enter email: "referredcompany@example.com"
- [ ] Click "Send Referral"
- [ ] ✅ Referral token generated
- [ ] ✅ Signup link displayed: `/signup?company_ref=XXXXX`
- [ ] Verify in DB: `company_referrals` table has entry

### Test Case 7.2: Referral Token is Unique
**Path**: Admin → Settings → Invite Company

- [ ] Send referral to "company1@example.com"
- [ ] Send referral to "company2@example.com"
- [ ] Verify in DB: Both `referral_token` values are different

### Test Case 7.3: View Pending Company Referrals
**Path**: Admin → Settings → Pending Referrals

- [ ] Navigate to Pending Referrals section
- [ ] ✅ All pending company referrals listed
- [ ] ✅ Shows invitee email
- [ ] ✅ Shows invitation date
- [ ] ✅ Status = "pending"

---

## AC-15: Partner Invites Partner

### Test Case 8.1: Send Partner Referral
**Path**: Partner Console → Invite Partner

- [ ] Log in as partner
- [ ] Navigate to "Invite Partner" page
- [ ] Enter email: "downlinepartner@example.com"
- [ ] Click "Send Invitation"
- [ ] ✅ Referral token generated
- [ ] ✅ Signup link displayed: `/partner/signup?ref=XXXXX`
- [ ] ✅ QR code displayed
- [ ] Verify in DB: `partner_referrals` table has entry with `inviter_partner_id`

### Test Case 8.2: Downline Partner Signup
**Path**: Public → Partner Signup with Referral

- [ ] Open signup link: `/partner/signup?ref=XXXXX`
- [ ] Complete partner signup form
- [ ] Submit signup
- [ ] ✅ Partner account created
- [ ] Verify in DB:
  - `partner_referrals.invitee_partner_id` populated with new partner ID
  - `status = 'accepted'`
  - `accepted_at` timestamp set

### Test Case 8.3: Upline Receives Commission from Downline Sales
**Path**: Partner Console → Commissions

- [ ] Downline partner refers a company
- [ ] Company subscribes
- [ ] Log in as upline partner
- [ ] Navigate to Commissions
- [ ] ✅ Upline commission event displayed (5% of subscription)
- [ ] ✅ Commission type = "upline"
- [ ] ✅ Amount calculated correctly

---

## AC-16: Entity Reassignment

### Test Case 9.1: Reassign Company to New Partner
**Path**: Admin → Partners → Reassignment

- [ ] Navigate to partner with assigned company
- [ ] Click "Reassign" button
- [ ] Select "Reassign Company"
- [ ] Select Company: "Test Company A"
- [ ] Current partner auto-populated
- [ ] Select new partner from dropdown
- [ ] Enter reason: "Client requested partner change"
- [ ] Click "Reassign"
- [ ] ✅ Reassignment successful
- [ ] Verify in DB:
  - Old `partner_company_links` entry: `is_active = false`
  - New `partner_company_links` entry: `is_active = true`
  - `entity_reassignments` log entry created

### Test Case 9.2: Reassign Partner Upline
**Path**: Admin → Partners → Reassignment

- [ ] Select partner with existing upline
- [ ] Click "Reassign Upline"
- [ ] Current upline auto-populated
- [ ] Select new upline partner
- [ ] Enter reason: "Original upline inactive"
- [ ] Click "Reassign"
- [ ] ✅ Reassignment successful
- [ ] Verify in DB:
  - Old `partner_referrals` entry: `status = 'reassigned'`
  - New `partner_referrals` entry: `status = 'accepted'`
  - `entity_reassignments` log entry created

### Test Case 9.3: View Reassignment Log
**Path**: Admin → Partners → Reassignment Log

- [ ] Navigate to Reassignment Log page
- [ ] ✅ All reassignments displayed
- [ ] ✅ Shows entity type (company/partner_upline)
- [ ] ✅ Shows from/to partners
- [ ] ✅ Shows reason
- [ ] ✅ Shows date/time
- [ ] ✅ Shows who performed reassignment

### Test Case 9.4: Regular User Cannot Reassign
**Path**: Admin → Partners (as regular user)

- [ ] Log in as regular user (not super admin)
- [ ] Navigate to Partners section
- [ ] ✅ Reassignment buttons not visible OR
- [ ] Try to access reassignment endpoint directly
- [ ] ✅ 403 Forbidden error returned

---

## AC-17: Referral Network Graph

### Test Case 10.1: View Network Graph
**Path**: Admin → Partners → Network Graph

- [ ] Navigate to Network Graph page
- [ ] ✅ Graph renders without errors
- [ ] ✅ Nodes displayed (partners + companies)
- [ ] ✅ Edges displayed (relationships)
- [ ] ✅ Different node colors for partners vs companies
- [ ] ✅ No JavaScript console errors

### Test Case 10.2: Graph Pagination
**Path**: Admin → Partners → Network Graph → Pagination

- [ ] Set limit to 10 nodes
- [ ] ✅ Only 10 nodes displayed
- [ ] ✅ Pagination controls visible
- [ ] Click "Next Page"
- [ ] ✅ Next 10 nodes loaded
- [ ] ✅ Page number updates
- [ ] ✅ Total pages displayed correctly

### Test Case 10.3: Filter by Type (Partners Only)
**Path**: Admin → Partners → Network Graph → Filter

- [ ] Select "Partners Only" filter
- [ ] ✅ Only partner nodes displayed
- [ ] ✅ No company nodes visible
- [ ] ✅ Partner→Partner edges visible
- [ ] ✅ Partner→Company edges hidden

### Test Case 10.4: Filter by Type (Companies Only)
**Path**: Admin → Partners → Network Graph → Filter

- [ ] Select "Companies Only" filter
- [ ] ✅ Only company nodes displayed
- [ ] ✅ No partner nodes visible
- [ ] ✅ Company→Company edges visible

### Test Case 10.5: Include Inactive Partners Toggle
**Path**: Admin → Partners → Network Graph → Filters

- [ ] Default view (inactive hidden)
- [ ] Note number of nodes
- [ ] Toggle "Include Inactive" ON
- [ ] ✅ Additional inactive partner nodes appear
- [ ] ✅ Total node count increases
- [ ] Toggle OFF
- [ ] ✅ Inactive nodes disappear

### Test Case 10.6: Graph Interaction
**Path**: Admin → Partners → Network Graph

- [ ] Hover over partner node
- [ ] ✅ Tooltip displays partner details (name, email, tier)
- [ ] Hover over company node
- [ ] ✅ Tooltip displays company name
- [ ] Hover over edge
- [ ] ✅ Edge type displayed (manages/referred_partner/referred_company)
- [ ] (If zoom implemented) Test zoom in/out
- [ ] (If drag implemented) Test drag nodes

---

## AC-18: Multi-Level Commissions

### Test Case 11.1: Direct Commission (First Year - 22%)
**Path**: Backend → Commission Calculation

**Setup**:
- Company subscribes for 100.00 MKD
- Subscription month = 1 (first year)

- [ ] Trigger subscription event
- [ ] Verify in DB: `affiliate_events` table
  - `partner_id` = direct partner
  - `commission_type` = 'direct'
  - `commission_amount` = 22.00 (22% of 100)
  - `subscription_month` = 1

### Test Case 11.2: Direct Commission (Second Year - 20%)
**Path**: Backend → Commission Calculation

**Setup**:
- Company renews subscription for 100.00 MKD
- Subscription month = 13 (second year)

- [ ] Trigger renewal event
- [ ] Verify in DB: `affiliate_events` table
  - `commission_type` = 'direct'
  - `commission_amount` = 20.00 (20% of 100)
  - `subscription_month` = 13

### Test Case 11.3: Upline Commission (5%)
**Path**: Backend → Commission Calculation

**Setup**:
- Direct partner has upline partner
- Company subscribes for 100.00 MKD

- [ ] Trigger subscription event
- [ ] Verify in DB: TWO `affiliate_events` entries:
  1. Direct partner: 22.00 (direct)
  2. Upline partner: 5.00 (upline)
- [ ] ✅ Both commissions recorded in single transaction

### Test Case 11.4: Sales Rep Commission (5%)
**Path**: Backend → Commission Calculation

**Setup**:
- Company has assigned sales rep partner
- Company subscribes for 100.00 MKD

- [ ] Trigger subscription event
- [ ] Verify in DB: `affiliate_events` entry for sales rep
  - `partner_id` = sales rep
  - `commission_type` = 'sales_rep'
  - `commission_amount` = 5.00 (5% of 100)

### Test Case 11.5: All Three Commissions Combined
**Path**: Backend → Commission Calculation

**Setup**:
- Direct partner with upline
- Company has sales rep
- Subscription = 100.00 MKD

- [ ] Trigger subscription
- [ ] Verify THREE commission events:
  1. Direct: 22.00
  2. Upline: 5.00
  3. Sales Rep: 5.00
- [ ] ✅ Total = 32.00 (32% of 100)
- [ ] ✅ All created in single DB transaction

### Test Case 11.6: Duplicate Prevention
**Path**: Backend → Commission Calculation

**Setup**:
- Subscription already processed
- Attempt to process same subscription again

- [ ] Trigger duplicate subscription event
- [ ] ✅ No new commission events created
- [ ] ✅ Existing commission count unchanged
- [ ] Check logs for duplicate detection message

### Test Case 11.7: Decimal Rounding
**Path**: Backend → Commission Calculation

**Setup**:
- Subscription amount = 33.33 MKD

- [ ] Trigger subscription
- [ ] Verify direct commission = 7.33 (not 7.3326)
- [ ] Verify upline commission = 1.67 (not 1.6665)
- [ ] ✅ All amounts rounded to 2 decimal places

### Test Case 11.8: Inactive Partner Does Not Receive Commission
**Path**: Backend → Commission Calculation

**Setup**:
- Upline partner is inactive (is_active = false)
- Company subscribes

- [ ] Trigger subscription
- [ ] ✅ Direct partner receives commission
- [ ] ✅ Inactive upline does NOT receive commission
- [ ] Verify in DB: Only 1 commission event (direct), not 2

---

## Performance Testing

### Test Case 12.1: Partner List Load Time
**Path**: Admin → Partners

- [ ] Navigate to partner list with 100+ partners
- [ ] Measure load time with DevTools Network tab
- [ ] ✅ Page loads in < 2 seconds
- [ ] ✅ No N+1 query issues in Laravel Debugbar

### Test Case 12.2: Network Graph Performance
**Path**: Admin → Partners → Network Graph

- [ ] Load network graph with 100 nodes
- [ ] Measure render time
- [ ] ✅ Renders in < 3 seconds
- [ ] ✅ No browser freezing or lag

### Test Case 12.3: Permission Caching
**Path**: Check Laravel logs

- [ ] Assign partner to company
- [ ] Perform multiple permission checks in single request
- [ ] ✅ Database query count does NOT increase with each check
- [ ] ✅ Request-scoped cache working

---

## Security Testing

### Test Case 13.1: Super Admin Access Only
**Path**: Various privileged endpoints

- [ ] Log in as regular user
- [ ] Try to access `/partners` route
- [ ] ✅ 403 Forbidden or redirect to unauthorized page
- [ ] Try to access `/reassignments/company-partner` endpoint
- [ ] ✅ 403 Forbidden

### Test Case 13.2: Partner Can Only See Own Data
**Path**: Partner Console

- [ ] Log in as Partner A
- [ ] Try to access Partner B's commission data via API
- [ ] ✅ Access denied or filtered to only Partner A's data

### Test Case 13.3: CSRF Protection
**Path**: All POST/PUT/DELETE endpoints

- [ ] Remove CSRF token from request
- [ ] Submit form
- [ ] ✅ 419 CSRF token mismatch error

---

## Browser Compatibility

- [ ] ✅ Chrome (latest)
- [ ] ✅ Firefox (latest)
- [ ] ✅ Safari (latest)
- [ ] ✅ Edge (latest)
- [ ] ✅ Mobile Safari (iOS)
- [ ] ✅ Mobile Chrome (Android)

---

## Test Summary

**Total Test Cases**: 80+
**Passed**: _____ / _____
**Failed**: _____ / _____
**Blocked**: _____ / _____

**Critical Issues Found**:
1.
2.
3.

**Minor Issues Found**:
1.
2.
3.

**Overall Status**: [ ] PASS [ ] FAIL [ ] CONDITIONAL PASS

---

**QA Sign-Off**

**Name**: _____________________
**Date**: _____ / _____ / _____
**Signature**: _____________________

// CLAUDE-CHECKPOINT
