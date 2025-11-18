# Facturino Companion Mobile App - Development Roadmap

**Project Type:** Lightweight companion app (NOT full feature parity)
**Framework:** Expo SDK 50+ (React Native + TypeScript)
**Timeline:** 4-6 weeks
**Location:** `/facturino-mobile/`

---

## 🎯 Project Scope

### ✅ INCLUDED Features (Companion Only)
- Login (Sanctum bearer token)
- Company switcher
- Dashboard (stats: unpaid, overdue, collected)
- Invoice list/detail/create/send
- Customer list/add
- Bank accounts (read-only view)
- Bank transactions (read-only list)
- Receipt scanner (OCR upload)
- Notifications (push + list)
- Settings (logout, open web app)

### ❌ EXCLUDED Features (Web-Only)
- Subscription management (Paddle/CPAY)
- Payment processing
- Partner dashboard
- Commission tracking
- QES certificate signing
- E-invoice XML workflow
- VAT reports
- PSD2 bank setup
- Reconciliation tools
- IFRS accounting
- Multi-user permissions
- Advanced settings

### 📊 Technical Limits
- **Total Screens:** 12
- **API Endpoints:** 27
- **State Management:** Context API (no Redux)
- **File Size Limit:** 50-120 lines per file

---

## 📅 PHASE 1: PROJECT SETUP

**Status:** ⏳ PENDING
**Duration:** Week 1 (Days 1-2)
**Assigned:** Setup Agent

### Tasks

#### ✅ Task 1.1: Folder Structure
- [ ] Create `/facturino-mobile/` directory
- [ ] Create `src/` subdirectories:
  - `src/api/` - API clients
  - `src/screens/` - Screen components
  - `src/components/` - Reusable components
  - `src/contexts/` - Context providers
  - `src/navigation/` - Navigation config
  - `src/types/` - TypeScript interfaces
  - `src/utils/` - Utilities
  - `assets/` - Images, icons
  - `config/` - App configuration

**Deliverable:** Folder structure created
**Verification:** Directory tree confirmed

---

#### ✅ Task 1.2: Package Configuration
- [ ] Create `package.json` with dependencies:
  - expo@^50.0.0
  - react-native
  - typescript
  - @react-navigation/native
  - @react-navigation/stack
  - @react-navigation/bottom-tabs
  - axios
  - react-hook-form
  - expo-secure-store
  - expo-camera
  - expo-image-picker
  - expo-notifications
- [ ] Create `app.json` (Expo config)
- [ ] Create `tsconfig.json`

**Deliverable:** Configuration files created
**Verification:** `npm install` runs successfully

---

#### ✅ Task 1.3: TypeScript Types
- [ ] Create `src/types/api.ts` with interfaces:
  - User, Company, AuthState
  - Invoice, InvoiceItem, Customer
  - BankAccount, BankTransaction
  - Notification, ReceiptScanResult
  - Currency, Tax, Item

**Deliverable:** Type definitions file
**Verification:** No TypeScript errors

---

### Phase 1 Completion Criteria
- [ ] All folders created
- [ ] Dependencies installed without errors
- [ ] TypeScript compiles successfully
- [ ] No syntax errors

**Audit Report:** `MOBILE_AUDIT_PHASE_1.md` (generated after completion)

---

## 📅 PHASE 2: AUTHENTICATION & FOUNDATION

**Status:** ⏳ PENDING
**Duration:** Week 1 (Days 3-5)
**Assigned:** Foundation Agent

### Tasks

#### ✅ Task 2.1: API Client Setup
- [ ] Create `src/api/client.ts`:
  - Axios instance with base URL
  - Request interceptor (add Bearer token)
  - Response interceptor (handle 401)
  - Company header injection
- [ ] Create `src/api/auth.ts`:
  - `login(email, password, deviceName)`
  - `logout()`
  - `getBootstrap()`

**Deliverable:** API client modules
**Verification:** Mock API call returns expected structure

---

#### ✅ Task 2.2: Auth Context
- [ ] Create `src/contexts/AuthContext.tsx`:
  - State: user, token, selectedCompany, companies
  - Actions: login, logout, switchCompany
  - Token storage (SecureStore)
  - Bootstrap data loading

**Deliverable:** AuthContext provider
**Verification:** Context provides auth state

---

#### ✅ Task 2.3: Storage Utilities
- [ ] Create `src/utils/storage.ts`:
  - `saveToken(token)`
  - `getToken()`
  - `deleteToken()`
  - `saveCompany(company)`
  - `getCompany()`

**Deliverable:** Storage utility functions
**Verification:** Token save/retrieve works

---

#### ✅ Task 2.4: Navigation Setup
- [ ] Create `src/navigation/AppNavigator.tsx`:
  - AuthStack (LoginScreen)
  - MainStack (BottomTabs + Modals)
  - Conditional rendering based on auth state
- [ ] Create bottom tabs structure:
  - Dashboard, Invoices, Customers, More

**Deliverable:** Navigation structure
**Verification:** Navigation renders without errors

---

#### ✅ Task 2.5: Login Screen
- [ ] Create `src/screens/LoginScreen.tsx`:
  - Email input
  - Password input
  - Login button
  - Loading state
  - Error handling
  - Call AuthContext.login()

**Deliverable:** LoginScreen component
**Verification:** Login flow navigates to Dashboard

---

### Phase 2 Completion Criteria
- [ ] User can login with valid credentials
- [ ] Token stored in SecureStore
- [ ] Bootstrap data loaded
- [ ] Navigation switches to MainStack
- [ ] 401 errors trigger logout

**Audit Report:** `MOBILE_AUDIT_PHASE_2.md` (generated after completion)

---

## 📅 PHASE 3: DASHBOARD & INVOICES

**Status:** ⏳ PENDING
**Duration:** Week 2
**Assigned:** Screen Development Agent

### Tasks

#### ✅ Task 3.1: Dashboard API
- [ ] Create `src/api/dashboard.ts`:
  - `getDashboardStats()`

**Deliverable:** Dashboard API module
**Verification:** Returns stats object

---

#### ✅ Task 3.2: Dashboard Screen
- [ ] Create `src/screens/DashboardScreen.tsx`:
  - Company switcher (dropdown)
  - Stat cards: unpaid, overdue, collected
  - Quick action buttons: Create Invoice, Scan Receipt
  - Recent invoices list (5 items)
  - Pull-to-refresh

**Deliverable:** DashboardScreen component
**Verification:** Stats display correctly

---

#### ✅ Task 3.3: Invoice API
- [ ] Create `src/api/invoices.ts`:
  - `getInvoices(filters)`
  - `getInvoice(id)`
  - `createInvoice(payload)`
  - `sendInvoice(id)`
  - `downloadInvoicePDF(id)`

**Deliverable:** Invoice API module
**Verification:** API calls return expected data

---

#### ✅ Task 3.4: Invoice List Screen
- [ ] Create `src/screens/InvoiceListScreen.tsx`:
  - Search bar
  - Status filter tabs
  - Invoice cards (number, customer, amount, status)
  - Pagination (load more)
  - FAB: Create Invoice

**Deliverable:** InvoiceListScreen component
**Verification:** Invoice list loads and filters work

---

#### ✅ Task 3.5: Invoice Detail Screen
- [ ] Create `src/screens/InvoiceDetailScreen.tsx`:
  - Header: number, date, status
  - Customer info
  - Line items table
  - Total breakdown
  - Actions: Send Email, Download PDF

**Deliverable:** InvoiceDetailScreen component
**Verification:** Invoice detail displays all fields

---

#### ✅ Task 3.6: Create Invoice Screen
- [ ] Create `src/screens/CreateInvoiceScreen.tsx`:
  - Customer dropdown
  - Date pickers (invoice date, due date)
  - Line items (add/remove)
  - Tax dropdown
  - Notes textarea
  - Buttons: Save as Draft, Save & Send

**Deliverable:** CreateInvoiceScreen component
**Verification:** Invoice creation succeeds

---

### Phase 3 Completion Criteria
- [ ] Dashboard shows correct stats
- [ ] Invoice list loads with pagination
- [ ] Invoice detail shows all info
- [ ] Create invoice form validates
- [ ] Send invoice triggers email
- [ ] PDF download works

**Audit Report:** `MOBILE_AUDIT_PHASE_3.md` (generated after completion)

---

## 📅 PHASE 4: CUSTOMERS & RECEIPTS

**Status:** ⏳ PENDING
**Duration:** Week 3
**Assigned:** Screen Development Agent

### Tasks

#### ✅ Task 4.1: Customer API
- [ ] Create `src/api/customers.ts`:
  - `getCustomers(search)`
  - `getCustomer(id)`
  - `createCustomer(payload)`

**Deliverable:** Customer API module
**Verification:** Customer CRUD works

---

#### ✅ Task 4.2: Customer List Screen
- [ ] Create `src/screens/CustomerListScreen.tsx`:
  - Search bar
  - Customer cards (name, email, phone)
  - FAB: Add Customer

**Deliverable:** CustomerListScreen component
**Verification:** Customer list loads

---

#### ✅ Task 4.3: Add Customer Screen
- [ ] Create `src/screens/AddCustomerScreen.tsx`:
  - Form: name, email, phone, address, city
  - Save button

**Deliverable:** AddCustomerScreen component
**Verification:** Customer creation succeeds

---

#### ✅ Task 4.4: Receipt API
- [ ] Create `src/api/receipts.ts`:
  - `scanReceipt(imageUri)`
  - `createExpense(data)`

**Deliverable:** Receipt API module
**Verification:** OCR endpoint called successfully

---

#### ✅ Task 4.5: Receipt Scan Screen
- [ ] Create `src/screens/ReceiptScanScreen.tsx`:
  - Camera view / image picker
  - Take Photo / Choose Gallery buttons
  - OCR result display
  - Create Expense button

**Deliverable:** ReceiptScanScreen component
**Verification:** Receipt upload and OCR works

---

### Phase 4 Completion Criteria
- [ ] Customer list displays
- [ ] Add customer form validates
- [ ] Receipt scanner captures image
- [ ] OCR extracts data correctly
- [ ] Expense created from receipt

**Audit Report:** `MOBILE_AUDIT_PHASE_4.md` (generated after completion)

---

## 📅 PHASE 5: BANKING & NOTIFICATIONS

**Status:** ⏳ PENDING
**Duration:** Week 4
**Assigned:** Integration Agent

### Tasks

#### ✅ Task 5.1: Banking API
- [ ] Create `src/api/banking.ts`:
  - `getBankAccounts()`
  - `getBankTransactions(accountId, filters)`

**Deliverable:** Banking API module
**Verification:** Bank data loads (read-only)

---

#### ✅ Task 5.2: Bank Accounts Screen
- [ ] Create `src/screens/BankAccountsScreen.tsx`:
  - Bank account cards (name, balance, last sync)
  - View Transactions button

**Deliverable:** BankAccountsScreen component
**Verification:** Bank accounts display

---

#### ✅ Task 5.3: Bank Transactions Screen
- [ ] Create `src/screens/BankTransactionsScreen.tsx`:
  - Date range filter
  - Transaction rows (date, description, amount)
  - Read-only (no reconciliation)

**Deliverable:** BankTransactionsScreen component
**Verification:** Transactions display correctly

---

#### ✅ Task 5.4: Notifications API
- [ ] Create `src/api/notifications.ts`:
  - `getNotifications()`
  - `registerDevice(fcmToken)`
  - `markAsRead(id)`

**Deliverable:** Notifications API module
**Verification:** Notification endpoints work

---

#### ✅ Task 5.5: Push Notification Handler
- [ ] Create `src/utils/notifications.ts`:
  - Register for push notifications
  - Handle foreground notifications
  - Handle notification tap

**Deliverable:** Notification handler
**Verification:** Push notifications received

---

#### ✅ Task 5.6: Notifications Screen
- [ ] Create `src/screens/NotificationsScreen.tsx`:
  - Notification list (icon, title, message, timestamp)
  - Read/unread indicators
  - Mark all as read button

**Deliverable:** NotificationsScreen component
**Verification:** Notifications display

---

### Phase 5 Completion Criteria
- [ ] Bank accounts load (read-only)
- [ ] Bank transactions display
- [ ] Push notifications register successfully
- [ ] Notification list shows all items
- [ ] Notification tap opens relevant screen

**Audit Report:** `MOBILE_AUDIT_PHASE_5.md` (generated after completion)

---

## 📅 PHASE 6: SETTINGS & POLISH

**Status:** ⏳ PENDING
**Duration:** Week 5
**Assigned:** Polish Agent

### Tasks

#### ✅ Task 6.1: Settings Screen
- [ ] Create `src/screens/SettingsScreen.tsx`:
  - User info display
  - Selected company display
  - Switch Company button
  - Open Facturino Web button (deep link)
  - Logout button

**Deliverable:** SettingsScreen component
**Verification:** All actions work

---

#### ✅ Task 6.2: Deep Link Configuration
- [ ] Configure deep linking in `app.json`
- [ ] Add URL scheme: `facturino://`
- [ ] Test web app redirect

**Deliverable:** Deep link config
**Verification:** Open web app works

---

#### ✅ Task 6.3: UI Polish
- [ ] Add loading spinners
- [ ] Add error messages
- [ ] Add empty states
- [ ] Consistent color scheme
- [ ] Typography styles

**Deliverable:** Polished UI
**Verification:** App looks professional

---

#### ✅ Task 6.4: Format Utilities
- [ ] Create `src/utils/formatters.ts`:
  - `formatCurrency(amount, currency)`
  - `formatDate(date)`
  - `formatStatus(status)`

**Deliverable:** Formatter utilities
**Verification:** All formats display correctly

---

### Phase 6 Completion Criteria
- [ ] Settings screen functional
- [ ] Deep link opens web app
- [ ] UI is polished and consistent
- [ ] All formatters work
- [ ] No visual bugs

**Audit Report:** `MOBILE_AUDIT_PHASE_6.md` (generated after completion)

---

## 📅 PHASE 7: BUILD & DEPLOYMENT

**Status:** ⏳ PENDING
**Duration:** Week 6
**Assigned:** DevOps Agent

### Tasks

#### ✅ Task 7.1: EAS Build Configuration
- [ ] Create `eas.json`:
  - Development profile
  - Preview profile
  - Production profile
- [ ] Configure bundle IDs:
  - iOS: `mk.facturino.companion`
  - Android: `mk.facturino.companion`

**Deliverable:** EAS config file
**Verification:** `eas build` runs successfully

---

#### ✅ Task 7.2: App Store Assets
- [ ] Create app icon (1024x1024)
- [ ] Create splash screen
- [ ] Prepare screenshots (6 per platform)
- [ ] Write app description
- [ ] Privacy policy URL

**Deliverable:** Store assets
**Verification:** Assets meet store requirements

---

#### ✅ Task 7.3: Build & Test
- [ ] Generate development build
- [ ] Test on iOS device
- [ ] Test on Android device
- [ ] Fix critical bugs
- [ ] Generate preview build

**Deliverable:** Preview builds
**Verification:** App runs on physical devices

---

#### ✅ Task 7.4: Store Submission
- [ ] Submit to TestFlight (iOS)
- [ ] Submit to Internal Testing (Android)
- [ ] Gather beta feedback
- [ ] Fix issues
- [ ] Submit to production

**Deliverable:** App published
**Verification:** App available on stores

---

### Phase 7 Completion Criteria
- [ ] EAS build succeeds
- [ ] App runs on iOS/Android
- [ ] No critical bugs
- [ ] Beta testers approve
- [ ] App submitted to stores

**Audit Report:** `MOBILE_AUDIT_PHASE_7.md` (generated after completion)

---

## 📅 PHASE 8: TESTING & DOCUMENTATION

**Status:** ⏳ PENDING
**Duration:** Ongoing
**Assigned:** QA Agent

### Test Checklist

#### Functional Tests
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (error shown)
- [ ] Company switcher changes context
- [ ] Dashboard stats load correctly
- [ ] Invoice list loads with pagination
- [ ] Invoice filters work (status, search)
- [ ] Invoice detail shows all fields
- [ ] Create invoice with multiple line items
- [ ] Send invoice by email
- [ ] Download invoice PDF
- [ ] Customer list loads
- [ ] Add customer validates form
- [ ] Receipt scanner captures image
- [ ] OCR extracts data
- [ ] Bank accounts display (read-only)
- [ ] Bank transactions load
- [ ] Notifications display
- [ ] Push notification received
- [ ] Settings screen shows user info
- [ ] Open web app deep link works
- [ ] Logout clears token

#### Security Tests
- [ ] Token stored in SecureStore (encrypted)
- [ ] 401 response triggers logout
- [ ] API calls use HTTPS only
- [ ] No sensitive data in logs
- [ ] Camera permission requested
- [ ] Notification permission requested

#### Performance Tests
- [ ] App starts in <3 seconds
- [ ] Invoice list scrolls smoothly
- [ ] Image upload completes in <10s
- [ ] No memory leaks
- [ ] Battery usage acceptable

---

### Phase 8 Completion Criteria
- [ ] All functional tests pass
- [ ] All security checks pass
- [ ] Performance acceptable
- [ ] Documentation complete

**Audit Report:** `MOBILE_AUDIT_PHASE_8.md` (generated after completion)

---

## 🏁 FINAL DELIVERABLES

### Code Repository
- `/facturino-mobile/` - Complete Expo project
- All source files in `src/`
- Configuration files (package.json, app.json, eas.json)
- README.md with setup instructions

### Documentation
- MOBILE_ROADMAP.md (this file)
- 8 Audit Reports (one per phase)
- API Integration Guide
- Build & Deployment Guide

### Builds
- iOS build (TestFlight / App Store)
- Android build (Internal Testing / Play Store)

### Verification
- [ ] All 12 screens functional
- [ ] All 27 API endpoints integrated
- [ ] No forbidden features included
- [ ] Context API (no Redux)
- [ ] File sizes <120 lines
- [ ] TypeScript compiles without errors
- [ ] Tests pass
- [ ] Builds succeed

---

## 📊 SUCCESS METRICS

### Scope Compliance
- ✅ Only 12 screens (no extras)
- ✅ Only 27 API endpoints (no subscriptions/payments)
- ✅ No partner features
- ✅ No QES/e-invoice
- ✅ No IFRS/accounting

### Code Quality
- ✅ TypeScript strict mode
- ✅ No files >120 lines
- ✅ Context API only
- ✅ Consistent formatting

### Timeline
- ✅ Completed in 4-6 weeks
- ✅ All phases audited

### Functionality
- ✅ App runs on iOS/Android
- ✅ All core features work
- ✅ No critical bugs

---

## 🔄 AUDIT PROTOCOL

After each phase completion:

1. **Developer Agent** marks tasks complete
2. **QA Agent** verifies completion criteria
3. **Documentation Agent** generates audit report:
   - `MOBILE_AUDIT_PHASE_X.md`
   - Contains:
     - Phase summary
     - Completed tasks list
     - Verification results
     - Issues encountered
     - Lessons learned
     - Next phase recommendations

4. **Project Manager** reviews and approves
5. Move to next phase

---

## 📝 NOTES FOR FUTURE LLM

### Context
This is a **companion mobile app**, not a full InvoiceShelf fork. The web app (Facturino) contains all advanced features. Mobile is for quick access only.

### Forbidden Features
Never implement: subscriptions, payments, partner dashboards, commissions, QES signing, e-invoice XML, IFRS reports, VAT reports, PSD2 setup, reconciliation, or any enterprise features.

### Architecture
- Context API (not Redux)
- Small files (<120 lines)
- 27 API endpoints maximum
- Expo managed workflow

### Audit Reports
Always check phase audit reports before modifying. Each phase has a completion report with verification results.

### Emergency Contacts
If scope creep detected, refer to this roadmap's "EXCLUDED Features" section.

---

**Roadmap Created:** 2025-11-19
**Last Updated:** 2025-11-19
**Version:** 1.0
**Status:** READY TO BEGIN
