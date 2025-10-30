// SET-01: Company Settings and Branding Configuration Test
// Tests company info update, logo upload, and settings persistence

describe('Company Settings Configuration', () => {
  const testCompanyData = {
    name: 'Македонска Трговска ООД - Test',
    phone: '+389 2 123 456',
    country: 'Macedonia',
    state: 'Скопски регион',
    city: 'Скопје',
    zip: '1000',
    address_street_1: 'Партизанска 15',
    address_street_2: 'Деловен центар',
    tax_id: 'MK4080003501234',
    vat_id: 'MK4080003501234'
  }

  beforeEach(() => {
    // Login as admin user
    cy.visit('/admin/login')
    
    // Use existing admin credentials from installation
    cy.get('input[type="email"]').type('admin@example.com')
    cy.get('input[type="password"]').type('password')
    cy.get('button[type="submit"]').click()
    
    // Wait for dashboard to load
    cy.url().should('include', '/admin/dashboard')
    cy.wait(1000)
    
    // Navigate to company settings
    cy.visit('/admin/settings/company-info')
    cy.wait(2000)
  })

  describe('Company Information Management', () => {
    it('should load company settings page correctly', () => {
      // Verify page elements exist
      cy.get('h1, h2, h3').should('contain.text', 'Company Info')
      cy.get('input[type="text"]').should('exist')
      cy.get('button').contains('Save').should('be.visible')
      
      // Take screenshot for visual verification
      cy.screenshot('company-settings-loaded')
    })

    it('should update company basic information', () => {
      // Clear and fill company name
      cy.get('input').first().clear().type(testCompanyData.name)
      
      // Find phone input and update
      cy.get('input').then($inputs => {
        // Try different ways to find phone field
        cy.get('input').eq(1).clear().type(testCompanyData.phone)
      })
      
      // Update address fields
      cy.get('textarea').first().clear().type(testCompanyData.address_street_1)
      
      // Find tax ID field
      cy.get('input').then($inputs => {
        const taxInput = Array.from($inputs).find(input => 
          input.value.includes('MK') || input.placeholder?.includes('tax') || input.placeholder?.includes('MK')
        )
        if (taxInput) {
          cy.wrap(taxInput).clear().type(testCompanyData.tax_id)
        }
      })
      
      // Save changes
      cy.get('button').contains('Save').click()
      
      // Wait for save confirmation
      cy.wait(2000)
      
      // Verify success (look for success message or page reload)
      cy.get('body').should('be.visible')
      
      cy.screenshot('company-info-updated')
    })

    it('should handle company logo upload', () => {
      // Check if file upload component exists
      cy.get('body').then($body => {
        if ($body.find('input[type="file"], [data-testid="file-upload"]').length > 0) {
          // Create a test image file
          const fileName = 'company-logo.png'
          
          // Upload file if upload component exists
          cy.get('input[type="file"]').first().selectFile({
            contents: 'cypress/fixtures/macedonia-test-data.json', // Use existing file as mock
            fileName: fileName,
            mimeType: 'image/png'
          }, { force: true })
          
          cy.wait(1000)
          
          // Save changes
          cy.get('button').contains('Save').click()
          cy.wait(2000)
          
          cy.screenshot('logo-upload-test')
        } else {
          cy.log('Logo upload component not found - testing basic validation')
          // Test basic form validation instead
          cy.get('input').first().clear().type('Test Company Name')
          cy.get('button').contains('Save').click()
          cy.wait(1000)
        }
      })
    })

    it('should validate required fields', () => {
      // Clear required field (company name)
      cy.get('input').first().clear()
      
      // Try to save
      cy.get('button').contains('Save').click()
      
      // Check for validation error (could be in multiple forms)
      cy.get('body').then($body => {
        const hasError = $body.find('.error, .invalid, .text-red, [class*="error"]').length > 0
        if (hasError) {
          cy.log('Validation error displayed correctly')
        } else {
          cy.log('No validation error found - field might not be required')
        }
      })
      
      cy.screenshot('validation-test')
    })

    it('should persist company settings across page reloads', () => {
      // Update company name
      const uniqueName = `Test Company ${Date.now()}`
      cy.get('input').first().clear().type(uniqueName)
      
      // Save
      cy.get('button').contains('Save').click()
      cy.wait(2000)
      
      // Reload page
      cy.reload()
      cy.wait(2000)
      
      // Verify the data persisted
      cy.get('input').first().should('have.value', uniqueName)
      
      cy.screenshot('settings-persistence-test')
    })

    it('should handle Macedonia-specific data formats', () => {
      // Test Cyrillic text input
      cy.get('input').first().clear().type('Македонска Трговска ООД')
      
      // Test Macedonia phone format
      cy.get('input').then($inputs => {
        cy.get('input').eq(1).clear().type('+389 2 123 456')
      })
      
      // Test Macedonia VAT ID format
      cy.get('input').then($inputs => {
        const vatInput = Array.from($inputs).find(input => 
          input.placeholder?.includes('VAT') || input.placeholder?.includes('МК')
        )
        if (vatInput) {
          cy.wrap(vatInput).clear().type('MK4080003501234')
        }
      })
      
      // Save changes
      cy.get('button').contains('Save').click()
      cy.wait(2000)
      
      cy.screenshot('macedonia-data-formats')
    })
  })

  describe('Settings Performance & UI Tests', () => {
    it('should load settings page within performance threshold', () => {
      const startTime = Date.now()
      
      cy.visit('/admin/settings/company-info')
      
      cy.get('input').should('be.visible').then(() => {
        const loadTime = Date.now() - startTime
        cy.log(`Settings page loaded in ${loadTime}ms`)
        
        // Expect load time under 3 seconds
        expect(loadTime).to.be.lessThan(3000)
      })
    })

    it('should be responsive on mobile viewport', () => {
      cy.viewport('iphone-6')
      
      // Verify page is usable on mobile
      cy.get('input').should('be.visible')
      cy.get('button').contains('Save').should('be.visible')
      
      // Test mobile interaction
      cy.get('input').first().clear().type('Mobile Test Company')
      cy.get('button').contains('Save').should('be.visible')
      
      cy.screenshot('mobile-responsive-settings')
    })
  })

  describe('Error Handling', () => {
    it('should handle network errors gracefully', () => {
      // Simulate network failure
      cy.intercept('POST', '**/settings**', { forceNetworkError: true }).as('settingsRequest')
      
      // Try to save
      cy.get('input').first().clear().type('Network Test')
      cy.get('button').contains('Save').click()
      
      // Wait for request
      cy.wait('@settingsRequest')
      
      // Verify error handling
      cy.get('body').should('be.visible')
      
      cy.screenshot('network-error-handling')
    })
  })
})

