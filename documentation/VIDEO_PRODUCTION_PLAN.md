# Facturino Video Tutorial Production Plan
## Complete Video Tutorial Strategy for DOC-VID-01

**Project:** Facturino Video Tutorial Series
**Date Created:** 2025-11-17
**Status:** Production Ready
**Version:** 1.0
**Ticket:** DOC-VID-01 (MEDIUM Priority)

---

## EXECUTIVE SUMMARY

This comprehensive production plan outlines the creation of video tutorials for Facturino, a Macedonian-localized accounting platform. The plan includes 3 required critical tutorials plus 5 recommended additional tutorials, complete production workflows, tool recommendations, and success metrics.

**Key Deliverables:**
- 3 Critical Video Tutorials (Migration, E-Faktura, IFRS Accounting)
- Production Guide with tool recommendations
- Video specifications and quality standards
- Hosting and distribution strategy
- Success metrics and timeline

---

## TABLE OF CONTENTS

1. [Prioritized Tutorial List](#prioritized-tutorial-list)
2. [Tutorial 1: Migration Wizard (COMPLETE)](#tutorial-1-migration-wizard)
3. [Tutorial 2: E-Faktura Submission](#tutorial-2-e-faktura-submission)
4. [Tutorial 3: IFRS Accounting Setup](#tutorial-3-ifrs-accounting-setup)
5. [Additional Recommended Tutorials](#additional-recommended-tutorials)
6. [Production Guide](#production-guide)
7. [Video Specifications](#video-specifications)
8. [Hosting & Distribution](#hosting--distribution)
9. [Success Metrics](#success-metrics)
10. [Production Timeline](#production-timeline)

---

## PRIORITIZED TUTORIAL LIST

### Critical Tutorials (Required for DOC-VID-01)

| Priority | Tutorial | Duration | Complexity | Status | Audience |
|----------|----------|----------|------------|--------|----------|
| 1 | Migration Wizard | 11 min | Medium | READY | New users migrating from Onivo/Megasoft |
| 2 | E-Faktura Submission Workflow | 8-10 min | High | SCRIPT NEEDED | All Macedonian businesses |
| 3 | IFRS Accounting Setup | 7-9 min | High | SCRIPT NEEDED | Professional accountants |

### Recommended Additional Tutorials (Phase 2)

| Priority | Tutorial | Duration | Complexity | Target Audience |
|----------|----------|----------|------------|-----------------|
| 4 | Creating Your First Invoice | 5-7 min | Low | First-time users |
| 5 | Bank Feed Integration (PSD2) | 6-8 min | Medium | Advanced users |
| 6 | Paddle Subscription Setup | 5-6 min | Low | Business owners |
| 7 | Multi-Company Accountant Dashboard | 8-10 min | Medium | Professional accountants |
| 8 | VAT Reporting & Compliance | 7-9 min | Medium | Macedonian businesses |

---

## TUTORIAL 1: MIGRATION WIZARD

### Status: PRODUCTION READY ✅

**Existing Documentation:**
- Full script: `/Users/tamsar/Downloads/mkaccounting/documentation/VIDEO_TUTORIAL_SCRIPT.md`
- Storyboard: `/Users/tamsar/Downloads/mkaccounting/documentation/VIDEO_STORYBOARD.md`
- Quick reference: Available

**Key Details:**
- Duration: 11 minutes
- Word count: ~2,850 words
- Scenes: 10 major sections
- Frames: 48 detailed storyboard frames
- Example data: Complete CSV templates included

**Production Status:**
- ✅ Script complete and reviewed
- ✅ Storyboard with 48 frames
- ✅ Example CSV data prepared
- ✅ Screen flow outlined
- ✅ Visual callouts specified
- ✅ Audio timing planned

**Next Steps:**
1. Record screen capture following storyboard
2. Record voiceover using script
3. Edit and add visual callouts
4. Final review and publish

**Estimated Production Time:** 11-19 hours total

---

## TUTORIAL 2: E-FAKTURA SUBMISSION

### Status: SCRIPT NEEDED

**Target Audience:** Macedonian businesses required to submit electronic invoices

**Tutorial Overview:**
This tutorial demonstrates the complete workflow for submitting QES-signed XML invoices to the Macedonian tax authority, a critical feature for legal compliance.

### Detailed Script

**Duration:** 8-10 minutes

#### Section 1: Introduction (30 seconds)

**Visual:** Facturino dashboard with E-Faktura menu highlighted

**Voiceover:**
"Welcome to Facturino! In this tutorial, you'll learn how to submit electronic invoices to the Macedonian tax authority using our E-Faktura feature. This is a legal requirement for all Macedonian businesses with annual revenue over 3 million denars. By the end of this video, you'll know how to generate UBL XML invoices, sign them with your QES certificate, and submit them to the e-Faktura portal."

**Callouts:**
- Highlight E-Faktura menu item
- Badge showing "Legal Compliance Required"

#### Section 2: Prerequisites (1 minute)

**Visual:** Checklist with document examples

**Voiceover:**
"Before we begin, you'll need three things. First, a valid QES (Qualified Electronic Signature) certificate from a Macedonian certification authority like Makedonski Telekom or KIBs. Second, your tax identification number - EDB number - registered with the UJP (Public Revenue Office). Third, at least one completed invoice in Facturino that you want to submit electronically.

Your QES certificate should be in PFX or P12 format with the password provided by your certification authority. If you don't have a QES certificate yet, visit kibs.com.mk or telekom.mk to apply."

**Callouts:**
- ✓ QES Certificate (.pfx or .p12 file)
- ✓ EDB Number (tax ID)
- ✓ Completed invoice ready to submit
- Link to certification authorities

#### Section 3: Upload QES Certificate (1.5 minutes)

**Visual:** Settings > E-Faktura Configuration

**Voiceover:**
"Let's start by configuring your QES certificate. Navigate to Settings, then E-Faktura Configuration. You'll see the QES Certificate section. Click 'Upload Certificate' and select your PFX or P12 file.

Enter your certificate password - this is the password provided by your certification authority, not your Facturino password. The system will verify the certificate and extract your company information automatically.

You can see the certificate details: issuer, subject, and expiration date. Make sure the expiration date is in the future - expired certificates won't work. Your certificate is now stored securely and ready to use. Click 'Save Settings'."

**Callouts:**
- Certificate upload dialog
- Password field (masked for security)
- Certificate validation success message
- Expiration date warning if < 30 days

**Screen Flow:**
1. Settings menu
2. E-Faktura Configuration tab
3. Upload Certificate button
4. File picker dialog
5. Password entry
6. Validation progress
7. Certificate details display
8. Save confirmation

#### Section 4: Configure UJP Settings (1 minute)

**Visual:** UJP Integration Settings section

**Voiceover:**
"Now let's configure your connection to the UJP e-Faktura portal. You'll need your EDB number - that's your tax identification number. Enter it in the EDB field. The format is 13 digits without spaces or dashes.

Next, select your e-Faktura environment. For testing, use the Test environment. For real submissions, use Production. I recommend starting with Test to verify everything works correctly.

Enter your UJP portal credentials - these are the same username and password you use to log into e-faktura.ujp.gov.mk. Click 'Test Connection' to verify everything is configured correctly. You should see a green success message. If you see an error, double-check your EDB number and credentials."

**Callouts:**
- EDB number format: 13 digits
- Environment selector: Test vs Production
- Test Connection button
- Success/error messages

#### Section 5: Generate UBL XML from Invoice (1.5 minutes)

**Visual:** Invoice detail page

**Voiceover:**
"Let's submit an invoice. Navigate to your Invoices page and open any invoice you want to submit. For this example, we'll use invoice INV-2025-001 to Македонска Трговска ДООЕЛ.

You'll see the E-Faktura status badge showing 'Not Submitted' in gray. Click the 'Export to E-Faktura' button. Facturino will now generate the UBL XML format required by the Macedonian tax authority.

Watch as the system validates the invoice data. It checks for required fields like customer VAT number, correct tax rates - 18%, 5%, or 0% - and proper item descriptions. All validation checks pass. You can see the UBL XML preview in the modal - this is the standardized format defined by OASIS for electronic invoicing.

Notice that all Cyrillic text is preserved correctly. The invoice total shows in Macedonian denars with proper decimal formatting. The tax breakdown is displayed according to Macedonian requirements."

**Callouts:**
- E-Faktura status badge
- Export button location
- Validation checklist with checkmarks
- XML preview window
- Cyrillic text preservation indicator

**Screen Flow:**
1. Invoices list
2. Click invoice INV-2025-001
3. Invoice detail page
4. Click "Export to E-Faktura"
5. Validation progress
6. XML preview modal
7. Tax breakdown display

#### Section 6: Sign XML with QES (1 minute)

**Visual:** XML signing dialog

**Voiceover:**
"Now we'll sign the UBL XML with your QES certificate. Click 'Sign with QES'. The system uses your uploaded certificate to create a cryptographic signature. This signature proves that your company authorized this invoice and ensures the data hasn't been tampered with.

The signing process takes a few seconds. You can see the progress indicator. Success! The XML is now signed. You can download the signed XML file if you want to keep a local copy. The signature is compliant with XMLDSig standards required by Macedonian law."

**Callouts:**
- Sign with QES button
- Signing progress animation
- Signature verification checkmark
- Download signed XML option
- XMLDSig compliance badge

#### Section 7: Submit to UJP Portal (1.5 minutes)

**Visual:** Submission dialog and progress

**Voiceover:**
"We're ready to submit to the UJP e-Faktura portal. Click 'Submit to UJP'. Facturino will now upload the signed XML to the tax authority's system.

The submission is in progress. You can see the status: connecting to UJP portal, authenticating with your credentials, uploading XML, waiting for response.

Excellent! The submission was successful. The UJP has assigned a unique reference number: EF-MK-2025-00001234. This is your proof of submission. Write this down or save the confirmation email.

The invoice status has changed to 'E-Faktura Submitted' with a green badge. You can see the submission timestamp and reference number. The system automatically sends a confirmation email to your registered email address with the submission details and a copy of the signed XML."

**Callouts:**
- Submit to UJP button
- Progress stages with checkmarks
- UJP reference number (highlighted)
- Status badge change animation
- Email confirmation sent notification

**Screen Flow:**
1. Submit confirmation dialog
2. Progress stages:
   - Connecting...
   - Authenticating...
   - Uploading XML...
   - Processing...
   - Complete!
3. Success screen with reference number
4. Invoice status updated
5. Email notification

#### Section 8: Verify Submission in UJP Portal (1 minute)

**Visual:** UJP portal (external website)

**Voiceover:**
"Let's verify the submission in the UJP portal. Open a new browser tab and go to e-faktura.ujp.gov.mk. Log in with your UJP credentials.

Navigate to 'Submitted Invoices'. You should see your invoice INV-2025-001 at the top of the list. The status shows 'Received' with today's date. Click on the invoice to view details. The UJP system shows all the invoice information, including the customer, items, tax breakdown, and total amount. Everything matches what you submitted from Facturino.

The reference number matches: EF-MK-2025-00001234. The submission is confirmed and legally valid. You can download the acknowledgment receipt from the UJP portal if needed for your records."

**Callouts:**
- UJP portal URL: e-faktura.ujp.gov.mk
- Invoice in submitted list
- Status indicator: Received
- Reference number match confirmation
- Download receipt button

#### Section 9: Troubleshooting Common Issues (1.5 minutes)

**Visual:** Split screen with errors and solutions

**Voiceover:**
"Let's cover common issues you might encounter.

**Issue 1: Certificate Error**
If you see 'Invalid certificate' or 'Certificate expired', check your QES certificate expiration date. Certificates are valid for 1-3 years depending on your provider. You'll need to renew with your certification authority.

**Issue 2: Validation Failures**
If the UBL validation fails, check that your customer has a valid Macedonian VAT number starting with MK and 13 digits. Also verify your tax rates are set to the standard 18%, reduced 5%, or zero-rated 0%.

**Issue 3: Connection Timeout**
If submission times out, the UJP portal may be experiencing high traffic, especially near monthly deadlines. Try again in a few minutes. You can also check the UJP system status at status.ujp.gov.mk.

**Issue 4: Wrong Environment**
Make sure you're using the correct environment. Test submissions go to test.e-faktura.ujp.gov.mk. Production submissions go to e-faktura.ujp.gov.mk. You can't mix them - a test submission won't count as official.

**Issue 5: Duplicate Submission**
If you try to submit the same invoice twice, you'll get a duplicate error. Check your submission history before resubmitting. If you need to correct an invoice, you must submit a credit note canceling the original, then submit a new corrected invoice."

**Callouts:**
- Error examples with screenshots
- Solution steps for each
- Links to help resources
- UJP status page

#### Section 10: Conclusion (30 seconds)

**Visual:** Dashboard with submitted invoice

**Voiceover:**
"Congratulations! You've successfully submitted your first e-Faktura invoice. Your invoice is now legally compliant with Macedonian tax law and registered with the UJP.

Remember, e-Faktura submission is required within 5 days of invoice date for B2B transactions. Facturino will remind you of upcoming deadlines. You can submit multiple invoices at once using bulk submission - we'll cover that in an advanced tutorial.

For questions, visit our help documentation at help.facturino.mk or contact support. Happy invoicing!"

**Callouts:**
- Success badge on dashboard
- Deadline reminder: 5 days
- Bulk submission teaser
- Help resources

---

### Screen Flow Summary for E-Faktura Tutorial

1. Introduction (Dashboard view)
2. Prerequisites checklist
3. Settings > E-Faktura Configuration
4. Upload QES certificate
5. Configure UJP settings
6. Invoices list
7. Invoice detail page
8. Export to E-Faktura
9. UBL XML validation
10. Sign with QES
11. Submit to UJP
12. Submission progress
13. Success confirmation
14. UJP portal verification
15. Troubleshooting scenarios
16. Conclusion

---

### Required Test Data for E-Faktura Tutorial

**QES Certificate:**
- Test certificate file: `test_qes_certificate.pfx`
- Password: `TestPassword123`
- Valid from: 2025-01-01
- Valid until: 2027-12-31

**Company Data:**
- EDB: 4080012562345
- Company: Тест Компанија ДООЕЛ
- Address: ул. Тестна 123, Скопје 1000

**Customer Data:**
- Name: Македонска Трговска ДООЕЛ
- VAT: MK4080013673456
- Address: ул. Македонија 45, Скопје 1000

**Invoice Data:**
- Invoice Number: INV-2025-001
- Date: 2025-01-15
- Due Date: 2025-02-15
- Items: 2 services
- Subtotal: 10,000.00 MKD
- Tax (18%): 1,800.00 MKD
- Total: 11,800.00 MKD

---

### Key Talking Points for E-Faktura Tutorial

1. **Legal Compliance:** E-Faktura is mandatory for businesses > 3M MKD revenue
2. **QES Certificate:** Required for legal validity, obtain from certified authorities
3. **UBL Standard:** Universal format ensures interoperability
4. **Macedonian Localization:** Full Cyrillic support, MKD currency, local tax rates
5. **5-Day Deadline:** Submit within 5 days of invoice date for B2B
6. **Submission Proof:** UJP reference number is legal proof of submission
7. **Security:** XMLDSig cryptographic signature prevents tampering
8. **Integration:** Seamless workflow from invoice creation to submission

---

### Production Notes for E-Faktura Tutorial

**Critical Requirements:**
- Must show actual UJP portal (can use test environment)
- Certificate handling must emphasize security
- Clear distinction between Test and Production environments
- Show real XML structure (use syntax highlighting)
- Demonstrate Cyrillic text preservation

**Screen Recording Considerations:**
- Mask actual certificate passwords
- Use test EDB numbers (not real business data)
- Blur sensitive information in UJP portal
- Show realistic timing (submission takes 5-10 seconds)
- Include loading/progress states

**Visual Callouts Priority:**
- Certificate expiration warnings
- Environment selector (Test vs Prod)
- Validation checkmarks
- UJP reference number
- Status badge changes

---

## TUTORIAL 3: IFRS ACCOUNTING SETUP

### Status: SCRIPT NEEDED

**Target Audience:** Professional accountants managing client books

**Tutorial Overview:**
This tutorial demonstrates setting up double-entry IFRS accounting using the eloquent-ifrs package, enabling professional accountants to maintain compliant financial records.

### Detailed Script

**Duration:** 7-9 minutes

#### Section 1: Introduction (30 seconds)

**Visual:** Facturino dashboard with Accounting module highlighted

**Voiceover:**
"Welcome to Facturino! In this tutorial, you'll learn how to set up and use the IFRS-compliant accounting module. This feature is designed for professional accountants who need to maintain double-entry bookkeeping and generate financial statements that comply with International Financial Reporting Standards.

By the end of this video, you'll know how to configure your chart of accounts, set up automatic journal entries for invoices and payments, and generate balance sheets and income statements."

**Callouts:**
- Highlight Accounting menu
- IFRS compliance badge
- "For Professional Accountants" indicator

#### Section 2: Understanding IFRS Integration (1 minute)

**Visual:** Architecture diagram showing invoice → journal entry flow

**Voiceover:**
"Facturino uses the eloquent-ifrs package to provide full double-entry bookkeeping. Every financial transaction - invoices, payments, expenses - automatically creates corresponding journal entries.

Here's how it works: When you create an invoice, the system debits Accounts Receivable and credits Revenue. When a payment is received, it debits Cash or Bank and credits Accounts Receivable. All entries follow IFRS standards with proper account classification.

The system maintains the accounting equation: Assets = Liabilities + Equity. Every transaction is balanced - debits always equal credits. This ensures your financial statements are accurate and audit-ready."

**Callouts:**
- Flow diagram: Invoice → Journal Entry → Financial Statement
- Accounting equation display
- Example journal entry with debits/credits
- IFRS compliance indicators

#### Section 3: Chart of Accounts Setup (1.5 minutes)

**Visual:** Accounting > Chart of Accounts

**Voiceover:**
"Let's set up your Chart of Accounts. Navigate to Accounting, then Chart of Accounts. Facturino comes with a standard Macedonian chart of accounts pre-configured, but you can customize it for your specific needs.

You'll see five main account categories: Assets, Liabilities, Equity, Revenue, and Expenses. Click on Assets to expand. You'll see subcategories like Current Assets, Fixed Assets, and Bank Accounts.

Let's add a new bank account. Click 'Add Account', enter the account code - let's use 1020 for Stopanska Bank Current Account. Enter the name in Cyrillic: Тековна сметка - Стопанска Банка. Select the category: Current Assets, and subcategory: Bank Accounts.

Set the account type to 'Bank'. This tells the system to include this account in bank reconciliation. Enter your bank account number if you want to track it. Click 'Save Account'.

Your new account appears in the chart with account code 1020. You can see the current balance is zero because we haven't recorded any transactions yet. You can edit or deactivate accounts at any time."

**Callouts:**
- Account categories hierarchy
- Account code numbering system
- Required fields marked
- Account type selector
- Balance display

**Screen Flow:**
1. Accounting menu
2. Chart of Accounts
3. Expand Assets category
4. Add Account button
5. Account form
6. Save confirmation
7. Account appears in list

#### Section 4: Configure Automatic Journal Entries (1.5 minutes)

**Visual:** Settings > Accounting Integration

**Voiceover:**
"Now let's configure automatic journal entries. Go to Settings, then Accounting Integration. This is where you tell Facturino which accounts to use for different transaction types.

For invoices, we need to map three accounts. First, Accounts Receivable - select account 1210 'Побарувања од купувачи'. Second, Revenue account - select 4100 'Приходи од продажба'. Third, VAT Payable - select 2310 'ДДВ за плаќање'.

When you create an invoice, the system will automatically debit Accounts Receivable and credit Revenue for the subtotal, plus credit VAT Payable for the tax amount.

For payments, map Cash/Bank account - we'll use our newly created 1020 Stopanska Bank account. And Accounts Receivable - same 1210 account.

You can also configure expense accounts, purchase accounts, and other transaction types. For now, these basic settings will handle invoices and payments. Click 'Save Settings'."

**Callouts:**
- Account mapping form
- Example journal entry preview
- Debit/credit flow diagram
- Macedonian account names in Cyrillic
- Save confirmation

#### Section 5: Create Invoice with Journal Entry (1.5 minutes)

**Visual:** Create new invoice

**Voiceover:**
"Let's see the automatic journal entries in action. Create a new invoice to Македонска Трговска ДООЕЛ. Add one item: Web Development Services, quantity 10 hours at 2,500 denars per hour. Subtotal: 25,000 denars. Tax at 18%: 4,500 denars. Total: 29,500 denars.

Click 'Save Invoice'. Notice the notification: 'Journal entry created automatically'. Let's view it. Click on the Accounting tab in the invoice detail page.

Here's the journal entry. Entry number JE-2025-001. Entry date matches the invoice date. You can see the debits and credits:

Debit: Accounts Receivable (1210) - 29,500 MKD
Credit: Revenue (4100) - 25,000 MKD
Credit: VAT Payable (2310) - 4,500 MKD

Total debits: 29,500. Total credits: 29,500. The entry is balanced. All accounts are in Macedonian with proper Cyrillic names. The reference shows the invoice number INV-2025-001 for easy tracking.

This journal entry is now part of your general ledger and will appear in your financial statements."

**Callouts:**
- Invoice creation form
- Automatic journal entry notification
- Journal entry details modal
- Debit/credit columns with totals
- Balance verification checkmark
- Reference to source invoice

**Screen Flow:**
1. Create Invoice
2. Fill invoice details
3. Save invoice
4. Journal entry created notification
5. Accounting tab
6. View journal entry
7. Debit/credit breakdown

#### Section 6: Record Payment and Reconciliation (1 minute)

**Visual:** Record payment on invoice

**Voiceover:**
"When payment is received, record it on the invoice. Click 'Record Payment', enter the amount: 29,500 denars. Select payment method: Bank Transfer. Select the bank account: 1020 Stopanska Bank. Payment date: today. Add a reference: bank transaction ID.

Click 'Save Payment'. Another journal entry is created automatically:

Debit: Bank - Stopanska (1020) - 29,500 MKD
Credit: Accounts Receivable (1210) - 29,500 MKD

The invoice status changes to 'Paid'. Accounts Receivable is reduced to zero for this customer. Your bank account balance increases by 29,500 denars. Everything is reconciled and balanced."

**Callouts:**
- Payment form
- Bank account selector
- Journal entry for payment
- Invoice status change to "Paid"
- Account balances updated

#### Section 7: View Financial Statements (1.5 minutes)

**Visual:** Reports > Financial Statements

**Voiceover:**
"Now let's generate financial statements. Navigate to Reports, then Financial Statements.

First, let's view the Balance Sheet. Select the reporting period - let's use Month to Date. Click 'Generate Report'.

Here's your balance sheet following IFRS format. Assets section shows Current Assets: Bank account 1020 has 29,500 denars. Accounts Receivable shows zero because we received payment. Total Current Assets: 29,500 denars.

Liabilities section shows VAT Payable: 4,500 denars.

Equity section shows Retained Earnings: 25,000 denars - this is our revenue minus expenses.

The accounting equation is balanced: Assets 29,500 = Liabilities 4,500 + Equity 25,000. The statement is in both Macedonian and English, and you can export to PDF or Excel.

Now let's view the Income Statement. Select the same period and generate. You can see Revenue: 25,000 denars from our invoice. Expenses: zero for this example. Net Income: 25,000 denars. This flows to Retained Earnings in the balance sheet."

**Callouts:**
- Report selector menu
- Period selector
- Balance sheet format (IFRS)
- Accounting equation verification
- Income statement format
- Export options (PDF, Excel)

**Screen Flow:**
1. Reports menu
2. Financial Statements
3. Balance Sheet tab
4. Select period
5. Generate report
6. View balance sheet
7. Switch to Income Statement
8. View income statement
9. Export options

#### Section 8: Multi-Currency Support (1 minute)

**Visual:** Multi-currency transaction example

**Voiceover:**
"Facturino supports multi-currency accounting. If you invoice a client in euros or dollars, the system uses the exchange rate on the transaction date to convert to your base currency - Macedonian denars.

Here's an invoice in euros: 1,500 EUR. The exchange rate is 1 EUR = 61.5 MKD. The journal entry shows both currencies: Revenue 1,500 EUR (92,250 MKD). Financial statements use the base currency for consolidation, but you can view multi-currency reports to see foreign currency balances.

Exchange rate differences are automatically posted to a Forex Gain/Loss account following IFRS requirements."

**Callouts:**
- Currency selector on invoice
- Exchange rate display
- Dual currency journal entry
- Forex gain/loss account
- Multi-currency report example

#### Section 9: Accountant Multi-Company Dashboard (45 seconds)

**Visual:** Accountant dashboard showing multiple clients

**Voiceover:**
"For accountants managing multiple clients, use the Multi-Company Dashboard. Click the company selector in the top right. You'll see all your client companies. Switch between them instantly - no need to log out and in.

Each company has its own chart of accounts, journal entries, and financial statements. You can generate consolidated reports across all clients or individual reports per company. The dashboard shows key metrics for all your clients at a glance: total revenue, expenses, profit, and outstanding receivables."

**Callouts:**
- Company selector dropdown
- Client list with company names
- Quick stats for each company
- Consolidated reporting option

#### Section 10: Conclusion (30 seconds)

**Visual:** Accounting dashboard with financial statements

**Voiceover:**
"Congratulations! You've set up IFRS-compliant accounting in Facturino. You now have automated journal entries, a customized chart of accounts, and real-time financial statements.

Remember, the system automatically handles invoices, payments, and expenses. Your financial statements are always up to date and audit-ready. For advanced topics like depreciation, inventory accounting, and multi-entity consolidation, check our advanced accounting tutorials.

Visit help.facturino.mk for detailed accounting guides. Happy accounting!"

**Callouts:**
- Key features recap
- Advanced topics teaser
- Help resources

---

### Screen Flow Summary for IFRS Accounting Tutorial

1. Introduction to accounting module
2. IFRS integration explanation
3. Chart of Accounts setup
4. Add new bank account
5. Configure automatic journal entries
6. Create invoice with auto journal entry
7. View generated journal entry
8. Record payment
9. View payment journal entry
10. Generate Balance Sheet
11. Generate Income Statement
12. Multi-currency example
13. Multi-company dashboard
14. Conclusion

---

### Required Test Data for IFRS Accounting Tutorial

**Company:**
- Name: Тест Сметководство ДООЕЛ
- Base Currency: MKD
- Fiscal Year: 2025

**Chart of Accounts:**
- 1020: Stopanska Bank Current Account
- 1210: Accounts Receivable (Побарувања)
- 2310: VAT Payable (ДДВ)
- 4100: Revenue (Приходи)
- 5100: Expenses (Трошоци)

**Invoice:**
- Customer: Македонска Трговска ДООЕЛ
- Item: Web Development, 10 hours @ 2,500 MKD
- Subtotal: 25,000 MKD
- Tax: 4,500 MKD (18%)
- Total: 29,500 MKD

**Payment:**
- Amount: 29,500 MKD
- Method: Bank Transfer
- Account: 1020 Stopanska Bank

---

### Key Talking Points for IFRS Accounting Tutorial

1. **IFRS Compliance:** Full compliance with international standards
2. **Automatic Journal Entries:** No manual entry needed for invoices/payments
3. **Double-Entry:** Every transaction balanced (debits = credits)
4. **Real-Time Statements:** Balance sheet and income statement always current
5. **Multi-Currency:** Support for EUR, USD with automatic conversion
6. **Macedonian Localization:** Account names in Cyrillic, MKD base currency
7. **Professional Features:** Chart of accounts, journal entries, financial statements
8. **Multi-Company:** Accountants can manage multiple clients from one dashboard

---

## ADDITIONAL RECOMMENDED TUTORIALS

### Tutorial 4: Creating Your First Invoice

**Duration:** 5-7 minutes
**Complexity:** Low
**Audience:** First-time users

**Key Sections:**
1. Introduction to invoicing module (30s)
2. Add customer (1 min)
3. Create invoice (2 min)
4. Customize invoice template (1 min)
5. Preview and send invoice (1 min)
6. Track invoice status (30s)
7. Conclusion (30s)

**Talking Points:**
- Invoice numbering and customization
- Adding items and services
- Tax calculations (18%, 5%, 0%)
- Payment terms and due dates
- Email delivery
- Invoice templates in Macedonian/Albanian

---

### Tutorial 5: Bank Feed Integration (PSD2)

**Duration:** 6-8 minutes
**Complexity:** Medium
**Audience:** Advanced users

**Key Sections:**
1. Introduction to PSD2 bank feeds (30s)
2. Connect Stopanska Bank (1.5 min)
3. OAuth authentication flow (1 min)
4. Sync transactions (1 min)
5. Match transactions to invoices (1.5 min)
6. Bank reconciliation (1 min)
7. Add additional banks (NLB, Komercijalna) (30s)
8. Conclusion (30s)

**Talking Points:**
- PSD2 open banking standard
- Supported Macedonian banks (Stopanska, NLB, Komercijalna)
- Automatic transaction import
- Smart matching algorithms
- Bank reconciliation workflow
- Security and data protection

---

### Tutorial 6: Paddle Subscription Setup

**Duration:** 5-6 minutes
**Complexity:** Low
**Audience:** Business owners

**Key Sections:**
1. Introduction to billing (30s)
2. View subscription plans (1 min)
3. Select and subscribe to plan (1.5 min)
4. Payment with Paddle (1 min)
5. Manage subscription (1 min)
6. Invoicing and receipts (30s)
7. Conclusion (30s)

**Talking Points:**
- Available plans (Basic, Professional, Enterprise)
- Pricing in EUR/MKD
- Paddle payment gateway
- Automatic billing and receipts
- Upgrade/downgrade options
- Partner commission tracking

---

### Tutorial 7: Multi-Company Accountant Dashboard

**Duration:** 8-10 minutes
**Complexity:** Medium
**Audience:** Professional accountants

**Key Sections:**
1. Introduction to multi-company features (30s)
2. Add client company (1 min)
3. Switch between companies (1 min)
4. Company-specific settings (1.5 min)
5. Consolidated reporting (2 min)
6. Client permissions and access (1 min)
7. Billing clients for services (1 min)
8. Conclusion (30s)

**Talking Points:**
- Manage multiple clients from one account
- Separate data per company
- Consolidated financial reports
- Client user permissions
- Time tracking and billing
- Accountant commission tracking

---

### Tutorial 8: VAT Reporting & Compliance

**Duration:** 7-9 minutes
**Complexity:** Medium
**Audience:** Macedonian businesses

**Key Sections:**
1. Introduction to Macedonian VAT (30s)
2. VAT rates and configuration (1 min)
3. VAT on invoices and purchases (1.5 min)
4. Generate VAT report (ДДВ-04) (2 min)
5. Export to XML for UJP submission (1.5 min)
6. VAT payment tracking (1 min)
7. Quarterly vs monthly filing (30s)
8. Conclusion (30s)

**Talking Points:**
- Macedonian VAT rates (18%, 5%, 0%)
- ДДВ-04 report format
- UJP submission requirements
- Monthly/quarterly filing options
- VAT payment deadlines
- Penalties for late filing

---

## PRODUCTION GUIDE

### Recording Software Recommendations

#### Screen Recording

**Free Options:**
- **OBS Studio** (Windows/Mac/Linux)
  - Open source, powerful
  - Supports multiple scenes
  - Good for complex tutorials
  - Learning curve: Medium
  - Download: obsproject.com

- **QuickTime Player** (Mac only)
  - Built-in, simple
  - Good quality
  - Limited features
  - Learning curve: Low
  - Pre-installed on macOS

**Paid Options:**
- **Camtasia** ($299)
  - Best for beginners
  - Built-in editor
  - Cursor effects included
  - Learning curve: Low
  - Recommended for this project

- **ScreenFlow** (Mac, $169)
  - Professional quality
  - Great editing tools
  - Built-in annotations
  - Learning curve: Medium
  - Excellent for Mac users

- **Snagit** ($62.99)
  - Simple and affordable
  - Good for short tutorials
  - Easy annotations
  - Learning curve: Low

#### Audio Recording

**Free Options:**
- **Audacity** (Windows/Mac/Linux)
  - Industry standard
  - Noise reduction tools
  - Multi-track editing
  - Learning curve: Medium

**Paid Options:**
- **Adobe Audition** ($20.99/month)
  - Professional audio editing
  - Advanced noise reduction
  - Batch processing
  - Learning curve: High

**Microphone Recommendations:**

**Budget ($50-$100):**
- Blue Snowball ($49)
- Fifine K669B ($35)
- Good enough for tutorials

**Professional ($100-$200):**
- Blue Yeti ($130) - Recommended
- Audio-Technica AT2020 ($100)
- Samson Q2U ($70)
- Excellent quality for the price

**Premium ($200+):**
- Shure SM7B ($399)
- Only if you plan many videos

#### Video Editing

**Free Options:**
- **DaVinci Resolve** (Windows/Mac/Linux)
  - Professional-grade
  - Color correction
  - Audio mixing
  - Learning curve: High
  - Recommended for free option

- **iMovie** (Mac only)
  - Simple and intuitive
  - Good for basic editing
  - Limited features
  - Learning curve: Low

**Paid Options:**
- **Adobe Premiere Pro** ($20.99/month)
  - Industry standard
  - Integration with Adobe suite
  - Learning curve: High
  - Best for professionals

- **Final Cut Pro** (Mac, $299 one-time)
  - Professional Mac editing
  - Fast rendering
  - Magnetic timeline
  - Learning curve: Medium
  - Great for Mac users

- **Camtasia** ($299, already mentioned)
  - Screen recording + editing
  - Best all-in-one solution
  - Learning curve: Low
  - **Recommended for this project**

---

### Production Workflow

#### Phase 1: Pre-Production (2-3 days)

**Day 1: Script and Storyboard**
1. Review existing scripts (Migration already done)
2. Write scripts for E-Faktura and IFRS tutorials
3. Create storyboards with scene breakdowns
4. Prepare example data and test accounts
5. Review and approve scripts

**Day 2: Setup and Testing**
1. Install and configure recording software
2. Set up microphone and test audio levels
3. Configure screen resolution (1920x1080)
4. Prepare clean browser profile
5. Test recording workflow with 2-minute sample
6. Review sample quality

**Day 3: Final Prep**
1. Create test data in Facturino
2. Prepare example files (CSV, certificates)
3. Clean desktop and disable notifications
4. Write detailed recording checklist
5. Schedule recording sessions

#### Phase 2: Production (4-6 days)

**Tutorial 1: Migration Wizard (Day 1-2)**
- 3 hours: Record screen capture (all scenes)
- 2 hours: Record voiceover
- 3 hours: Edit and sync audio/video
- 2 hours: Add callouts and annotations
- 1 hour: Review and revisions
- Total: 11 hours over 2 days

**Tutorial 2: E-Faktura (Day 3-4)**
- 2.5 hours: Record screen capture
- 1.5 hours: Record voiceover
- 3 hours: Edit and sync
- 2 hours: Add callouts and UJP verification
- 1 hour: Review and revisions
- Total: 10 hours over 2 days

**Tutorial 3: IFRS Accounting (Day 5-6)**
- 2.5 hours: Record screen capture
- 1.5 hours: Record voiceover
- 3 hours: Edit and sync
- 2 hours: Add callouts and financial statements
- 1 hour: Review and revisions
- Total: 10 hours over 2 days

#### Phase 3: Post-Production (1-2 days)

**Day 1: Polish and Export**
1. Final color correction
2. Audio mastering (normalize, compression)
3. Add intro/outro animations
4. Add background music
5. Create custom thumbnails
6. Export at final settings (1080p, H.264)
7. Quality check on different devices

**Day 2: Publishing and Metadata**
1. Upload to YouTube/Vimeo
2. Add titles, descriptions, tags
3. Create playlists
4. Add to help documentation
5. Create social media clips
6. Announce to users

---

### Recording Best Practices

#### Environment Setup

**Room Preparation:**
- Quiet room with minimal echo
- Close windows to reduce noise
- Turn off fans, AC, or noisy equipment
- Use "Do Not Disturb" mode on all devices
- Place "Recording in Progress" sign if needed

**Computer Setup:**
- Close all unnecessary applications
- Disable notifications (macOS: Do Not Disturb, Windows: Focus Assist)
- Clear browser cache and cookies
- Use incognito/private browsing mode
- Hide bookmarks bar
- Set browser zoom to 100%
- Screen resolution: 1920x1080
- Disable screen saver

**Audio Setup:**
- Microphone 6-8 inches from mouth
- Use pop filter to reduce plosives (p, b, t sounds)
- Test audio levels (peaks at -6dB to -3dB)
- Record 10 seconds of room tone for noise reduction
- Use headphones to monitor audio during recording

#### Recording Techniques

**Screen Recording:**
- Record in segments, not all at once
- Pause recording between scenes for breaks
- Move cursor slowly and deliberately
- Pause briefly (1-2 seconds) after each action
- Highlight important UI elements with cursor
- Avoid rapid scrolling or clicking
- Record each scene 2-3 times for options
- Keep recordings under 15 minutes to avoid file corruption

**Voiceover Recording:**
- Read script aloud 2-3 times before recording
- Maintain consistent distance from microphone
- Speak clearly and at moderate pace (260 words/minute)
- Pause 1-2 seconds between sentences
- Re-record sentences with mistakes immediately
- Stay hydrated but avoid mouth noise
- Smile while speaking (improves vocal tone)
- Record each section separately for easier editing

**Cursor Highlighting:**
- Use cursor highlighting software:
  - **MousePointer Pro** (Windows, $4.99)
  - **Mouseposé** (Mac, $4.99)
  - **OBS Studio** cursor highlight plugin (free)
- Enable click animation (ripple effect)
- Use yellow or blue highlight color
- Size: 32-48px cursor with 64-96px highlight
- Keep highlight subtle, not distracting

---

### Editing Workflow

#### Video Editing Steps

1. **Import Assets**
   - Screen recordings
   - Voiceover audio
   - Background music (royalty-free)
   - Sound effects (clicks, success sounds)
   - Intro/outro animations
   - Logo assets

2. **Rough Cut**
   - Arrange video clips in sequence
   - Trim excess footage (long pauses, mistakes)
   - Sync voiceover with video
   - Add scene transitions (0.3s fade)
   - Mark sections for callouts

3. **Add Visual Elements**
   - Title cards (intro, section headers)
   - Text overlays (key points, definitions)
   - Callout boxes (highlight important info)
   - Arrows and highlights (point to UI elements)
   - Annotations (explain complex concepts)
   - Lower thirds (display speaker info if applicable)

4. **Audio Mixing**
   - Normalize voiceover levels (-3dB peak)
   - Add background music (-25dB, subtle)
   - Add sound effects (-15dB for clicks, -10dB for success sounds)
   - Remove loud breaths between sentences
   - Apply noise reduction if needed
   - EQ to enhance voice clarity (boost 3-5kHz)
   - Add subtle compression (3:1 ratio, -20dB threshold)

5. **Color Correction**
   - Adjust brightness/contrast for consistency
   - Correct color temperature
   - Ensure UI elements are readable
   - Match color across all scenes

6. **Final Review**
   - Watch entire video start to finish
   - Check audio sync (lips match speech)
   - Verify all callouts appear at correct times
   - Check for spelling errors in text overlays
   - Test on different devices (laptop, phone, tablet)
   - Get feedback from colleague or test user

7. **Export**
   - Resolution: 1920x1080
   - Frame rate: 30fps
   - Codec: H.264
   - Bitrate: 8-10 Mbps
   - Audio: AAC, 192kbps, 48kHz
   - Format: MP4

---

### Style Guide for Visual Consistency

#### Color Palette

**Primary Colors:**
- Facturino Blue: #3B82F6 (highlights, buttons, links)
- Success Green: #10B981 (checkmarks, success states)
- Warning Amber: #F59E0B (warnings, cautions)
- Error Red: #EF4444 (errors, validation failures)
- Dark Gray: #1F2937 (main text)
- Light Gray: #F9FAFB (callout backgrounds)

**Usage:**
- Use blue for highlighting interactive elements
- Use green for success indicators and checkmarks
- Use amber for warnings and important notices
- Use red sparingly for errors only
- Maintain consistent color usage across all tutorials

#### Typography

**Font Family:**
- Primary: Roboto (Google Fonts)
- Code/Data: Roboto Mono

**Font Sizes:**
- Main Title: 48px, Bold
- Section Headers: 40px, Bold
- Subtitles: 32px, Regular
- Body Text: 28px, Regular
- Code/Data: 28px, Roboto Mono
- Small Notes: 24px, Regular

**Text Overlays:**
- Use high contrast (white text on dark background or vice versa)
- Add subtle drop shadow for readability (0 2px 4px rgba(0,0,0,0.3))
- Keep text on screen for minimum 3 seconds
- Limit text to 2-3 lines per overlay
- Left-align for longer text, center for short phrases

#### Animation Guidelines

**Timing:**
- Fade in/out: 0.3 seconds
- Slide in: 0.4 seconds with ease-out
- Scale/pop: 0.2 seconds with ease-in-out
- Progress bars: 1.0 second linear
- Checkmarks: 0.3 seconds with bounce effect

**Callout Animations:**
- Callouts slide in from right
- Arrows draw on over 0.5 seconds
- Highlights pulse gently (opacity 80%-100%)
- Circles draw on clockwise over 0.4 seconds
- Keep animations smooth, not jarring

**Transition Types:**
- Between major sections: 0.5s cross-fade
- Within same scene: direct cut (no transition)
- To error examples: quick fade to red overlay (0.2s)
- To success: fade to green with checkmark

#### Callout Design

**Callout Boxes:**
- Rounded corners: 8px border-radius
- Drop shadow: 0 4px 6px rgba(0,0,0,0.1)
- Border: 2px solid matching color
- Internal padding: 16px
- Max width: 400px for text callouts
- Background: semi-transparent white (rgba(255,255,255,0.95))

**Arrow Styles:**
- Stroke width: 4px
- Arrowhead size: 12px
- Curved arrows for distant elements
- Straight arrows for nearby elements
- Color matches callout purpose (blue for info, green for success, etc.)

**Highlight Circles:**
- Stroke width: 4px
- Stroke color: yellow (#FBBF24) or blue (#3B82F6)
- No fill (transparent inside)
- Size: 20-40px larger than element being highlighted
- Pulse animation (optional)

---

## VIDEO SPECIFICATIONS

### Technical Requirements

#### Video Format

| Setting | Value | Notes |
|---------|-------|-------|
| Resolution | 1920x1080 | Full HD, 16:9 aspect ratio |
| Frame Rate | 30fps | Standard for tutorial videos |
| Codec | H.264 | Best compatibility |
| Bitrate | 8-10 Mbps | High quality without huge files |
| Container | MP4 | Universal support |
| Color Space | sRGB | Standard for web |
| Chroma Subsampling | 4:2:0 | Standard for online video |

#### Audio Format

| Setting | Value | Notes |
|---------|-------|-------|
| Codec | AAC | Best compatibility |
| Sample Rate | 48kHz | Professional standard |
| Bit Depth | 16-bit | CD quality |
| Bitrate | 192kbps | High quality for speech |
| Channels | Stereo | 2.0 stereo |
| Normalization | -3dB peak | Prevents clipping |

#### File Size Estimates

**Per Tutorial:**
- 5-minute video: ~300-400 MB
- 10-minute video: ~600-800 MB
- 15-minute video: ~900-1200 MB

**Full Series (8 tutorials, ~65 minutes total):**
- Total size: ~5-6 GB

### Quality Standards

#### Video Quality Checklist

- [ ] Resolution is 1920x1080
- [ ] Frame rate is consistent 30fps (no drops)
- [ ] Text is crisp and readable on all devices
- [ ] Cyrillic characters display correctly
- [ ] No pixelation or compression artifacts
- [ ] Colors are vibrant and consistent
- [ ] Brightness/contrast is balanced
- [ ] UI elements are clearly visible
- [ ] Cursor is easy to follow
- [ ] No screen tearing or glitches

#### Audio Quality Checklist

- [ ] Voice is clear and intelligible
- [ ] No background noise or hum
- [ ] No plosives (p, b, t sounds too loud)
- [ ] No clipping or distortion
- [ ] Volume is consistent throughout
- [ ] Background music is subtle, not distracting
- [ ] Sound effects enhance, not overwhelm
- [ ] Audio syncs perfectly with video
- [ ] No echo or room reverb
- [ ] Audio levels normalized to -3dB

#### Content Quality Checklist

- [ ] Script is accurate and complete
- [ ] Pacing is appropriate (not too fast/slow)
- [ ] Examples use realistic data
- [ ] No sensitive information visible
- [ ] No spelling or grammar errors in overlays
- [ ] Callouts appear at right times
- [ ] Visual flow is logical
- [ ] Troubleshooting covers common issues
- [ ] Conclusion summarizes key points
- [ ] Call to action is clear

---

### Export Settings by Platform

#### YouTube Upload

**Recommended Settings:**
```
Resolution: 1920x1080
Frame Rate: 30fps
Codec: H.264
Bitrate: 8 Mbps (constant)
Audio: AAC, 192kbps, 48kHz
Format: MP4
Filename: facturino_migration_wizard_tutorial_1080p.mp4
```

**Upload Metadata Template:**
```
Title: [Tutorial Name] - Facturino Tutorial (Macedonia)
Description: [See template below]
Tags: facturino, [topic], macedonia, tutorial, accounting
Category: Education
Language: English (or Macedonian for MK version)
Thumbnail: 1280x720 custom thumbnail
End Screen: Enable
Cards: Add relevant cards
Subtitles: Upload SRT file
```

#### Vimeo Upload

**Recommended Settings:**
```
Resolution: 1920x1080
Frame Rate: 30fps
Codec: H.264
Bitrate: 10 Mbps (for higher quality)
Audio: AAC, 256kbps, 48kHz
Format: MP4
```

**Vimeo Advantages:**
- Higher bitrate allowed (better quality)
- No ads
- Professional appearance
- Better for website embedding
- Privacy controls

#### Website Embedding

**For Help Documentation:**
```html
<iframe
  src="https://www.youtube.com/embed/VIDEO_ID"
  width="1920"
  height="1080"
  frameborder="0"
  allow="autoplay; encrypted-media"
  allowfullscreen>
</iframe>
```

**Responsive Sizing:**
```html
<div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
  <iframe
    src="https://www.youtube.com/embed/VIDEO_ID"
    style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"
    frameborder="0"
    allowfullscreen>
  </iframe>
</div>
```

---

### Thumbnail Design Specifications

#### Technical Requirements

| Setting | Value |
|---------|-------|
| Resolution | 1280x720 pixels |
| Aspect Ratio | 16:9 |
| File Format | JPG or PNG (PNG for transparency) |
| File Size | < 2MB |
| Color Mode | RGB |

#### Design Guidelines

**Layout:**
- Left 60%: Screenshot from tutorial
- Right 40%: Text overlay
- Facturino logo in top-left corner

**Text:**
- Tutorial title: Bold, 72px minimum
- Subtitle: 48px
- Use high contrast colors
- Ensure text readable on mobile (small size)

**Visual Elements:**
- Show actual UI from tutorial
- Include relevant icon/badge (e.g., "E-Faktura" badge)
- Use brand colors (Facturino blue)
- Add subtle drop shadow on text

**Examples:**

**Migration Wizard Thumbnail:**
```
[Screenshot: Upload CSV files interface] | MIGRATION WIZARD
                                         | Import Data to Facturino
                                         | [Facturino Logo]
```

**E-Faktura Thumbnail:**
```
[Screenshot: UBL XML preview]           | E-FAKTURA
                                        | Submit Invoices to UJP
                                        | [Macedonia Flag] [Facturino Logo]
```

**IFRS Accounting Thumbnail:**
```
[Screenshot: Balance Sheet]             | IFRS ACCOUNTING
                                        | Professional Bookkeeping
                                        | [IFRS Badge] [Facturino Logo]
```

---

## HOSTING & DISTRIBUTION

### Primary Platform: YouTube

**Channel Setup:**
- Channel Name: Facturino Official
- Channel URL: youtube.com/@facturino (custom URL)
- Channel Description: "Official tutorials for Facturino - Macedonian accounting software with e-Faktura, IFRS accounting, and bank integration."
- Channel Art: 2560x1440 banner with Facturino branding
- Profile Picture: Facturino logo 800x800

**Playlist Structure:**
1. Getting Started
   - Creating Your First Invoice
   - Adding Customers
   - Basic Settings

2. Data Migration
   - Migration Wizard (Complete Guide)
   - Migrating from Onivo
   - Migrating from Megasoft

3. E-Faktura & Tax Compliance
   - E-Faktura Submission Workflow
   - VAT Reporting (ДДВ-04)
   - Tax Configuration

4. Professional Accounting
   - IFRS Accounting Setup
   - Chart of Accounts
   - Financial Statements
   - Multi-Company Management

5. Advanced Features
   - Bank Feed Integration (PSD2)
   - Paddle Subscription Setup
   - Multi-Currency Invoicing

**Upload Schedule:**
- Week 1: Migration Wizard
- Week 2: E-Faktura Submission
- Week 3: IFRS Accounting Setup
- Week 4: Creating Your First Invoice
- Month 2: Additional tutorials as needed

**Optimization:**
- Upload during low-traffic times (2-4 AM local time)
- Use YouTube Studio to schedule releases
- Add to relevant playlists immediately
- Pin best comment after 24 hours
- Respond to comments within 24-48 hours

---

### Secondary Platforms

#### Vimeo

**Use Case:** High-quality version for website embedding

**Setup:**
- Vimeo Pro account ($20/month) for better analytics
- Private videos (only accessible via embed)
- No ads for professional appearance
- Better quality than YouTube embeds

**Upload Settings:**
- Same videos as YouTube
- Privacy: "Hide from Vimeo.com" (only embed)
- Download: Disabled (prevent piracy)
- Comments: Disabled (use YouTube for engagement)

#### Help Documentation Website

**Integration:**
- Embed YouTube videos in help articles
- Responsive iframe sizing
- Contextual help text below video
- "Watch on YouTube" link for better quality
- Transcript available for accessibility

**Example Help Article Structure:**
```markdown
# How to Use the Migration Wizard

[Video Embed]

**In this tutorial you'll learn:**
- How to download CSV templates
- How to fill in your data
- How to upload and validate files
- How to complete the import

**Transcript:** [expandable section]
[Full video transcript for SEO and accessibility]

**Related Articles:**
- Preparing Data for Migration
- Troubleshooting Migration Errors
- Supported File Formats
```

#### Social Media Distribution

**Instagram (60-second clips):**
- Extract key moments from full tutorials
- Add captions (auto-generated)
- Use hashtags: #facturino #accounting #macedonia #smallbusiness
- Post 2-3 times per week

**Facebook (2-minute versions):**
- Longer clips than Instagram
- Share to Facturino Facebook page
- Boost top-performing videos ($10-20)
- Engage with comments

**LinkedIn (professional audience):**
- Share full tutorials or 3-minute highlights
- Target accountants and business owners
- Professional tone in description
- Post during business hours (Tuesday-Thursday 10 AM - 2 PM)

**Twitter/X (30-second teasers):**
- Very short clips with link to full video
- Use relevant hashtags
- Quote tweet with key takeaways
- Schedule 1-2 per week

---

### Internal Distribution

#### Customer Onboarding

**New User Email Series:**
- Day 1: Welcome email with "Creating Your First Invoice" video
- Day 3: "Migration Wizard" video (if they haven't created invoices)
- Day 7: "E-Faktura Setup" video (for Macedonian users)
- Day 14: "IFRS Accounting" video (if they have accountant role)

**In-App Tutorials:**
- Context-sensitive video links in UI
- "Watch tutorial" button on complex pages
- Tooltips with video links
- First-time user guided tour with video integration

#### Support Team Resources

**Support Portal:**
- Videos categorized by topic
- Search function to find relevant videos
- Quick links from support tickets
- "Suggest a tutorial" form for customers

**Support Agent Training:**
- Required viewing for all new support agents
- Reference videos when answering tickets
- "Watch this video" links in responses
- Track which videos reduce support load

#### Sales Team Materials

**Demo Videos:**
- Use migration video to show ease of setup
- Use E-Faktura video to highlight compliance
- Use IFRS video for accountant prospects
- Embed in sales presentations

**Prospect Outreach:**
- Send relevant tutorial based on prospect needs
- Include in proposal documents
- Share on social selling (LinkedIn)

---

### Content Promotion Strategy

#### Launch Week Activities

**Day 1 (Monday):**
- Upload Migration Wizard video to YouTube
- Publish help article with embedded video
- Email newsletter announcement to all users
- Share on LinkedIn and Facebook

**Day 2 (Tuesday):**
- Create Instagram clip (60s) from key moment
- Share on Twitter with key takeaways
- Engage with comments on all platforms

**Day 3 (Wednesday):**
- Publish blog post: "How to Migrate to Facturino in 10 Minutes"
- Include video embed in blog post
- Share blog post on social media
- Submit to relevant Macedonian business forums

**Day 4 (Thursday):**
- Monitor YouTube analytics
- Respond to all comments
- Create Facebook highlight clip (2 min)
- Boost Facebook post ($10-20)

**Day 5 (Friday):**
- Review analytics and engagement
- Thank engaged viewers
- Prepare for next tutorial release

#### Ongoing Promotion

**Monthly Activities:**
- Analyze video performance metrics
- Create compilation/highlight videos
- Refresh thumbnails if needed
- Update video descriptions with new links

**Quarterly Activities:**
- Survey users for new tutorial ideas
- Plan next batch of tutorials
- Review SEO and update tags
- Create year-in-review video (if applicable)

---

### SEO and Discoverability

#### YouTube SEO

**Title Optimization:**
- Include main keyword first
- Add context: "(Macedonia)" or "(Macedonian)"
- Keep under 60 characters for mobile display
- Example: "E-Faktura Tutorial - Submit Invoices to UJP (Macedonia) - Facturino"

**Description Template:**
```
[Main description paragraph - 2-3 sentences]

In this tutorial you'll learn:
✓ [Key point 1]
✓ [Key point 2]
✓ [Key point 3]

Timestamps:
0:00 - Introduction
0:30 - [Section 1]
1:30 - [Section 2]
...

🔗 Resources:
• Help Documentation: https://help.facturino.mk
• CSV Templates: [link]
• Sign up for Facturino: https://facturino.mk

🇲🇰 Macedonian-specific features:
• VAT number format (MK + 13 digits)
• Cyrillic character support
• Macedonian Denar (MKD) currency
• Tax rates: 18%, 5%, 0%

💬 Have questions? Leave a comment below!

📧 Contact: support@facturino.mk

#Facturino #Macedonia #Accounting #Tutorial #[TopicKeyword]

[Full transcript for SEO - optional but recommended]
```

**Tags (Maximum 500 characters):**
```
facturino, macedonia accounting, e-faktura, macedonian invoicing, accounting software, small business accounting, invoice software macedonia, VAT macedonia, macedonian denar, онлајн фактури, сметководство македонија, ддв македонија
```

**Closed Captions:**
- Upload SRT file with accurate captions
- Include timestamps
- Improves SEO and accessibility
- Helps non-native speakers

#### Help Documentation SEO

**Article Optimization:**
- H1: Main tutorial title
- H2: Section headings matching video chapters
- Meta description: 150-160 characters with main keyword
- Alt text on thumbnail images
- Internal links to related articles
- External links to YouTube video
- Schema markup for VideoObject

**Example Schema Markup:**
```json
{
  "@context": "https://schema.org",
  "@type": "VideoObject",
  "name": "Migration Wizard Tutorial - Facturino",
  "description": "Learn how to import data to Facturino...",
  "thumbnailUrl": "https://facturino.mk/images/thumbnails/migration-wizard.jpg",
  "uploadDate": "2025-11-17",
  "duration": "PT11M",
  "contentUrl": "https://www.youtube.com/watch?v=...",
  "embedUrl": "https://www.youtube.com/embed/..."
}
```

---

## SUCCESS METRICS

### Video Performance Metrics

#### YouTube Analytics (Track Weekly)

**Engagement Metrics:**
| Metric | Target (Month 1) | Target (Month 3) | Target (Month 6) |
|--------|------------------|------------------|------------------|
| Total Views | 500+ | 2,000+ | 5,000+ |
| Average View Duration | 6 min (60%) | 7 min (70%) | 8 min (75%) |
| Watch Time (hours) | 50+ | 250+ | 600+ |
| Likes | 25+ | 100+ | 250+ |
| Comments | 10+ | 40+ | 100+ |
| Shares | 5+ | 20+ | 50+ |
| Subscribers Gained | 50+ | 200+ | 500+ |

**Audience Metrics:**
| Metric | Target | Notes |
|--------|--------|-------|
| Average Age | 25-44 | Business decision-makers |
| Gender Split | 60/40 M/F | Typical for B2B software |
| Geography | 70%+ Macedonia | Primary market |
| Traffic Source | 40% search, 30% suggested, 20% external, 10% direct | Balanced discovery |
| Device | 60% desktop, 30% mobile, 10% tablet | Expected for tutorials |

**Performance Indicators:**
- **Retention Rate:** Aim for 60%+ average retention
- **Click-Through Rate (CTR):** 4%+ from impressions
- **Audience Retention Graph:** Should be relatively flat (not steep drop-off)
- **Top Traffic Sources:** YouTube search (good SEO), suggested videos (good engagement)
- **Playback Locations:** YouTube watch page (70%), embedded (20%), other (10%)

#### Business Impact Metrics (Track Monthly)

**Customer Acquisition:**
| Metric | Measurement | Target |
|--------|-------------|--------|
| Video-Attributed Signups | Track with UTM parameters | 50+ per month |
| Trial-to-Paid Conversion | Compare video viewers vs non-viewers | 10% higher for viewers |
| Time to First Invoice | Days from signup to first invoice | 20% faster for viewers |

**Support Efficiency:**
| Metric | Measurement | Target |
|--------|-------------|--------|
| Support Ticket Reduction | Compare before/after video launch | 15-20% reduction |
| Migration-Related Tickets | Specific to migration issues | 30% reduction |
| Average Resolution Time | Time to resolve tickets | 25% faster (link to video) |
| Customer Self-Service | % of users who watch video before contacting support | 40%+ |

**User Engagement:**
| Metric | Measurement | Target |
|--------|-------------|--------|
| Feature Adoption | % of users using E-Faktura, IFRS, etc. | 10% increase |
| Migration Completion Rate | % of users who complete migration | 80%+ (vs 60% before) |
| User Satisfaction (NPS) | Net Promoter Score surveys | +10 points |
| Product Usage | Active users per month | 15% increase |

---

### Content Quality Metrics

#### Viewer Feedback (Qualitative)

**Comment Analysis (Weekly):**
- Sentiment: Positive, Neutral, Negative (aim for 80%+ positive)
- Common questions: Track recurring questions for FAQ or new videos
- Feature requests: Identify missing tutorials
- User stories: Success stories to feature

**Surveys (Quarterly):**
- "Was this tutorial helpful?" (Yes/No)
- "What could be improved?" (Open text)
- "What other tutorials would you like to see?" (Multiple choice)
- "How did you find this video?" (Discovery path)

#### Technical Quality (Per Video)

**Pre-Publication Checklist:**
- [ ] Audio levels consistent (-3dB peak)
- [ ] No background noise
- [ ] Video resolution 1080p
- [ ] No pixelation or artifacts
- [ ] Text overlays readable on mobile
- [ ] Cyrillic characters display correctly
- [ ] All links in description work
- [ ] Timestamps accurate
- [ ] Thumbnail loads properly
- [ ] Closed captions uploaded

**Post-Publication Review (Week 1):**
- [ ] Average view duration > 50%
- [ ] Likes > 90% positive (likes vs dislikes)
- [ ] No negative comments about quality
- [ ] No reports of broken links
- [ ] Plays correctly on all devices

---

### ROI Calculation

#### Cost Analysis

**Production Costs (One-Time):**
| Item | Cost | Notes |
|------|------|-------|
| Camtasia License | $299 | One-time, all tutorials |
| Blue Yeti Microphone | $130 | One-time |
| Pop Filter | $15 | One-time |
| Total Equipment | $444 | Amortize over all 8 videos = $56/video |

**Labor Costs (Per Tutorial):**
| Task | Hours | Rate | Cost |
|------|-------|------|------|
| Script Writing | 4 | $50/hr | $200 |
| Recording | 5 | $50/hr | $250 |
| Editing | 6 | $50/hr | $300 |
| Review & Revisions | 2 | $50/hr | $100 |
| Publishing & Promotion | 2 | $50/hr | $100 |
| Total Labor per Tutorial | 19 | - | $950 |

**Total Cost per Tutorial:** $1,006 (equipment + labor)
**Total for 3 Required Tutorials:** $3,018
**Total for 8 Recommended Tutorials:** $8,048

#### Revenue Impact (Conservative Estimates)

**Customer Acquisition:**
- 50 video-attributed signups per month
- 20% trial-to-paid conversion = 10 new customers/month
- Average subscription: €30/month
- Annual value: €30 × 12 × 10 = €3,600/month = €43,200/year

**Support Cost Savings:**
- 20% reduction in support tickets
- Average ticket cost: €10 (30 min at €20/hr)
- 100 tickets/month × 20% = 20 fewer tickets
- Savings: €200/month = €2,400/year

**Total Annual Value:** €43,200 + €2,400 = €45,600

**ROI for 3 Tutorials:**
- Investment: €3,018
- Return Year 1: €45,600
- ROI: 1,411% (45,600 / 3,018 × 100)
- Break-even: Within 1 month

**ROI for 8 Tutorials:**
- Investment: €8,048
- Return Year 1: €45,600 (conservative, likely higher with more content)
- ROI: 467%
- Break-even: Within 2 months

---

### A/B Testing for Optimization

#### Thumbnail Testing

**Test Variables:**
- Screenshot position (left vs right)
- Text size (large vs medium)
- Color scheme (blue vs green accent)
- Face vs no face (if presenter appears)
- Badge/icon presence

**Method:**
- Create 2 thumbnail versions
- Run for 2 weeks each
- Compare CTR (click-through rate)
- Use winner going forward

#### Title Testing

**Test Variables:**
- Keyword position (beginning vs end)
- Length (short vs descriptive)
- Format: "How to..." vs "Complete Guide to..."
- Emotional triggers: "Easy", "Complete", "Professional"

**Method:**
- Change title after 1 month
- Compare CTR and views
- Use TubeBuddy or VidIQ for suggestions

#### Content Structure Testing

**Test Variables:**
- Tutorial length (shorter vs comprehensive)
- Introduction length (30s vs 1 min)
- Callout frequency (every 30s vs every 2 min)
- Background music (subtle vs none)

**Method:**
- Analyze retention graphs
- Identify drop-off points
- Adjust future videos accordingly

---

### Continuous Improvement Plan

#### Monthly Review (First Friday of Month)

**Review Checklist:**
1. Analyze views, watch time, retention for all videos
2. Read and categorize all comments
3. Identify top-performing videos (why?)
4. Identify underperforming videos (why?)
5. Survey users for feedback
6. Compile list of new tutorial ideas
7. Update video descriptions with new links
8. Refresh thumbnails if CTR < 3%

#### Quarterly Strategy Session

**Agenda:**
1. Review quarterly goals vs actual performance
2. Identify content gaps (missing tutorials)
3. Plan next quarter's tutorial releases
4. Budget for new equipment if needed
5. Consider hiring video editor (if volume increases)
6. Review competitor tutorials
7. Update style guide if needed
8. Plan any experimental content (e.g., live Q&A)

#### Annual Content Audit

**Activities:**
1. Review all tutorials for outdated information
2. Re-record sections with UI changes
3. Update thumbnails to current brand
4. Consolidate overlapping tutorials
5. Archive or unlist underperforming content
6. Create "year in review" highlight video
7. Survey top engaged users for testimonials
8. Plan next year's content calendar

---

## PRODUCTION TIMELINE

### Phase 1: Pre-Production (Week 1)

| Day | Tasks | Hours | Deliverables |
|-----|-------|-------|--------------|
| Mon | Review existing Migration script, write E-Faktura script outline | 4 | E-Faktura outline |
| Tue | Complete E-Faktura script, create storyboard | 6 | E-Faktura script + storyboard |
| Wed | Write IFRS Accounting script outline | 4 | IFRS outline |
| Thu | Complete IFRS script, create storyboard | 6 | IFRS script + storyboard |
| Fri | Review all scripts, prepare test data, set up recording environment | 4 | Scripts approved, data ready |

**Total:** 24 hours
**Deliverables:**
- E-Faktura script (2,500 words) ✓
- E-Faktura storyboard (30 frames) ✓
- IFRS Accounting script (2,200 words) ✓
- IFRS Accounting storyboard (28 frames) ✓
- Test data prepared ✓
- Recording equipment tested ✓

---

### Phase 2: Production - Tutorial 1 (Week 2)

**Migration Wizard** (Script already complete)

| Day | Tasks | Hours | Notes |
|-----|-------|-------|-------|
| Mon | Screen recording (all 10 scenes) | 3 | Record each scene 2-3 times |
| Tue | Voiceover recording | 2 | Use existing script |
| Wed | Video editing (rough cut, sync audio) | 3 | Arrange clips, remove mistakes |
| Thu | Add visual callouts and annotations | 2 | Follow storyboard |
| Fri | Final review, color correction, export | 1 | Quality check |

**Total:** 11 hours
**Deliverable:** Migration Wizard Tutorial (11 min) ready for upload ✓

---

### Phase 3: Production - Tutorial 2 (Week 3)

**E-Faktura Submission**

| Day | Tasks | Hours | Notes |
|-----|-------|-------|-------|
| Mon | Screen recording (10 sections) | 2.5 | Includes UJP portal |
| Tue | Voiceover recording | 1.5 | Use new script |
| Wed | Video editing (rough cut, sync audio) | 3 | Sync with screen recording |
| Thu | Add visual callouts, XML highlights | 2 | Emphasize security |
| Fri | Final review, export | 1 | Quality check |

**Total:** 10 hours
**Deliverable:** E-Faktura Tutorial (8-10 min) ready for upload ✓

---

### Phase 4: Production - Tutorial 3 (Week 4)

**IFRS Accounting Setup**

| Day | Tasks | Hours | Notes |
|-----|-------|-------|-------|
| Mon | Screen recording (10 sections) | 2.5 | Financial statements focus |
| Tue | Voiceover recording | 1.5 | Use new script |
| Wed | Video editing (rough cut, sync audio) | 3 | Sync with screen recording |
| Thu | Add visual callouts, account highlights | 2 | Chart of accounts visuals |
| Fri | Final review, export | 1 | Quality check |

**Total:** 10 hours
**Deliverable:** IFRS Accounting Tutorial (7-9 min) ready for upload ✓

---

### Phase 5: Post-Production & Publishing (Week 5)

| Day | Tasks | Hours | Deliverables |
|-----|-------|-------|--------------|
| Mon | Create thumbnails for all 3 tutorials | 2 | 3 custom thumbnails |
| Tue | Write YouTube descriptions, tags, metadata | 2 | Optimized metadata |
| Wed | Upload tutorials to YouTube, schedule releases | 2 | Scheduled uploads |
| Thu | Create help documentation articles with embeds | 3 | 3 help articles |
| Fri | Prepare email newsletter, social media posts | 2 | Promotion materials |

**Total:** 11 hours
**Deliverables:**
- 3 custom thumbnails ✓
- 3 YouTube videos uploaded and scheduled ✓
- 3 help documentation articles ✓
- Email newsletter ready ✓
- Social media posts prepared ✓

---

### Phase 6: Launch & Promotion (Week 6)

| Day | Tasks | Hours | Notes |
|-----|-------|-------|-------|
| Mon | Publish Migration Wizard (Tutorial 1) | 1 | YouTube, help docs, email |
| Mon | Monitor launch, respond to comments | 2 | Engagement |
| Tue | Create and share social media clips | 2 | Instagram, Facebook, LinkedIn |
| Wed | Publish E-Faktura (Tutorial 2) | 1 | Staggered release |
| Thu | Publish IFRS Accounting (Tutorial 3) | 1 | Complete series |
| Fri | Week 1 analytics review | 2 | Initial performance |

**Total:** 9 hours

---

### Total Timeline Summary

**Pre-Production:** 1 week (24 hours)
**Production:** 3 weeks (31 hours for 3 tutorials)
**Post-Production:** 1 week (11 hours)
**Launch & Promotion:** 1 week (9 hours)

**Grand Total:** 6 weeks, 75 hours

---

### Milestone Schedule

**Week 1 (Pre-Production):**
- ✅ All scripts written and approved
- ✅ Storyboards complete
- ✅ Test data prepared
- ✅ Recording environment ready

**Week 2 (Production - Migration):**
- ✅ Migration Wizard tutorial complete

**Week 3 (Production - E-Faktura):**
- ✅ E-Faktura tutorial complete

**Week 4 (Production - IFRS):**
- ✅ IFRS Accounting tutorial complete

**Week 5 (Post-Production):**
- ✅ All metadata ready
- ✅ All tutorials uploaded to YouTube
- ✅ Help documentation complete
- ✅ Promotion materials ready

**Week 6 (Launch):**
- ✅ All 3 tutorials published
- ✅ Week 1 performance data collected
- ✅ DOC-VID-01 ticket marked COMPLETE

---

### Optional: Additional Tutorials Timeline

**If proceeding with 5 additional tutorials (Months 2-3):**

**Week 7-8:** Creating Your First Invoice (5-7 min)
**Week 9-10:** Bank Feed Integration (6-8 min)
**Week 11-12:** Paddle Subscription Setup (5-6 min)
**Week 13-14:** Multi-Company Dashboard (8-10 min)
**Week 15-16:** VAT Reporting (7-9 min)

**Total Additional Time:** 10 weeks, ~50 hours

---

## CONCLUSION

This comprehensive video tutorial production plan provides everything needed to complete **DOC-VID-01** (Record Video Tutorials) for the Facturino project.

### Summary of Deliverables

**Critical Tutorials (Required):**
1. ✅ Migration Wizard (READY - script and storyboard complete)
2. ✅ E-Faktura Submission (SCRIPT PROVIDED in this plan)
3. ✅ IFRS Accounting Setup (SCRIPT PROVIDED in this plan)

**Production Resources Provided:**
- ✅ Complete scripts with voiceover text
- ✅ Detailed screen flow outlines
- ✅ Key talking points for each tutorial
- ✅ Required test data specifications
- ✅ Production guide with tool recommendations
- ✅ Video and audio specifications
- ✅ Hosting and distribution strategy
- ✅ Success metrics and ROI calculation
- ✅ 6-week production timeline

**Additional Value:**
- 5 recommended tutorials for Phase 2
- Thumbnail design specifications
- SEO optimization guide
- A/B testing strategies
- Continuous improvement plan

### Next Steps

1. **Immediate (Week 1):** Review and approve E-Faktura and IFRS scripts
2. **Week 2:** Begin production with Migration Wizard (already prepared)
3. **Week 3-4:** Produce E-Faktura and IFRS tutorials
4. **Week 5:** Post-production and metadata
5. **Week 6:** Launch all 3 tutorials

### Success Criteria for DOC-VID-01

- ✅ At least 3 key video tutorials recorded
- ✅ Tutorials cover Migration, E-Faktura, and IFRS Accounting
- ✅ Professional quality (1080p, clear audio, helpful visuals)
- ✅ Published on YouTube and embedded in help documentation
- ✅ Promoted to user base via email and social media

**Estimated Completion:** 6 weeks from start
**Total Investment:** $3,018 (€2,800)
**Expected ROI:** 1,411% in Year 1

---

**Document Version:** 1.0
**Date Created:** 2025-11-17
**Author:** Claude (Anthropic)
**Status:** READY FOR PRODUCTION
**Related Ticket:** DOC-VID-01 (PRODUCTION_READINESS_ROADMAP.md)
