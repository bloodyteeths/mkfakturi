// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************

// Custom commands for InvoiceShelf application

/**
 * Login as admin user
 */
Cypress.Commands.add('loginAsAdmin', () => {
  cy.session('admin-login', () => {
    cy.visit('/admin/auth/login', { failOnStatusCode: false })
    
    cy.url().then((url) => {
      if (url.includes('/admin/auth/login')) {
        cy.get('input[name="email"]', { timeout: 10000 }).should('be.visible').type(Cypress.env('ADMIN_EMAIL'))
        cy.get('input[name="password"]').type(Cypress.env('ADMIN_PASSWORD'))
        cy.get('button[type="submit"]').click()
      }
    })
    
    cy.url({ timeout: 15000 }).should('include', '/admin')
    cy.contains('Dashboard', { timeout: 10000 }).should('be.visible')
  })
})

/**
 * Login as partner user
 */
Cypress.Commands.add('loginAsPartner', () => {
  cy.session('partner-login', () => {
    cy.visit('/admin/auth/login', { failOnStatusCode: false })
    
    cy.url().then((url) => {
      if (url.includes('/admin/auth/login')) {
        cy.get('input[name="email"]', { timeout: 10000 }).should('be.visible').type(Cypress.env('PARTNER_EMAIL'))
        cy.get('input[name="password"]').type(Cypress.env('PARTNER_PASSWORD'))
        cy.get('button[type="submit"]').click()
      }
    })
    
    cy.url({ timeout: 15000 }).should('include', '/admin')
    cy.contains('Dashboard', { timeout: 10000 }).should('be.visible')
  })
})

/**
 * Create a test customer
 */
Cypress.Commands.add('createTestCustomer', (customerName = null) => {
  const name = customerName || Cypress.env('TEST_CUSTOMER_NAME')
  
  cy.visit('/admin/customers')
  cy.get('[data-cy="add-customer"]').click()
  
  cy.get('input[name="name"]').type(name)
  cy.get('input[name="email"]').type(`${name.toLowerCase().replace(' ', '.')}@test.mk`)
  cy.get('input[name="phone"]').type('+38970123456')
  
  // Address fields
  cy.get('input[name="address_street_1"]').type('ул. Македонија 1')
  cy.get('input[name="city"]').type('Скопје')
  cy.get('input[name="zip"]').type('1000')
  
  cy.get('button[type="submit"]').click()
  cy.contains('Customer created successfully').should('be.visible')
  
  return cy.wrap(name)
})

/**
 * Create a test invoice
 */
Cypress.Commands.add('createTestInvoice', (customerName = null) => {
  const customer = customerName || Cypress.env('TEST_CUSTOMER_NAME')
  
  cy.visit('/admin/invoices')
  cy.get('[data-cy="add-invoice"]').click()
  
  // Select customer
  cy.get('[data-cy="customer-select"]').click()
  cy.contains(customer).click()
  
  // Add invoice item
  cy.get('[data-cy="add-item"]').click()
  cy.get('input[name="items[0][name]"]').type('Test Service')
  cy.get('input[name="items[0][description]"]').type('Professional consulting services')
  cy.get('input[name="items[0][quantity]"]').clear().type('1')
  cy.get('input[name="items[0][price]"]').clear().type('1500')
  
  // Save invoice
  cy.get('button[type="submit"]').click()
  cy.contains('Invoice created successfully').should('be.visible')
  
  // Get invoice number for reference
  cy.url().then((url) => {
    const invoiceId = url.split('/').pop()
    return cy.wrap(invoiceId)
  })
})

/**
 * Process payment for invoice
 */
Cypress.Commands.add('processPayment', (invoiceId) => {
  cy.visit(`/admin/invoices/${invoiceId}`)
  
  // Click add payment
  cy.get('[data-cy="add-payment"]').click()
  
  // Fill payment details
  cy.get('input[name="amount"]').clear().type('1500')
  cy.get('select[name="payment_method_id"]').select('Bank Transfer')
  cy.get('input[name="reference_number"]').type('PAY-' + Date.now())
  cy.get('textarea[name="notes"]').type('Test payment via Cypress')
  
  // Save payment
  cy.get('button[type="submit"]').click()
  cy.contains('Payment created successfully').should('be.visible')
})

/**
 * Export invoice as XML
 */
Cypress.Commands.add('exportInvoiceXML', (invoiceId) => {
  cy.visit(`/admin/invoices/${invoiceId}`)
  
  // Click export dropdown
  cy.get('[data-cy="export-dropdown"]').click()
  cy.get('[data-cy="export-xml"]').click()
  
  // Select export options
  cy.get('select[name="format"]').select('UBL 2.1 XML')
  cy.get('input[name="digital_signature"]').check()
  cy.get('input[name="validate"]').check()
  
  // Download XML
  cy.get('button[data-cy="download-xml"]').click()
  
  // Verify download (check for download in downloads folder would require additional setup)
  cy.contains('XML export completed').should('be.visible')
})

/**
 * Switch company in accountant console
 */
Cypress.Commands.add('switchCompanyInConsole', (companyName) => {
  // Click company switcher
  cy.get('[data-cy="company-switcher"]').click()
  
  // Look for partner companies section
  cy.get('[data-cy="partner-companies"]').should('be.visible')
  
  // Click on target company
  cy.contains(companyName).click()
  
  // Verify company switch
  cy.get('[data-cy="current-company"]').should('contain', companyName)
  cy.contains('Company switched successfully').should('be.visible')
})

/**
 * Access accountant console
 */
Cypress.Commands.add('accessAccountantConsole', () => {
  cy.visit('/admin/console')
  cy.contains('Контролна Табла').should('be.visible')
  cy.get('[data-cy="client-companies"]').should('be.visible')
})

/**
 * Wait for page to load completely
 */
Cypress.Commands.add('waitForPageLoad', () => {
  cy.get('[data-cy="page-loader"]').should('not.exist')
  cy.get('body').should('be.visible')
})

