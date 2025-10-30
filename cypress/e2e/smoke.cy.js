/**
 * SMK-01: Comprehensive Smoke Test
 * Enhanced flow: login→invoice→payment→export + accountant console switch
 * 
 * This test validates the complete business workflow from login to export
 * including accountant console company switching validation and partner
 * bureau multi-client workflow testing.
 * 
 * Target: "CI green" with end-to-end validation of complete workflow
 * including partner features for accountant bureau confidence.
 */

describe('Comprehensive Smoke Test - Complete Business Workflow', () => {
  let testCustomerName
  let testInvoiceId
  let partnerCompanyName

  before(() => {
    // Set up test data
    testCustomerName = `Smoke Test Customer ${Date.now()}`
    partnerCompanyName = 'Partner Test Company MK'
  })

  beforeEach(() => {
    // Ensure clean state and wait for page load
    cy.clearCookies()
    cy.clearLocalStorage()
  })

  describe('Phase 1: Admin User Complete Workflow', () => {
    it('should login as admin user successfully', () => {
      // Enhanced login with better error handling
      cy.visit('/admin/auth/login', { failOnStatusCode: false })
      
      // Check if login page loaded or if already logged in
      cy.url().then((url) => {
        if (url.includes('/admin/auth/login')) {
          cy.get('input[name="email"]', { timeout: 10000 }).should('be.visible').type(Cypress.env('ADMIN_EMAIL'))
          cy.get('input[name="password"]').type(Cypress.env('ADMIN_PASSWORD'))
          cy.get('button[type="submit"]').click()
          cy.url({ timeout: 15000 }).should('include', '/admin/dashboard')
        }
      })
      
      // Verify dashboard elements with Macedonia context
      cy.contains('Dashboard', { timeout: 10000 }).should('be.visible')
      cy.log('✅ Admin login successful - dashboard loaded')
    })

    it('should create a test customer with Macedonia-specific data', () => {
      cy.loginAsAdmin()
      cy.createTestCustomer(testCustomerName)
      
      // Verify customer was created
      cy.visit('/admin/customers')
      cy.contains(testCustomerName).should('be.visible')
      cy.contains('Скопје').should('be.visible') // Verify Macedonia address
    })

    it('should create an invoice for the test customer', () => {
      cy.loginAsAdmin()
      cy.createTestInvoice(testCustomerName).then((invoiceId) => {
        testInvoiceId = invoiceId
        
        // Verify invoice details
        cy.visit(`/admin/invoices/${invoiceId}`)
        cy.contains(testCustomerName).should('be.visible')
        cy.contains('Test Service').should('be.visible')
        cy.contains('1,500.00').should('be.visible') // Macedonia formatting
        cy.contains('MKD').should('be.visible') // Macedonia currency
      })
    })

    it('should process payment for the invoice', () => {
      cy.loginAsAdmin()
      cy.processPayment(testInvoiceId)
      
      // Verify payment was recorded
      cy.visit(`/admin/invoices/${testInvoiceId}`)
      cy.contains('PAID').should('be.visible')
      cy.get('[data-cy="payment-history"]').should('contain', '1,500.00')
    })

    it('should export invoice as UBL XML with digital signature', () => {
      cy.loginAsAdmin()
      cy.exportInvoiceXML(testInvoiceId)
      
      // Verify export success message
      cy.contains('XML export completed').should('be.visible')
      
      // Verify Macedonia-specific UBL elements would be present
      // (actual XML validation would require downloading and parsing)
      cy.contains('UBL 2.1').should('be.visible')
      cy.contains('Digital signature applied').should('be.visible')
    })
  })

  describe('Phase 2: Accountant Console Multi-Client Management', () => {
    it('should login as partner user and access accountant console', () => {
      cy.loginAsPartner()
      cy.accessAccountantConsole()
      
      // Verify accountant console interface
      cy.contains('Контролна Табла').should('be.visible') // Macedonian "Dashboard"
      cy.get('[data-cy="client-companies"]').should('be.visible')
      cy.get('[data-cy="partner-stats"]').should('be.visible')
    })

    it('should display list of managed client companies', () => {
      cy.loginAsPartner()
      cy.visit('/admin/console')
      
      // Verify company list is displayed
      cy.get('[data-cy="company-card"]').should('have.length.at.least', 1)
      cy.contains('Primary').should('be.visible') // Primary company badge
      cy.contains('%').should('be.visible') // Commission rate display
      
      // Verify Macedonia-specific company information
      cy.contains('Скопје').should('be.visible') // Macedonia addresses
      cy.contains('MK').should('be.visible') // Macedonia country code
    })

    it('should switch between client companies successfully', () => {
      cy.loginAsPartner()
      cy.visit('/admin/console')
      
      // Get first available company name for switching
      cy.get('[data-cy="company-card"]').first().within(() => {
        cy.get('[data-cy="company-name"]').invoke('text').then((companyName) => {
          partnerCompanyName = companyName.trim()
          
          // Perform company switch
          cy.get('[data-cy="switch-company"]').click()
        })
      })
      
      // Verify context switch
      cy.contains('Company switched successfully').should('be.visible')
      cy.get('[data-cy="current-company"]').should('contain', partnerCompanyName)
    })

    it('should validate context changes after company switch', () => {
      cy.loginAsPartner()
      cy.visit('/admin/console')
      
      // Switch to a client company
      cy.get('[data-cy="company-card"]').first().within(() => {
        cy.get('[data-cy="switch-company"]').click()
      })
      
      // Navigate to invoices and verify context
      cy.visit('/admin/invoices')
      cy.get('[data-cy="company-context"]').should('be.visible')
      
      // Verify that data is scoped to the selected company
      cy.get('[data-cy="invoice-list"]').should('be.visible')
      cy.get('[data-cy="company-badge"]').should('contain', 'Partner Client')
    })

    it('should repeat key operations in switched company context', () => {
      cy.loginAsPartner()
      cy.visit('/admin/console')
      
      // Switch company
      cy.get('[data-cy="company-card"]').first().within(() => {
        cy.get('[data-cy="switch-company"]').click()
      })
      
      // Create customer in partner context
      const partnerCustomerName = `Partner Customer ${Date.now()}`
      cy.createTestCustomer(partnerCustomerName)
      
      // Verify customer is created in correct company context
      cy.visit('/admin/customers')
      cy.contains(partnerCustomerName).should('be.visible')
      cy.get('[data-cy="company-context"]').should('contain', 'Partner')
      
      // Create invoice in partner context
      cy.createTestInvoice(partnerCustomerName).then((partnerInvoiceId) => {
        // Verify invoice is in partner context
        cy.visit(`/admin/invoices/${partnerInvoiceId}`)
        cy.get('[data-cy="company-context"]').should('contain', 'Partner')
        cy.contains(partnerCustomerName).should('be.visible')
      })
    })
  })

  describe('Phase 3: Company Switcher Integration Validation', () => {
    it('should show partner companies in main company switcher', () => {
      cy.loginAsPartner()
      cy.visit('/admin/dashboard')
      
      // Click main company switcher
      cy.get('[data-cy="company-switcher"]').click()
      
      // Verify partner section is visible
      cy.get('[data-cy="partner-companies"]').should('be.visible')
      cy.contains('Partner Clients').should('be.visible')
      
      // Verify commission rates and badges
      cy.get('[data-cy="partner-company"]').should('have.length.at.least', 1)
      cy.contains('%').should('be.visible') // Commission rate
      cy.contains('Primary').should('be.visible') // Primary badge
    })

    it('should allow switching between own companies and partner clients', () => {
      cy.loginAsPartner()
      cy.visit('/admin/dashboard')
      
      // Switch to partner client via main switcher
      cy.get('[data-cy="company-switcher"]').click()
      cy.get('[data-cy="partner-companies"]').within(() => {
        cy.get('[data-cy="partner-company"]').first().click()
      })
      
      // Verify switch occurred
      cy.contains('Company switched successfully').should('be.visible')
      cy.get('[data-cy="current-company"]').should('contain', 'Partner')
      
      // Verify dashboard shows partner company data
      cy.get('[data-cy="dashboard-stats"]').should('be.visible')
      cy.get('[data-cy="company-badge"]').should('contain', 'Partner Client')
    })

    it('should maintain session persistence across page navigation', () => {
      cy.loginAsPartner()
      cy.visit('/admin/console')
      
      // Switch to partner company
      cy.get('[data-cy="company-card"]').first().within(() => {
        cy.get('[data-cy="switch-company"]').click()
      })
      
      // Navigate to different pages and verify context persists
      const pages = ['/admin/dashboard', '/admin/customers', '/admin/invoices', '/admin/payments']
      
      pages.forEach((page) => {
        cy.visit(page)
        cy.get('[data-cy="company-context"]').should('be.visible')
        cy.get('[data-cy="company-badge"]').should('contain', 'Partner')
      })
    })
  })

  describe('Phase 4: Complete Business Process Validation', () => {
    it('should validate complete invoice-to-payment-to-export flow in partner context', () => {
      cy.loginAsPartner()
      cy.visit('/admin/console')
      
      // Switch to partner company
      cy.get('[data-cy="company-card"]').first().within(() => {
        cy.get('[data-cy="switch-company"]').click()
      })
      
      // Complete business flow
      const businessCustomer = `Business Customer ${Date.now()}`
      
      // 1. Create customer
      cy.createTestCustomer(businessCustomer)
      
      // 2. Create invoice
      cy.createTestInvoice(businessCustomer).then((businessInvoiceId) => {
        
        // 3. Process payment
        cy.processPayment(businessInvoiceId)
        
        // 4. Export XML
        cy.exportInvoiceXML(businessInvoiceId)
        
        // 5. Verify complete workflow in partner context
        cy.visit(`/admin/invoices/${businessInvoiceId}`)
        cy.contains('PAID').should('be.visible')
        cy.get('[data-cy="company-context"]').should('contain', 'Partner')
        cy.contains('XML export completed').should('be.visible')
      })
    })

    it('should validate Macedonia-specific features throughout workflow', () => {
      cy.loginAsAdmin()
      
      // Create Macedonia-specific customer
      cy.visit('/admin/customers')
      cy.get('[data-cy="add-customer"]').click()
      
      cy.get('input[name="name"]').type('Македонска Компанија ДОО')
      cy.get('input[name="email"]').type('kontakt@kompanija.mk')
      cy.get('input[name="phone"]').type('+38970123456')
      cy.get('input[name="tax_id"]').type('MK4080003501411') // Macedonia VAT format
      
      // Macedonia address
      cy.get('input[name="address_street_1"]').type('бул. Македонија 15')
      cy.get('input[name="city"]').type('Скопје')
      cy.get('input[name="zip"]').type('1000')
      cy.get('select[name="country"]').select('Macedonia')
      
      cy.get('button[type="submit"]').click()
      
      // Create invoice with Macedonia tax rates
      cy.createTestInvoice('Македонска Компанија ДОО').then((mkInvoiceId) => {
        cy.visit(`/admin/invoices/${mkInvoiceId}`)
        
        // Verify Macedonia-specific elements
        cy.contains('MKD').should('be.visible') // Macedonia currency
        cy.contains('18%').should('be.visible') // Standard VAT rate
        cy.contains('ДДВ').should('be.visible') // Macedonia VAT label
        cy.contains('Македонска').should('be.visible') // Cyrillic text
        
        // Process payment and verify
        cy.processPayment(mkInvoiceId)
        cy.contains('PAID').should('be.visible')
        
        // Export XML and verify Macedonia compliance
        cy.exportInvoiceXML(mkInvoiceId)
        cy.contains('UBL 2.1').should('be.visible')
        cy.contains('MK').should('be.visible') // Country code in XML
      })
    })
  })

  describe('Phase 5: Error Handling and Edge Cases', () => {
    it('should handle network failures gracefully', () => {
      cy.loginAsAdmin()
      
      // Simulate network failure during operations
      cy.intercept('POST', '/api/v1/invoices', { forceNetworkError: true }).as('networkFailure')
      
      cy.visit('/admin/invoices')
      cy.get('[data-cy="add-invoice"]').click()
      
      // Attempt to create invoice (will fail)
      cy.get('[data-cy="customer-select"]').click()
      cy.contains(testCustomerName).click()
      cy.get('button[type="submit"]').click()
      
      // Verify error handling
      cy.contains('Network error').should('be.visible')
      cy.get('[data-cy="error-message"]').should('be.visible')
    })

    it('should validate permissions in partner context', () => {
      cy.loginAsPartner()
      cy.visit('/admin/console')
      
      // Switch to partner company
      cy.get('[data-cy="company-card"]').first().within(() => {
        cy.get('[data-cy="switch-company"]').click()
      })
      
      // Verify partner has appropriate permissions
      cy.visit('/admin/invoices')
      cy.get('[data-cy="add-invoice"]').should('be.visible') // Can create
      
      // Verify restricted access (e.g., settings)
      cy.visit('/admin/settings/company')
      cy.contains('Access denied').should('be.visible')
    })

    it('should handle session timeout and re-authentication', () => {
      cy.loginAsAdmin()
      
      // Clear session to simulate timeout
      cy.clearCookies()
      cy.clearLocalStorage()
      
      // Attempt to access protected resource
      cy.visit('/admin/dashboard')
      
      // Should redirect to login
      cy.url().should('include', '/admin/auth/login')
      cy.contains('Login').should('be.visible')
    })
  })

  describe('Phase 6: Performance and Load Validation', () => {
    it('should load pages within acceptable time limits', () => {
      cy.loginAsAdmin()
      
      const pages = [
        '/admin/dashboard',
        '/admin/customers',
        '/admin/invoices',
        '/admin/payments',
        '/admin/console'
      ]
      
      pages.forEach((page) => {
        const start = Date.now()
        cy.visit(page)
        cy.waitForPageLoad()
        cy.then(() => {
          const loadTime = Date.now() - start
          expect(loadTime).to.be.lessThan(5000) // 5 second max load time
        })
      })
    })

    it('should handle large datasets efficiently', () => {
      cy.loginAsAdmin()
      
      // Test with pagination
      cy.visit('/admin/invoices')
      cy.get('[data-cy="pagination"]').should('be.visible')
      
      // Test search functionality
      cy.get('[data-cy="search-input"]').type('test')
      cy.get('[data-cy="search-results"]').should('be.visible')
      
      // Clear search
      cy.get('[data-cy="clear-search"]').click()
      cy.get('[data-cy="invoice-list"]').should('be.visible')
    })
  })

  after(() => {
    // Cleanup test data
    cy.loginAsAdmin()
    
    // Delete test customer and associated data
    cy.visit('/admin/customers')
    cy.contains(testCustomerName).parent().within(() => {
      cy.get('[data-cy="delete-customer"]').click()
    })
    cy.get('[data-cy="confirm-delete"]').click()
    cy.contains('Customer deleted successfully').should('be.visible')
  })
})

