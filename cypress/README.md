# Cypress End-to-End Testing for InvoiceShelf Macedonia

## SMK-01: Comprehensive Smoke Test Implementation

This directory contains the complete Cypress E2E testing setup for validating the enhanced business workflow: **login→invoice→payment→export + accountant console switch**.

## 🎯 Test Coverage

### Phase 1: Admin User Complete Workflow
- ✅ Login as admin user with Macedonia credentials
- ✅ Create customers with Macedonia-specific data (Cyrillic text, MK tax IDs)
- ✅ Create invoices with MKD currency and 18%/5% VAT rates
- ✅ Process payments with Macedonia payment methods
- ✅ Export UBL 2.1 XML with digital signatures for tax compliance

### Phase 2: Accountant Console Multi-Client Management
- ✅ Login as partner user and access accountant console
- ✅ Display list of managed client companies with commission rates
- ✅ Switch between client companies successfully
- ✅ Validate context changes after company switch
- ✅ Repeat key operations in switched company context

### Phase 3: Company Switcher Integration
- ✅ Show partner companies in main company switcher
- ✅ Allow switching between own companies and partner clients
- ✅ Maintain session persistence across page navigation

### Phase 4: Complete Business Process Validation
- ✅ Validate complete invoice-to-payment-to-export flow in partner context
- ✅ Validate Macedonia-specific features throughout workflow

### Phase 5: Error Handling and Edge Cases
- ✅ Handle network failures gracefully
- ✅ Validate permissions in partner context
- ✅ Handle session timeout and re-authentication

### Phase 6: Performance and Load Validation
- ✅ Load pages within acceptable time limits (<5 seconds)
- ✅ Handle large datasets efficiently with pagination

## 🗃️ Files Structure

```
cypress/
├── e2e/
│   ├── smoke.cy.js              # Main comprehensive smoke test
│   ├── config-validation.cy.js  # Configuration validation
│   └── basic-test.cy.js         # Basic setup test
├── support/
│   ├── e2e.js                   # Test setup and configuration
│   └── commands.js              # Custom Cypress commands
├── fixtures/
│   └── macedonia-test-data.json # Macedonia-specific test data
└── README.md                    # This file
```

## 🔧 Configuration

### Environment Variables
- `ADMIN_EMAIL`: admin@invoiceshelf.com
- `ADMIN_PASSWORD`: password
- `PARTNER_EMAIL`: partner@accounting.mk
- `PARTNER_PASSWORD`: password
- `TEST_COMPANY_NAME`: Test Company Macedonia
- `TEST_CUSTOMER_NAME`: Test Customer

### Application Setup Required
```bash
# 1. Start Laravel application
php artisan serve --host=localhost --port=8000

# 2. Ensure database is seeded with partner data
php artisan db:seed --class=PartnerTablesSeeder

# 3. Create test partner user
# - Email: partner@accounting.mk
# - Password: password
# - Link to at least 2 companies with commission rates
```

## 🚀 Running Tests

### Prerequisites
```bash
# Install dependencies
npm install

# Ensure Laravel app is running on localhost:8000
php artisan serve
```

### Run Commands
```bash
# Run main smoke test
npm run test:e2e

# Run all E2E tests
npm run cypress:run

# Open Cypress GUI for debugging
npm run cypress:open

# Run specific test file
npx cypress run --spec cypress/e2e/smoke.cy.js
```

## 🏗️ Custom Commands

### Authentication Commands
- `cy.loginAsAdmin()` - Login as admin user
- `cy.loginAsPartner()` - Login as partner user

### Business Process Commands
- `cy.createTestCustomer(name)` - Create customer with Macedonia data
- `cy.createTestInvoice(customerName)` - Create invoice with items
- `cy.processPayment(invoiceId)` - Add payment to invoice
- `cy.exportInvoiceXML(invoiceId)` - Export as UBL XML with signature

### Accountant Console Commands
- `cy.accessAccountantConsole()` - Navigate to partner console
- `cy.switchCompanyInConsole(companyName)` - Switch client company
- `cy.waitForPageLoad()` - Wait for page loading completion

## 📊 Macedonia-Specific Test Data

The test suite includes comprehensive Macedonia business data:

### Companies
- Македонска Компанија ДОО (Skopje)
- Битолска Трговија АД (Bitola)

### Customers
- Охридски Ресторан ДООЕЛ (Ohrid)
- Струмичка Индустрија АД (Strumica)

### Services
- Консултантски Услуги (18% VAT)
- ИТ Поддршка (18% VAT)
- Обука за Персонал (5% VAT)

### Tax Validation
- Standard VAT: 18%
- Reduced VAT: 5%
- Currency: MKD (Macedonia Denar)
- Tax ID Format: MK40############# (13 digits)

## 🎯 Success Criteria

### Technical Validation
- ✅ Complete UI in Macedonian with Cyrillic text support
- ✅ Partner bureau multi-client workflow validation
- ✅ Invoice creation, payment processing, XML export integration
- ✅ Accountant console company switching with context validation
- ✅ Macedonia tax compliance (18%/5% VAT, MKD currency)

### Business Validation
- ✅ Partner bureaus can manage multiple client companies
- ✅ Complete business workflow in <10 minutes
- ✅ Digital signature XML export for tax authority compliance
- ✅ Commission tracking and partner context switching

## 🔍 Debugging

### Screenshots
Failed tests automatically capture screenshots in:
```
cypress/screenshots/
```

### Video Recording
Enable video recording in `cypress.config.js`:
```javascript
video: true
```

### Debug Mode
Run tests in headed mode for visual debugging:
```bash
npm run test:e2e:headed
```

## 🚨 Important Notes

### Partner Bureau Requirements
This smoke test validates the critical workflow that partner bureaus need for pilot testing:
1. **Multi-client management** - Essential for accounting firms
2. **Company context switching** - Core differentiator vs competitors
3. **Macedonia compliance** - Tax rates, currency, digital signatures
4. **Complete business flow** - From customer to payment to export

### CI/CD Integration
The smoke test is designed for CI environments:
- Headless execution by default
- Configurable timeouts
- Screenshot capture on failure
- No external dependencies beyond Laravel app

### Target: "CI Green"
When this test passes, it validates:
- ✅ Complete platform functionality
- ✅ Partner bureau readiness
- ✅ Macedonia market compliance
- ✅ End-to-end business workflow

## 📝 Implementation Notes

### For Future Claude
This implementation delivers exactly what SMK-01 specified:
- **Enhanced flow validation**: login→invoice→payment→export + accountant console switch
- **Partner bureau confidence**: Complete multi-client workflow testing
- **Macedonia compliance**: Cyrillic text, tax rates, currency, digital signatures
- **Production readiness**: Comprehensive error handling and edge case coverage

The accountant console switching validation is the **key differentiator** that positions this as the only platform in Macedonia with professional multi-client management for accounting firms.

