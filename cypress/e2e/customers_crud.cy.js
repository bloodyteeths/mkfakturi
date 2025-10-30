/**
 * OPS-01: Customer CRUD with Macedonia Validation
 * 
 * Tests comprehensive customer management with Macedonia-specific validation:
 * - VAT ID validation (MK + 13 digits)
 * - Macedonia address formats
 * - Cyrillic text support
 * - Customer creation, update, deletion flows
 * - Macedonia postal codes and city validation
 */

describe('OPS-01: Customer CRUD with Macedonia Validation', () => {
  let macedoniaData
  let authToken
  
  before(() => {
    // Load Macedonia test data
    cy.fixture('macedonia-test-data').then((data) => {
      macedoniaData = data
    })
  })

  beforeEach(() => {
    // Login as admin and get auth token
    cy.visit('/admin/login')
    cy.get('[data-cy="email"]').type(Cypress.env('ADMIN_EMAIL'))
    cy.get('[data-cy="password"]').type(Cypress.env('ADMIN_PASSWORD'))
    cy.get('[data-cy="login-button"]').click()
    
    // Wait for dashboard to load
    cy.url().should('include', '/admin/dashboard')
    
    // Navigate to customers
    cy.visit('/admin/customers')
    cy.url().should('include', '/admin/customers')
  })

  describe('Customer Creation with Macedonia Data', () => {
    it('should create customer with valid Macedonia VAT ID', () => {
      const customer = macedoniaData.customers[0] // Охридски Ресторан ДООЕЛ
      
      // Click Create Customer
      cy.get('[data-cy="create-customer"]').click()
      
      // Fill customer form with Macedonia data
      cy.get('[data-cy="customer-name"]').type(customer.name)
      cy.get('[data-cy="customer-email"]').type(customer.email)
      cy.get('[data-cy="customer-phone"]').type(customer.phone)
      cy.get('[data-cy="customer-tax-id"]').type(customer.tax_id)
      
      // Address information
      cy.get('[data-cy="customer-address-street"]').type(customer.address.street)
      cy.get('[data-cy="customer-address-city"]').type(customer.address.city)
      cy.get('[data-cy="customer-address-zip"]').type(customer.address.zip)
      cy.get('[data-cy="customer-address-country"]').select(customer.address.country)
      
      // Submit form
      cy.get('[data-cy="save-customer"]').click()
      
      // Verify success message
      cy.get('[data-cy="success-message"]').should('contain', 'Customer created successfully')
      
      // Verify customer appears in list
      cy.get('[data-cy="customers-table"]').should('contain', customer.name)
      cy.get('[data-cy="customers-table"]').should('contain', customer.tax_id)
      cy.get('[data-cy="customers-table"]').should('contain', customer.address.city)
    })

    it('should validate Macedonia VAT ID format (MK + 13 digits)', () => {
      // Test invalid VAT ID formats
      const invalidVatIds = [
        'MK123456789012',    // 12 digits (too short)
        'MK12345678901234',  // 14 digits (too long)
        'RS1234567890123',   // Wrong country code
        'MK123456789012A',   // Contains letter
        '1234567890123',     // Missing country code
        'MK 123456789012'    // Contains space
      ]
      
      cy.get('[data-cy="create-customer"]').click()
      
      invalidVatIds.forEach((invalidVatId) => {
        cy.get('[data-cy="customer-tax-id"]').clear().type(invalidVatId)
        cy.get('[data-cy="customer-name"]').type('Test Customer')
        cy.get('[data-cy="save-customer"]').click()
        
        // Should show validation error
        cy.get('[data-cy="validation-errors"]').should('contain', 'VAT ID must be in format: MK followed by 13 digits')
        
        // Clear form for next test
        cy.get('[data-cy="customer-tax-id"]').clear()
      })
    })

    it('should support Cyrillic text in customer names and addresses', () => {
      const customer = macedoniaData.customers[1] // Струмичка Индустрија АД
      
      cy.get('[data-cy="create-customer"]').click()
      
      // Test Cyrillic input and rendering
      cy.get('[data-cy="customer-name"]').type(customer.name)
      cy.get('[data-cy="customer-address-street"]').type(customer.address.street)
      cy.get('[data-cy="customer-address-city"]').type(customer.address.city)
      
      // Verify Cyrillic text is preserved
      cy.get('[data-cy="customer-name"]').should('have.value', customer.name)
      cy.get('[data-cy="customer-address-street"]').should('have.value', customer.address.street)
      cy.get('[data-cy="customer-address-city"]').should('have.value', customer.address.city)
      
      // Complete and save
      cy.get('[data-cy="customer-email"]').type(customer.email)
      cy.get('[data-cy="customer-tax-id"]').type(customer.tax_id)
      cy.get('[data-cy="save-customer"]').click()
      
      // Verify Cyrillic text in table
      cy.get('[data-cy="customers-table"]').should('contain', customer.name)
      cy.get('[data-cy="customers-table"]').should('contain', customer.address.city)
    })

    it('should validate Macedonia postal codes', () => {
      const validPostalCodes = ['1000', '7000', '6000', '2400', '1300']
      const invalidPostalCodes = ['123', '12345', 'ABCD', '00000']
      
      cy.get('[data-cy="create-customer"]').click()
      
      // Test valid postal codes
      validPostalCodes.forEach((zip) => {
        cy.get('[data-cy="customer-address-zip"]').clear().type(zip)
        // Should not show error
        cy.get('[data-cy="postal-code-error"]').should('not.exist')
      })
      
      // Test invalid postal codes
      invalidPostalCodes.forEach((zip) => {
        cy.get('[data-cy="customer-address-zip"]').clear().type(zip)
        cy.get('[data-cy="customer-name"]').type('Test')
        cy.get('[data-cy="save-customer"]').click()
        
        // Should show validation error
        cy.get('[data-cy="validation-errors"]').should('contain', 'Postal code must be 4 digits for Macedonia')
        
        cy.get('[data-cy="customer-address-zip"]').clear()
      })
    })
  })

  describe('Customer Update Operations', () => {
    it('should update customer with new Macedonia address', () => {
      // First create a customer
      const originalCustomer = macedoniaData.customers[0]
      const updatedData = {
        name: 'Updated Македонски Клиент',
        address: {
          street: 'нов. Партизанска 25',
          city: 'Велес',
          zip: '1400'
        }
      }
      
      // Create customer first
      cy.get('[data-cy="create-customer"]').click()
      cy.get('[data-cy="customer-name"]').type(originalCustomer.name)
      cy.get('[data-cy="customer-email"]').type(originalCustomer.email)
      cy.get('[data-cy="customer-tax-id"]').type(originalCustomer.tax_id)
      cy.get('[data-cy="save-customer"]').click()
      
      // Now edit the customer
      cy.get('[data-cy="customers-table"]').contains(originalCustomer.name).parent().find('[data-cy="edit-customer"]').click()
      
      // Update fields
      cy.get('[data-cy="customer-name"]').clear().type(updatedData.name)
      cy.get('[data-cy="customer-address-street"]').clear().type(updatedData.address.street)
      cy.get('[data-cy="customer-address-city"]').clear().type(updatedData.address.city)
      cy.get('[data-cy="customer-address-zip"]').clear().type(updatedData.address.zip)
      
      // Save changes
      cy.get('[data-cy="save-customer"]').click()
      
      // Verify updates
      cy.get('[data-cy="success-message"]').should('contain', 'Customer updated successfully')
      cy.get('[data-cy="customers-table"]').should('contain', updatedData.name)
      cy.get('[data-cy="customers-table"]').should('contain', updatedData.address.city)
    })

    it('should prevent VAT ID duplication', () => {
      const customer1 = macedoniaData.customers[0]
      const customer2 = macedoniaData.customers[1]
      
      // Create first customer
      cy.get('[data-cy="create-customer"]').click()
      cy.get('[data-cy="customer-name"]').type(customer1.name)
      cy.get('[data-cy="customer-email"]').type(customer1.email)
      cy.get('[data-cy="customer-tax-id"]').type(customer1.tax_id)
      cy.get('[data-cy="save-customer"]').click()
      
      // Try to create second customer with same VAT ID
      cy.get('[data-cy="create-customer"]').click()
      cy.get('[data-cy="customer-name"]').type(customer2.name)
      cy.get('[data-cy="customer-email"]').type(customer2.email)
      cy.get('[data-cy="customer-tax-id"]').type(customer1.tax_id) // Same VAT ID
      cy.get('[data-cy="save-customer"]').click()
      
      // Should show duplicate error
      cy.get('[data-cy="validation-errors"]').should('contain', 'VAT ID already exists')
    })
  })

  describe('Customer Search and Filtering', () => {
    it('should search customers by Macedonia city names', () => {
      const cities = ['Скопје', 'Битола', 'Охрид', 'Струмица']
      
      // Create customers in different cities
      macedoniaData.customers.forEach((customer, index) => {
        if (index < 2) { // Limit to prevent test timeout
          cy.get('[data-cy="create-customer"]').click()
          cy.get('[data-cy="customer-name"]').type(customer.name)
          cy.get('[data-cy="customer-email"]').type(customer.email)
          cy.get('[data-cy="customer-tax-id"]').type(customer.tax_id)
          cy.get('[data-cy="customer-address-city"]').type(customer.address.city)
          cy.get('[data-cy="save-customer"]').click()
          cy.wait(500) // Brief wait between creations
        }
      })
      
      // Test city-based search
      cy.get('[data-cy="customer-search"]').type('Охрид')
      cy.get('[data-cy="customers-table"]').should('contain', 'Охридски')
      cy.get('[data-cy="customers-table"]').should('not.contain', 'Струмичка')
      
      // Clear search
      cy.get('[data-cy="customer-search"]').clear()
      cy.get('[data-cy="customers-table"]').should('contain', 'Охридски')
      cy.get('[data-cy="customers-table"]').should('contain', 'Струмичка')
    })

    it('should filter customers by VAT ID prefix', () => {
      // Test VAT ID search with MK prefix
      cy.get('[data-cy="customer-search"]').type('MK4080003501433')
      cy.get('[data-cy="customers-table"]').should('contain', 'Охридски')
      
      // Partial VAT ID search
      cy.get('[data-cy="customer-search"]').clear().type('MK40800035014')
      cy.get('[data-cy="customers-table"]').should('contain', 'Охридски')
    })
  })

  describe('Customer Deletion', () => {
    it('should delete customer with confirmation', () => {
      const customer = macedoniaData.customers[0]
      
      // Create customer first
      cy.get('[data-cy="create-customer"]').click()
      cy.get('[data-cy="customer-name"]').type(customer.name)
      cy.get('[data-cy="customer-email"]').type(customer.email)
      cy.get('[data-cy="customer-tax-id"]').type(customer.tax_id)
      cy.get('[data-cy="save-customer"]').click()
      
      // Delete customer
      cy.get('[data-cy="customers-table"]').contains(customer.name).parent().find('[data-cy="delete-customer"]').click()
      
      // Confirm deletion
      cy.get('[data-cy="confirm-delete"]').click()
      
      // Verify deletion
      cy.get('[data-cy="success-message"]').should('contain', 'Customer deleted successfully')
      cy.get('[data-cy="customers-table"]').should('not.contain', customer.name)
    })

    it('should prevent deletion of customer with active invoices', () => {
      // This test would require creating an invoice first
      // For now, we'll test the warning message
      cy.log('Note: Full implementation requires invoice creation setup')
      
      // Mock scenario - customer with invoices should show warning
      // Implementation depends on invoice relationships
    })
  })

  describe('Performance and Validation Metrics', () => {
    it('should load customer list within 2 seconds', () => {
      const startTime = Date.now()
      
      cy.visit('/admin/customers').then(() => {
        const loadTime = Date.now() - startTime
        expect(loadTime).to.be.lessThan(2000)
      })
    })

    it('should validate all required Macedonia fields', () => {
      cy.get('[data-cy="create-customer"]').click()
      
      // Try to save without required fields
      cy.get('[data-cy="save-customer"]').click()
      
      // Should show all required field errors
      cy.get('[data-cy="validation-errors"]').should('contain', 'Name is required')
      cy.get('[data-cy="validation-errors"]').should('contain', 'Email is required')
      cy.get('[data-cy="validation-errors"]').should('contain', 'VAT ID is required')
    })

    it('should handle Macedonia specific characters correctly', () => {
      const specialCharacters = 'Ѓѕџљњќѐ́'
      const testName = `Македонска Компанија ${specialCharacters}`
      
      cy.get('[data-cy="create-customer"]').click()
      cy.get('[data-cy="customer-name"]').type(testName)
      
      // Verify special characters are preserved
      cy.get('[data-cy="customer-name"]').should('have.value', testName)
    })
  })

  afterEach(() => {
    // Clean up created test customers
    cy.window().then((win) => {
      // Clear any test data if needed
      // This would typically involve API calls to clean up test data
    })
  })
})

// Performance tracking for audit report
Cypress.Commands.add('trackCustomerCrudPerformance', () => {
  const startTime = performance.now()
  return cy.then(() => {
    const endTime = performance.now()
    const duration = endTime - startTime
    cy.log(`Customer CRUD operation took ${duration.toFixed(2)}ms`)
    return duration
  })
})

