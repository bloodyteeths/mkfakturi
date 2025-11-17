# Partner Dashboard Testing Guide

## Prerequisites

### Step 1: Create Partner User in Railway Database

You need to execute the SQL commands to create a partner user. Here are **two methods**:

#### Method A: Using Railway Dashboard (Recommended)
1. Go to your Railway project dashboard
2. Click on your MySQL database service
3. Go to the **Data** or **Query** tab
4. Copy and paste the SQL from `create_partner_user.sql`
5. Execute the SQL commands

#### Method B: Using MySQL Client
```bash
mysql -h mysql-y5el.railway.internal -u root -p'uJZQdkQyPqITDiCiarqPQNlZzEpJLZxx' railway < create_partner_user.sql
```

### Step 2: Verify User Creation
Run this query to confirm the user was created:
```sql
SELECT u.id, u.name, u.email, u.role, c.name as company_name
FROM users u
LEFT JOIN company_user cu ON u.id = cu.user_id
LEFT JOIN companies c ON cu.company_id = c.id
WHERE u.email = 'partner@demo.mk';
```

---

## Partner Credentials

**Email:** `partner@demo.mk`
**Password:** `Partner2025!`

---

## UI Testing Instructions

### Test 1: Login Flow

1. **Navigate to the application**
   - Go to your Railway deployment URL (e.g., https://your-app.railway.app)

2. **Access login page**
   - Click on "Login" or navigate to `/login`

3. **Enter partner credentials**
   - Email: `partner@demo.mk`
   - Password: `Partner2025!`
   - Click "Login" button

4. **Expected result:**
   - ✅ Successfully logged in
   - ✅ Redirected to partner dashboard (not admin dashboard)
   - ✅ See partner-specific navigation/menu items

5. **What to check:**
   - [ ] User name "Partner Demo" appears in header/profile
   - [ ] Role badge shows "Partner" (if applicable)
   - [ ] Navigation menu shows partner-specific options

---

### Test 2: Partner Dashboard Access

1. **Dashboard overview**
   - After login, you should land on `/partner/dashboard` or similar

2. **Expected elements:**
   - [ ] Commission summary/stats
   - [ ] Referral links or codes
   - [ ] Partner-specific metrics (customers referred, revenue, etc.)
   - [ ] Recent activity/transactions

3. **What NOT to see:**
   - [ ] Admin-only menu items (user management, company settings, etc.)
   - [ ] Super admin features
   - [ ] Full customer database access

---

### Test 3: Commission/Referral Features

1. **Access commission page**
   - Look for "Commissions", "Earnings", or "Referrals" in the menu
   - Navigate to that section

2. **Test commission listing**
   - [ ] Can view commission history
   - [ ] Can see commission amounts
   - [ ] Can filter by date/status
   - [ ] Can export commission data (if feature exists)

3. **Test referral links**
   - [ ] Can generate/view referral links
   - [ ] Can copy referral code
   - [ ] Referral tracking stats are visible

---

### Test 4: Partner Profile Management

1. **Access profile settings**
   - Navigate to user profile or settings
   - URL likely `/partner/profile` or similar

2. **Test editable fields**
   - [ ] Can update name
   - [ ] Can update email (verify validation)
   - [ ] Can change password
   - [ ] Can update contact information
   - [ ] Can add bank details (for commission payouts)

3. **Test restrictions**
   - [ ] Cannot change role
   - [ ] Cannot access other partners' data
   - [ ] Cannot modify company settings

---

### Test 5: Customer/Referral Management

1. **View referred customers**
   - Navigate to customers or referrals section
   - Should show only customers referred by this partner

2. **Test customer list**
   - [ ] Can see customer names
   - [ ] Can see signup dates
   - [ ] Can see subscription status
   - [ ] Can see commission earned per customer

3. **Test filters/search**
   - [ ] Can search customers by name
   - [ ] Can filter by status (active/inactive)
   - [ ] Can filter by date range

---

### Test 6: Reporting & Analytics

1. **Access reports**
   - Look for "Reports", "Analytics", or "Statistics"

2. **Test available reports**
   - [ ] Commission summary report
   - [ ] Referral conversion rates
   - [ ] Monthly earnings charts
   - [ ] Customer acquisition trends

3. **Test export features**
   - [ ] Can export to PDF
   - [ ] Can export to CSV/Excel
   - [ ] Can select date ranges for reports

---

### Test 7: Access Control & Permissions

1. **Try accessing admin routes**
   - Manually navigate to `/admin/*` URLs
   - **Expected:** 403 Forbidden or redirect to partner dashboard

2. **Try accessing other partners' data**
   - Attempt to access `/partner/{other-id}/*`
   - **Expected:** Access denied

3. **Try modifying company settings**
   - Navigate to company/billing settings
   - **Expected:** Read-only or no access

4. **Test API endpoints** (if applicable)
   - Use browser DevTools Network tab
   - Check API calls return only partner-authorized data

---

### Test 8: Notifications & Communications

1. **Check notification center**
   - [ ] Can see commission notifications
   - [ ] Can see new referral notifications
   - [ ] Can mark notifications as read

2. **Test email notifications** (if configured)
   - Trigger a commission event
   - Check if email is sent to partner@demo.mk

---

### Test 9: Mobile Responsiveness

1. **Test on mobile device or browser DevTools**
   - [ ] Dashboard is mobile-friendly
   - [ ] Tables are scrollable/responsive
   - [ ] Charts render correctly
   - [ ] Navigation menu works on mobile

---

### Test 10: Logout & Session Management

1. **Test logout**
   - Click logout button
   - **Expected:** Redirected to login page
   - Session cleared

2. **Test session timeout** (if applicable)
   - Leave browser idle for timeout period
   - Try to access protected page
   - **Expected:** Redirected to login

3. **Test concurrent sessions**
   - Login on two different browsers
   - Logout from one
   - Check if other session is affected (depends on implementation)

---

## Common Issues & Troubleshooting

### Issue: Cannot login
- ✅ Verify user exists in database
- ✅ Check password hash is correct
- ✅ Verify `role` field is set to `partner`
- ✅ Check browser console for JavaScript errors

### Issue: Redirected to admin dashboard
- ✅ Check route middleware in `routes/web.php` or `routes/partner.php`
- ✅ Verify `role` check in login controller
- ✅ Check RedirectIfAuthenticated middleware

### Issue: 404 on partner routes
- ✅ Verify partner routes are registered
- ✅ Check `RouteServiceProvider.php` for partner route prefix
- ✅ Run `php artisan route:list` to see available routes

### Issue: Access denied errors
- ✅ Check authorization policies in `app/Policies/`
- ✅ Verify middleware on partner routes
- ✅ Check user-company relationship in database

---

## Expected File Locations (for debugging)

### Routes
- `routes/partner.php` - Partner-specific routes
- `routes/web.php` - General web routes with partner middleware

### Controllers
- `Modules/Mk/Controllers/Partner/DashboardController.php`
- `Modules/Mk/Controllers/Partner/CommissionController.php`
- `Modules/Mk/Controllers/Partner/ReferralController.php`

### Views/Components
- `resources/js/pages/partner/**/*.vue` - Partner Vue components
- `resources/views/partner/**/*.blade.php` - Partner Blade templates (if using Blade)

### Middleware
- `app/Http/Middleware/CheckPartnerRole.php` - Role verification
- `app/Http/Middleware/RedirectIfAuthenticated.php` - Login redirect logic

---

## Testing Checklist Summary

Copy this checklist and mark items as you test:

- [ ] Login with partner credentials
- [ ] Access partner dashboard
- [ ] View commission data
- [ ] View referral links/codes
- [ ] Update partner profile
- [ ] View referred customers
- [ ] Generate reports
- [ ] Test access control (cannot access admin routes)
- [ ] Test mobile responsiveness
- [ ] Test logout flow
- [ ] Test notification system
- [ ] Export data (CSV/PDF)

---

## Notes

- **Browser Console:** Keep DevTools open to catch JavaScript errors
- **Network Tab:** Monitor API calls to ensure correct endpoints are hit
- **Screenshots:** Take screenshots of any bugs/issues for reporting
- **Performance:** Note any slow loading pages or API calls

---

## Reporting Issues

If you find bugs or unexpected behavior:

1. Note the URL where issue occurs
2. Describe expected vs actual behavior
3. Include browser console errors
4. Include network request details (if API-related)
5. Note user role and permissions involved

---

**Happy Testing! 🧪**
