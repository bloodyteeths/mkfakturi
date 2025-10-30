// INS-03: Company Setup and Sample Data Seeding Test
// Tests company configuration step and validates sample data creation
// Created for Agent 2: Installation & Onboarding Flow Validator

describe('Installation Company Setup and Sample Data', () => {
  const macedoniaCompanyData = {
    name: 'Тест Компанија ООД',
    email: 'info@test-kompanija.mk',
    website: 'https://test-kompanija.mk',
    phone: '+389 2 3456 789',
    address_line_1: 'ул. Македонија 123',
    address_line_2: 'кат 2, стан 5',
    city: 'Скопје',
    state: 'Скопски регион',
    zip: '1000',
    country_id: 'MK',
    currency: 'MKD',
    tax_id: 'MK4080012345678',
    prefix: 'МК',
    due_amount: 7,
    invoice_color: '#2563eb',
    estimate_color: '#7c3aed'
  }

  const sampleDataExpected = {
    customers: {
      count: 5,
      examples: [
        'Битолска Банка АД',
        'Скопски Медицински Центар',
        'Универзитет Св. Кирил и Методиј'
      ]
    },
    invoices: {
      count: 10,
      totalAmount: 50000, // Minimum expected total
      currencies: ['MKD']
    },
    items: {
      count: 8,
      examples: [
        'Веб дизајн услуги',
        'ИТ консултации',
        'Софтверски развој'
      ]
    },
    payments: {
      count: 6,
      methods: ['cash', 'bank_transfer', 'check']
    }
  }

  let companyCreationTime = 0
  let sampleDataCreationTime = 0

  beforeEach(() => {
    // Setup for clean company setup test
    cy.clearCookies()
    cy.clearLocalStorage()
    
    // Ensure we have a fresh environment
    cy.task('resetDatabase', { preserveAdmin: true })
  })

  it('should complete company setup step successfully', () => {
    // Navigate to company setup step (typically step 7)
    navigateToCompanySetup()
    
    const startTime = Date.now()
    
    // Fill company information form
    fillCompanyForm(macedoniaCompanyData)
    
    // Validate form submission
    cy.get('[data-cy="company-save"], button:contains("Save"), button:contains("Continue")').click()
    cy.wait(3000)
    
    companyCreationTime = Date.now() - startTime
    
    // Verify company was created successfully
    validateCompanyCreation(macedoniaCompanyData)
    
    cy.log(`Company creation took ${companyCreationTime}ms`)
  })

  it('should create sample data during company preferences step', () => {
    // Complete installation up to company preferences (step 8)
    navigateToCompanyPreferences()
    
    const startTime = Date.now()
    
    // Enable sample data creation
    cy.get('[data-cy="enable-sample-data"], input[name="sample_data"], input[name="create_sample_data"]')
      .then(($checkbox) => {
        if ($checkbox.length > 0) {
          cy.wrap($checkbox).check()
        }
      })
    
    // Select Macedonia-specific sample data if option exists
    cy.get('[data-cy="sample-data-region"], select[name="sample_region"]')
      .then(($select) => {
        if ($select.length > 0) {
          cy.wrap($select).select('macedonia')
        }
      })
    
    // Complete installation
    cy.get('[data-cy="finish-installation"], button:contains("Finish"), button:contains("Complete Installation")').click()
    
    // Wait for sample data creation to complete
    cy.wait(10000, { log: false })
    
    sampleDataCreationTime = Date.now() - startTime
    
    // Validate sample data was created
    validateSampleDataCreation()
    
    cy.log(`Sample data creation took ${sampleDataCreationTime}ms`)
  })

  it('should validate company setup form validation', () => {
    navigateToCompanySetup()
    
    // Test required field validation
    cy.get('input[name="name"]').clear()
    cy.get('input[name="email"]').clear()
    cy.get('[data-cy="company-save"], button:contains("Save")').click()
    
    // Should show validation errors
    cy.get('.error, [role="alert"], .text-red').should('be.visible')
    cy.get('input[name="name"]').should('have.class', 'error').or('have.attr', 'aria-invalid', 'true')
    
    // Test email format validation
    cy.get('input[name="email"]').type('invalid-email')
    cy.get('[data-cy="company-save"], button:contains("Save")').click()
    cy.get('.error, [role="alert"]').should('contain.text', 'email').or('contain.text', 'valid')
    
    // Test phone format validation for Macedonia
    cy.get('input[name="phone"]').clear().type('123')
    cy.get('[data-cy="company-save"], button:contains("Save")').click()
    cy.get('.error, [role="alert"]').should('be.visible')
    
    // Fill valid data and verify validation passes
    fillCompanyForm(macedoniaCompanyData)
    cy.get('[data-cy="company-save"], button:contains("Save")').click()
    cy.get('.error, [role="alert"]').should('not.exist')
  })

  it('should handle Macedonia-specific company data correctly', () => {
    navigateToCompanySetup()
    
    // Test Cyrillic text support
    cy.get('input[name="name"]').type(macedoniaCompanyData.name)
    cy.get('input[name="name"]').should('have.value', macedoniaCompanyData.name)
    
    // Test Macedonia country selection
    cy.get('select[name="country_id"], [data-cy="country-select"]').select('MK')
    
    // Test MKD currency selection
    cy.get('select[name="currency"], [data-cy="currency-select"]').select('MKD')
    
    // Test Macedonia VAT ID format
    cy.get('input[name="tax_id"]').type(macedoniaCompanyData.tax_id)
    
    // Validate Macedonia phone number format
    cy.get('input[name="phone"]').type(macedoniaCompanyData.phone)
    
    // Submit and verify
    fillRemainingCompanyFields(macedoniaCompanyData)
    cy.get('[data-cy="company-save"], button:contains("Save")').click()
    cy.wait(2000)
    
    // Verify Macedonia-specific settings were applied
    validateMacedoniaSpecificSettings(macedoniaCompanyData)
  })

  it('should validate sample customers creation', () => {
    createSampleDataAndValidate()
    
    // Check specific customer creation via API
    cy.request({
      method: 'GET',
      url: '/api/v1/customers',
      headers: { 'Accept': 'application/json' }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.data).to.have.length.at.least(sampleDataExpected.customers.count)
      
      // Verify Macedonia-specific customer data
      const customers = response.body.data
      const macedonianCustomers = customers.filter(c => 
        c.name && (c.name.includes('Банка') || c.name.includes('ООД') || c.name.includes('АД'))
      )
      
      expect(macedonianCustomers.length).to.be.at.least(2, 'Should have Macedonia-specific sample customers')
      
      // Log customer details
      customers.forEach(customer => {
        cy.log(`Sample Customer: ${customer.name} - ${customer.email}`)
      })
    })
  })

  it('should validate sample invoices creation', () => {
    createSampleDataAndValidate()
    
    // Check invoice creation via API
    cy.request({
      method: 'GET',
      url: '/api/v1/invoices',
      headers: { 'Accept': 'application/json' }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.data).to.have.length.at.least(sampleDataExpected.invoices.count)
      
      const invoices = response.body.data
      let totalAmount = 0
      
      invoices.forEach(invoice => {
        expect(invoice.currency_id).to.be.oneOf(sampleDataExpected.invoices.currencies)
        totalAmount += parseInt(invoice.total)
        
        cy.log(`Sample Invoice: ${invoice.invoice_number} - ${invoice.total} ${invoice.currency_id}`)
      })
      
      expect(totalAmount).to.be.at.least(sampleDataExpected.invoices.totalAmount, 'Total invoice amount should meet minimum')
    })
  })

  it('should validate sample items creation', () => {
    createSampleDataAndValidate()
    
    // Check items creation via API
    cy.request({
      method: 'GET',
      url: '/api/v1/items',
      headers: { 'Accept': 'application/json' }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.data).to.have.length.at.least(sampleDataExpected.items.count)
      
      const items = response.body.data
      
      // Verify Macedonia-specific items exist
      const macedonianItems = items.filter(item => 
        item.name && (
          item.name.includes('услуги') || 
          item.name.includes('консултации') || 
          item.name.includes('развој')
        )
      )
      
      expect(macedonianItems.length).to.be.at.least(1, 'Should have Macedonia-specific sample items')
      
      items.forEach(item => {
        cy.log(`Sample Item: ${item.name} - ${item.price}`)
      })
    })
  })

  it('should validate sample payments creation', () => {
    createSampleDataAndValidate()
    
    // Check payments creation via API
    cy.request({
      method: 'GET',
      url: '/api/v1/payments',
      headers: { 'Accept': 'application/json' }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.data).to.have.length.at.least(sampleDataExpected.payments.count)
      
      const payments = response.body.data
      const paymentMethods = payments.map(p => p.payment_method).filter(Boolean)
      
      sampleDataExpected.payments.methods.forEach(method => {
        const hasMethod = paymentMethods.some(pm => pm.toLowerCase().includes(method))
        expect(hasMethod, `Should have ${method} payment method`).to.be.true
      })
      
      payments.forEach(payment => {
        cy.log(`Sample Payment: ${payment.amount} ${payment.currency_id} via ${payment.payment_method}`)
      })
    })
  })

  // Helper Functions

  function navigateToCompanySetup() {
    cy.log('Navigating to company setup step')
    
    // Either visit installation directly or navigate through steps
    cy.visit('/installation')
    
    // Quick navigation to company setup (step 7)
    // This assumes we can skip previous steps or they're already completed
    cy.url().then((url) => {
      if (url.includes('/installation')) {
        // Skip through steps quickly to reach company setup
        for (let i = 0; i < 7; i++) {
          cy.get('body').then(($body) => {
            if ($body.find('[data-cy="wizard-next"], button:contains("Continue"), button:contains("Next")').length > 0) {
              cy.get('[data-cy="wizard-next"], button:contains("Continue"), button:contains("Next")').first().click()
              cy.wait(1000)
            }
          })
        }
      }
    })
    
    // Verify we're on company setup step
    cy.get('body').should('contain.text', 'Company').or('contain.text', 'Organization')
    cy.screenshot('company-setup-step')
  }

  function navigateToCompanyPreferences() {
    cy.log('Navigating to company preferences step')
    
    // Navigate through all steps to reach preferences (step 8)
    navigateToCompanySetup()
    
    // Fill minimal company data to proceed
    fillCompanyForm(macedoniaCompanyData)
    cy.get('[data-cy="wizard-next"], button:contains("Continue")').click()
    cy.wait(2000)
    
    // Should now be on company preferences step
    cy.get('body').should('contain.text', 'Preferences').or('contain.text', 'Settings').or('contain.text', 'Sample')
    cy.screenshot('company-preferences-step')
  }

  function fillCompanyForm(companyData) {
    cy.log('Filling company form with Macedonia data')
    
    // Basic company information
    cy.get('input[name="name"]').clear().type(companyData.name)
    cy.get('input[name="email"]').clear().type(companyData.email)
    
    if (companyData.website) {
      cy.get('input[name="website"]').then(($input) => {
        if ($input.length > 0) {
          cy.wrap($input).clear().type(companyData.website)
        }
      })
    }
    
    cy.get('input[name="phone"]').clear().type(companyData.phone)
    
    // Address information
    cy.get('input[name="address_line_1"], textarea[name="address"]').clear().type(companyData.address_line_1)
    
    cy.get('input[name="city"]').then(($input) => {
      if ($input.length > 0) {
        cy.wrap($input).clear().type(companyData.city)
      }
    })
    
    cy.get('input[name="zip"]').then(($input) => {
      if ($input.length > 0) {
        cy.wrap($input).clear().type(companyData.zip)
      }
    })
    
    // Country and currency
    cy.get('select[name="country_id"], [data-cy="country-select"]').then(($select) => {
      if ($select.length > 0) {
        cy.wrap($select).select(companyData.country_id)
      }
    })
    
    cy.get('select[name="currency"], [data-cy="currency-select"]').then(($select) => {
      if ($select.length > 0) {
        cy.wrap($select).select(companyData.currency)
      }
    })
    
    // Tax ID for Macedonia
    cy.get('input[name="tax_id"]').then(($input) => {
      if ($input.length > 0) {
        cy.wrap($input).clear().type(companyData.tax_id)
      }
    })
  }

  function fillRemainingCompanyFields(companyData) {
    // Fill any remaining optional fields
    cy.get('input[name="prefix"]').then(($input) => {
      if ($input.length > 0) {
        cy.wrap($input).clear().type(companyData.prefix)
      }
    })
    
    cy.get('input[name="due_amount"]').then(($input) => {
      if ($input.length > 0) {
        cy.wrap($input).clear().type(companyData.due_amount.toString())
      }
    })
  }

  function validateCompanyCreation(companyData) {
    cy.log('Validating company was created successfully')
    
    // Check via API that company was created
    cy.request({
      method: 'GET',
      url: '/api/v1/company',
      headers: { 'Accept': 'application/json' }
    }).then((response) => {
      expect(response.status).to.eq(200)
      expect(response.body.name).to.eq(companyData.name)
      expect(response.body.email).to.eq(companyData.email)
      expect(response.body.currency).to.eq(companyData.currency)
      
      cy.log(`Company created: ${response.body.name}`)
    })
  }

  function validateMacedoniaSpecificSettings(companyData) {
    cy.log('Validating Macedonia-specific company settings')
    
    cy.request({
      method: 'GET',
      url: '/api/v1/company',
      headers: { 'Accept': 'application/json' }
    }).then((response) => {
      expect(response.body.country_id).to.eq('MK')
      expect(response.body.currency).to.eq('MKD')
      expect(response.body.tax_id).to.eq(companyData.tax_id)
      
      // Verify Cyrillic text was saved correctly
      expect(response.body.name).to.include('Тест')
      
      cy.log('Macedonia-specific settings validated successfully')
    })
  }

  function createSampleDataAndValidate() {
    cy.log('Creating sample data and validating results')
    
    // Navigate to preferences and enable sample data
    navigateToCompanyPreferences()
    
    cy.get('[data-cy="enable-sample-data"], input[name="sample_data"]').then(($checkbox) => {
      if ($checkbox.length > 0) {
        cy.wrap($checkbox).check()
      }
    })
    
    cy.get('[data-cy="finish-installation"], button:contains("Finish")').click()
    cy.wait(8000) // Wait for sample data creation
    
    // Visit main app to verify completion
    cy.visit('/')
    cy.url().should('not.include', '/installation')
  }

  function validateSampleDataCreation() {
    cy.log('Validating that sample data was created correctly')
    
    // Verify we can access the main application
    cy.visit('/')
    cy.url().should('not.include', '/installation')
    
    // Check dashboard shows data
    cy.get('body').should('contain.text', 'Dashboard').or('contain.text', 'Total').or('contain.text', 'Invoice')
    
    // Look for sample data indicators
    cy.get('[data-cy="customer-count"], [data-cy="total-customers"]').then(($el) => {
      if ($el.length > 0) {
        const customerCount = parseInt($el.text())
        expect(customerCount).to.be.at.least(sampleDataExpected.customers.count)
      }
    })
    
    cy.get('[data-cy="invoice-count"], [data-cy="total-invoices"]').then(($el) => {
      if ($el.length > 0) {
        const invoiceCount = parseInt($el.text())
        expect(invoiceCount).to.be.at.least(sampleDataExpected.invoices.count)
      }
    })
    
    cy.screenshot('sample-data-created')
    cy.log('Sample data validation completed')
  }
})

